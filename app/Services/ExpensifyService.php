<?php

namespace App\Services;

use App\Models\ExpensifyLogin;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ExpensifyService
{
    private Client $client;
    private const API_URL = 'https://integrations.expensify.com/Integration-Server/ExpensifyIntegrations';

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
            // Revert back to explode for line splitting
            $lines = explode("\n", $content);

            // Check if headers exist before trying to parse
            // Revert empty check logic
            if (count($lines) === 0 || empty(trim($lines[0]))) {
                return [];
            }
            $headers = str_getcsv(array_shift($lines));

            $data = [];
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                $values = str_getcsv($line);
                // Add a check for empty lines or lines with incorrect column count
                if (count($values) !== count($headers)) {
                    continue;
                }

                $data[] = array_combine($headers, $values);
            }

            return $data;
        } catch (\Exception $e) {
            return [];
        }
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
                return [];
            }

            // Add a small delay to allow the file to be generated
            sleep(config('services.slack.delay'));

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
            return [];
        }
    }

    /**
     * Fetches all expenses within a date range and returns amounts summed by category.
     *
     * @param ExpensifyLogin $login
     * @return array<string, float> Associative array mapping category name to total spent amount.
     * @throws GuzzleException
     */
    public function getSpentAmountsByCategories(ExpensifyLogin $login): array
    {
        try {
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
                                'startDate' => Carbon::now()->startOfYear()->format('Y-m-d'),
                                'endDate' => Carbon::today()->format('Y-m-d'),
                                // No specific category filter - get all
                            ]
                        ],
                        'outputSettings' => [
                            'fileExtension' => 'csv'
                        ]
                    ]),
                    'template' => '<#if addHeader == true>Category,Amount<#lt></#if>
<#list reports as report>
<#list report.transactionList as expense>
${expense.category},${expense.convertedAmount}<#lt>
</#list>
</#list>'
                ],
            ]);

            $filename = $response->getBody()->getContents();

            if (empty($filename)) {
                return [];
            }

            // Might need adjustment based on typical report generation time
            sleep(config('services.slack.delay'));

            $fileData = $this->downloadAndParseFile($filename, $login);

            if (empty($fileData)) {
                return []; // downloadAndParseFile logs errors internally
            }

            // Group by category and sum amounts
            return collect($fileData)
                ->groupBy('Category') // Use the actual header name from the CSV template
                ->map(function ($items, $category) {
                     // Ensure 'Amount' is treated as numeric, using the header from the template
                    return $items->sum(fn($item) => (float) ($item['Amount'] ?? 0));
                })
                ->all();

        } catch (ClientException $e) {
            return [];
        } catch (\Exception $e) {
            report($e);
            return [];
        }
    }
}
