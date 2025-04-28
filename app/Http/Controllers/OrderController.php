<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Services\ApiResponseService;
use DateTime;
use App\Services\ClouErpWarrantyService;
use App\Services\AppsyOrderService;

class OrderController extends Controller
{
    
    protected $responseService;
    protected $orderService;

    public function __construct(ApiResponseService $responseService,AppsyOrderService $orderService)
    {
        $this->responseService = $responseService;
        $this->orderService = $orderService;
    }


    //Function to get all missing warranty orders for my store 
    public function list_missing_warranty_orders(Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'appsy_domain' => 'required',
                'appsy_token' => 'required',
                'appsy_limit' => 'required|integer',
            ]);
            if ($validator->fails()) {
                return $this->responseService->sendError('Validation failed!',$validator->errors(),422);
            }

            $limit = $request['appsy_limit'];
            $domain = $request['appsy_domain'];
            $token = $request['appsy_token'];

            // Build API URL with query parameters
            $since = (new DateTime())->modify('-2 months')->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $queryParams = http_build_query([
                'since' => $since,
                'limit' => $limit,
                'with_warranties' => true,
            ]);

            $url = "https://$domain.myappsy.com/eapi/webshop/order?$queryParams";

            // Set headers
            $header = [
                'Content-Type: application/json',
                "Authorization: Bearer $token",
            ];

            // Initialize cURL
            $curl = curl_init();

            // Set cURL options
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,  // Set a timeout for the request (e.g., 30 seconds)
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => $header,
            ]);

            // Execute the API call and get the response
            $response = curl_exec($curl);

            // Check for cURL errors
            if(curl_errno($curl)) {
                // Handle error or return appropriate response
                curl_close($curl);
                return $this->responseService->sendError('Something went wrong!',curl_error($curl),400);
            }

            // Close cURL session
            curl_close($curl);

            // Decode the response
            $responseData = json_decode($response, true);

            // Check for valid JSON response
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->responseService->sendError('Error decoding JSON response !',json_last_error_msg(),400);  // Handle error appropriately
            }


            $orders = collect($responseData['data']) ->filter(function ($order) {
                        return empty($order['warranties']);
                    })->values(); 
            
            return $this->responseService->sendResponse($orders, 'Orders retrived successfully!',200);
        }catch (\Exception $e) {
            // Handle any other unexpected errors
            return $this->responseService->sendError('Something went wrong!',$e->getMessage(),500);
        }
        
    }


    // function used to update new warranty info for all missing orders 
    public function update_missing_warranty_orders(Request $request){

        try{

            $validator = Validator::make($request->all(), [
                'appsy_domain' => 'required',
                'appsy_token' => 'required',
                'appsy_limit' => 'required|integer',
                'clouderp_token' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->responseService->sendError('Validation failed!',$validator->errors(),422);
            }

            //define variables 
            $clouderp_token = $request['clouderp_token'];

            ////////////////////////////// Step 1 //////////////////////////////////////// 
            //get all orders from Cloud ERP 
            $clouderp_url = 'https://app.clouderp.hu/api/1/automatism/file-share/?s='.$clouderp_token;

            //using Service or function to get all orders from ERP 
            $erpService = new ClouErpWarrantyService($clouderp_url);
            //insert only warranty data into variable 
            $warrantyData = $erpService->fetchWarrantyData();

            ///////////////////////////// Step 2 //////////////////////////////////////// 
            //get all current Appsy Order Details 
            $currentMissingWarrantyOrders = $this->orderService->listMissingWarrantyOrders($request->all());


            ///////////////////////////// Step 3 //////////////////////////////////////// 
            //Update all waranties with values
            
            $warrantyItems_array=[];

            // Loop through Appsy missing warranty orders
            foreach ($currentMissingWarrantyOrders['data'] as $orderData) {
                $unasId = $orderData['webshop_id']; 

                //Check if this order exists in CloudERP data
                if (!isset($clouderpOrders[$unasId])) {
                    continue; // Skip this order
                }

                // Check if this order exists in ERP warranty data
                if (isset($warrantyData[$unasId])) {

                    $warrantyItems = [];

                    // Build warranty items array from ERP
                    foreach ($currentMissingWarrantyOrders[$unasId] as $warrantyData) {
                        $warrantyItems[] = [
                            'sku'           => $warrantyData['sku'],
                            'valid_from'    => $warrantyData['completed_date'],
                            'valid_months'  => $warrantyData['warranty_period'],
                        ];
                    }

                    $result = $this->orderService->setWarrantyData(
                        $request->domain,
                        $orderData['webshop_id'],
                        $request->token,
                        $warrantyItems
                    );

                    if (!$result['success']) {
                        return $this->responseService->sendError('Error setting warranty data', $result['errors'],400);
                    }
                }
            }

            return $this->responseService->sendResponse([], 'Warranty data set successfully',200);

        }catch (\Exception $e) {
            // Handle any other unexpected errors
            return $this->responseService->sendError('Something went wrong!',$e->getMessage(),500);
        }


        
    }





}
