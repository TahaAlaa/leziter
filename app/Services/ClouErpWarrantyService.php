<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Http;

class ClouErpWarrantyService
{
    protected $jsonUrl;

    public function __construct($jsonUrl = null)
    {
        $this->jsonUrl = $jsonUrl;
    }

    public function fetchWarrantyData()
    {
        if (!$this->jsonUrl) {
            throw new \Exception('ERP Warranty URL not set.');
        }

        try {
            // Fetch the content
            $response = Http::timeout(30)->get($this->jsonUrl);

            if ($response->failed()) {
                throw new \Exception('Failed to fetch warranty data from ERP.');
            }

            $orders = $response->json();

            if (!isset($orders['results'])) {
                throw new \Exception('Invalid warranty data format.');
            }

            $filteredOrders = [];

            foreach ($orders['results'] as $order) {
                $orderId = $order['webshopItemId'] ?? null;
                $completedDate = $order['completedTimestamp'] ?? null;
                $sku = $order['lineItems']['product']['sku'] ?? null;
                $warrantyPeriod = $order['lineItems']['product']['warrantyPeriod'] ?? null;

                if (!$orderId || !$completedDate || !$sku || !$warrantyPeriod) {
                    continue; // skip invalid records
                }

                $filteredOrders[$orderId][] = [
                    'completed_date' => (new DateTime($completedDate))->format('Y-m-d'),
                    'sku' => $sku,
                    'warranty_period' => $warrantyPeriod
                ];
            }

            return $filteredOrders;

        } catch (\Exception $e) {
            // You can log the error if needed
            \Log::error('ERP Warranty Fetch Error: ' . $e->getMessage());

            return []; // Return empty array on failure
        }
    }
}
