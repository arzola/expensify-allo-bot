<?php

namespace App\Services;

use App\Models\ExpensifyLogin;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExpensifyService
{
    private Client $client;
    private const API_URL = 'https://integrations.expensify.com/Integration-Server/ExpensifyIntegrations';
    private const DOWNLOAD_URL = 'https://integrations.expensify.com/Integration-Server/Download';

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->client = new Client();
    }

    private function downloadAndParseFile(string $filename, ExpensifyLogin $login): array
    {
        try {
            // Use the main API_URL for the download request as well
            $response = $this->client->post(self::API_URL, [
                'form_params' => [
                    'requestJobDescription' => json_encode([
                        'type' => 'download',
                        'credentials' => [
                            'partnerUserID' => $login->partner_id,
                            'partnerUserSecret' => $login->password,
                        ],
                        'fileName' => $filename
                    ]),
                ],
            ]);

            $content = $response->getBody()->getContents();

            // Parse CSV content
            $lines = explode("\n", $content);
            // Check if headers exist before trying to parse
            if (count($lines) === 0 || empty(trim($lines[0]))) {
                Log::warning('Downloaded file is empty or has no headers', [
                    'filename' => $filename,
                    'partner_id' => $login->partner_id
                ]);
                return [];
            }
            $headers = str_getcsv(array_shift($lines));

            $data = [];
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                $values = str_getcsv($line);
                // Add a check for empty lines or lines with incorrect column count
                if (count($values) !== count($headers)) {
                    Log::warning('Skipping line due to mismatched column count', [
                        'filename' => $filename,
                        'header_count' => count($headers),
                        'value_count' => count($values),
                        'line_content' => $line, // Log the problematic line
                        'partner_id' => $login->partner_id
                    ]);
                    continue;
                }

                $data[] = array_combine($headers, $values);
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Error downloading and parsing file from Expensify', [
                'error' => $e->getMessage(),
                'filename' => $filename,
                'partner_id' => $login->partner_id
            ]);
            return [];
        }
    }

    public function getCategorySpentAmount(ExpensifyLogin $login, string $category, Carbon $startDate, Carbon $endDate): float
    {
        $response = $this->client->post(self::API_URL, [
            'form_params' => [
                'requestJobDescription' => json_encode([
                    'type' => 'file',
                    'credentials' => [
                        'partnerUserID' => $login->partner_id,
                        'partnerUserSecret' => $login->password,
                    ],
                    'onReceive' => [
                        'immediateResponse' => ['returnRandomFileName']
                    ],
                    'inputSettings' => [
                        'type' => 'combinedReportData',
                        'filters' => [
                            'startDate' => $startDate->format('Y-m-d'),
                            'endDate' => $endDate->format('Y-m-d'),
                            'category' => $category
                        ]
                    ],
                    'outputSettings' => [
                        'fileExtension' => 'csv'
                    ]
                ]),
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data['fileName'])) {
            return 0;
        }

        $fileData = $this->downloadAndParseFile($data['fileName'], $login);

        return collect($fileData)->sum('amount');
    }

    public function validateCredentials(string $partnerId, string $password): bool
    {
        try {
            $response = $this->client->post(self::API_URL, [
                'form_params' => [
                    'requestJobDescription' => json_encode([
                        'type' => 'get',
                        'credentials' => [
                            'partnerUserID' => $partnerId,
                            'partnerUserSecret' => $password,
                        ],
                        'inputSettings' => [
                            'type' => 'policyList'
                        ]
                    ]),
                ],
            ]);
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $bodyContents = $response->getBody()->getContents();
                $data = json_decode($bodyContents, true);
                if (isset($data['responseCode']) && $data['responseCode'] == 200) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAvailableCategories(ExpensifyLogin $login): array
    {
        try {
            // Use the report exporter to get a list of all categories
            $response = $this->client->post(self::API_URL, [
                'form_params' => [
                    'requestJobDescription' => json_encode([
                        'type' => 'file',
                        'credentials' => [
                            'partnerUserID' => $login->partner_id,
                            'partnerUserSecret' => $login->password,
                        ],
                        'onReceive' => [
                            'immediateResponse' => ['returnRandomFileName']
                        ],
                        'inputSettings' => [
                            'type' => 'combinedReportData',
                            'filters' => [
                                'startDate' => now()->subYear()->format('Y-m-d'),
                                'endDate' => now()->format('Y-m-d'),
                                'limit' => '100'
                            ]
                        ],
                        'outputSettings' => [
                            'fileExtension' => 'csv'
                        ]
                    ]),
                    'template' => '<#if addHeader == true>Category<#lt></#if>
<#list reports as report>
<#list report.transactionList as expense>
${expense.category}<#lt>
</#list>
</#list>'
                ],
            ]);

            $filename = $response->getBody()->getContents();

            if (empty($filename)) {
                Log::error('Empty filename returned from Expensify API', [
                    'partner_id' => $login->partner_id
                ]);
                return [];
            }

            // Add a small delay to allow the file to be generated
            sleep(2);

            // Use the existing downloadAndParseFile method to get the data
            $fileData = $this->downloadAndParseFile($filename, $login);

            // Extract unique categories
            return collect($fileData)
                ->pluck('Category')
                ->filter()
                ->unique()
                ->values()
                ->all();
        } catch (\Exception $e) {
            Log::error('Exception when fetching Expensify categories', [
                'error' => $e->getMessage(),
                'partner_id' => $login->partner_id
            ]);
            return [];
        }
    }
}
