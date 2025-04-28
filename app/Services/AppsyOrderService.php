<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AppsyOrderService
{
    public function listMissingWarrantyOrders(array $params)
    {
        
        try {
            $limit = $params['appsy_limit'];
            $domain = $params['appsy_domain'];
            $token = $params['appsy_token'];

            // Build API URL
            $since = (new DateTime())->modify('-2 months')->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $queryParams = http_build_query([
                'since' => $since,
                'limit' => $limit,
                'with_warranties' => true,
            ]);

            $url = "https://{$domain}.myappsy.com/eapi/webshop/order?{$queryParams}";

            // Send request using Laravel Http Client (not raw cURL)
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$token}",
            ])->timeout(30)->get($url);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'errors' => $response->body(),
                    'status' => $response->status(),
                ];
            }

            $responseData = $response->json();

            if (!isset($responseData['data'])) {
                return [
                    'success' => false,
                    'errors' => 'Invalid API response format.',
                    'status' => 400,
                ];
            }

            // Filter orders without warranties
            $orders = collect($responseData['data'])
                        ->filter(fn($order) => empty($order['warranties']))
                        ->values();

            return [
                'success' => true,
                'data' => $orders,
                'status' => 200,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }



    public function setWarrantyData(array $params)
    {
        try {
            
            $domain = $params['appsy_domain'];
            $token = $params['appsy_token'];
            $orderId = $params['orderId'];
            $warrantyItems = $params['warrantyItems'];

            $url = "https://{$domain}.myappsy.com/eapi/webshop/order/warranty/{$orderId}";

            $payload = [
                'warranty_items' => $warrantyItems,
            ];

            // Send POST request using Laravel HTTP Client
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$token}",
            ])
            ->timeout(30) // You can adjust timeout if needed
            ->post($url, $payload);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'errors' => $response->body(),
                    'status' => $response->status(),
                ];
            }

            return [
                'success' => true,
                'data' => $response->json(),
                'status' => 200,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }



    
}
