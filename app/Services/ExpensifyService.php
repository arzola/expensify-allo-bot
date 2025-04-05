<?php

namespace App\Services;

use App\Models\ExpensifyLogin;
use GuzzleHttp\Client;
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
        $response = $this->client->post(self::DOWNLOAD_URL, [
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
        $headers = str_getcsv(array_shift($lines));
        
        $data = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $values = str_getcsv($line);
            if (count($values) !== count($headers)) continue;
            
            $data[] = array_combine($headers, $values);
        }
        
        return $data;
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

            $data = json_decode($response->getBody()->getContents(), true);
            
            // Check if we got a valid response without errors
            return !isset($data['error']);
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    public function getAvailableCategories(ExpensifyLogin $login): array
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
                            'startDate' => now()->subYear()->format('Y-m-d'),
                            'endDate' => now()->format('Y-m-d'),
                            'limit' => '100'
                        ]
                    ],
                    'outputSettings' => [
                        'fileExtension' => 'csv'
                    ]
                ]),
                'template' => 'Category,Amount\n${expense.category},${expense.amount}\n'
            ],
        ]);

        $filename = $response->getBody()->getContents();
        
        // Download the actual CSV file
        $fileResponse = $this->client->post(self::API_URL, [
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

        $csvContent = $fileResponse->getBody()->getContents();
        
        // Parse CSV content
        $lines = explode("\n", $csvContent);
        $headers = str_getcsv(array_shift($lines)); // Remove and parse header row
        
        $categories = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $values = str_getcsv($line);
            if (count($values) !== count($headers)) continue;
            
            $row = array_combine($headers, $values);
            if (isset($row['Category'])) {
                $categories[] = $row['Category'];
            }
        }
        
        return array_unique($categories);
    }
}
