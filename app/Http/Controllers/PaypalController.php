<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;

class PaypalController extends Controller
{
    public function payWithPaypal(Request $request){

        $payAmount = 220;
        $paymentId = 123;

        $order_request = new OrdersCreateRequest();
        $order_request->prefer('return=representation');
        $order_request->body = array(
            'intent' => 'CAPTURE',
            'application_context' =>
                array(
                    'return_url' => url('/payment/paypal/return'),
                    'cancel_url' => url('/payment/paypal/return')
                ),
            'purchase_units' =>[
                [
                    "reference_id" => $paymentId,
                    "description" => 'Your transaction description',
                    "amount" => [
                        "value" => $payAmount,
                        "currency_code" => 'USD',
                        'breakdown' => [
                            'item_total' =>
                                array(
                                    'currency_code' => 'USD',
                                    'value' => '180.00',
                                ),
                            'shipping' =>
                                array(
                                    'currency_code' => 'USD',
                                    'value' => '20.00',
                                ),
                            'handling' =>
                                array(
                                    'currency_code' => 'USD',
                                    'value' => '10.00',
                                ),
                            'tax_total' =>
                                array(
                                    'currency_code' => 'USD',
                                    'value' => '20.00',
                                ),
                            'shipping_discount' =>
                                array(
                                    'currency_code' => 'USD',
                                    'value' => '10.00',
                                ),
                        ]
                    ],
                    'items' =>[
                        [
                            'name' => 'T-Shirt',
                            'description' => 'Green XL',
                            'sku' => 'sku01',
                            'unit_amount' =>
                                array(
                                    'currency_code' => 'USD',
                                    'value' => '90.00',
                                ),
                            'tax' =>
                                array(
                                    'currency_code' => 'USD',
                                    'value' => '10.00',
                                ),
                            'quantity' => '1',
                            'category' => 'PHYSICAL_GOODS',
                        ],
                        [
                            'name' => 'Shoes',
                            'description' => 'Running, Size 10.5',
                            'sku' => 'sku02',
                            'unit_amount' =>
                                array(
                                    'currency_code' => 'USD',
                                    'value' => '45.00',
                                ),
                            'tax' =>
                                array(
                                    'currency_code' => 'USD',
                                    'value' => '5.00',
                                ),
                            'quantity' => '2',
                            'category' => 'PHYSICAL_GOODS',
                        ],
                    ],
                    'shipping' => [
                        'method' => 'United States Postal Service',
                        'name' =>
                            array(
                                'full_name' => 'John Doe',
                            ),
                        'address' =>
                            array(
                                'address_line_1' => '123 Townsend St',
                                'address_line_2' => 'Floor 6',
                                'admin_area_2' => 'San Francisco',
                                'admin_area_1' => 'CA',
                                'postal_code' => '94107',
                                'country_code' => 'US',
                            ),
                    ]

                ]
            ]
        );

        try {
            $paypal = \Config::get('paypal');

            $clientId = $paypal['client_id'];
            $clientSecret = $paypal['secret'];
            $mode = $paypal['mode'];

            if($mode == 'live')
                $client = new PayPalHttpClient(new ProductionEnvironment($clientId, $clientSecret));
            else
                $client = new PayPalHttpClient(new SandboxEnvironment($clientId, $clientSecret));

            $response = $client->execute($order_request);

            return response()->json($response,200);

        } catch (\Exception $ex) {
            \Log::error($ex->getMessage());
            return response()->json(['message' => 'Something went wrong. Please try again.'], $ex->statusCode);
        }

    }

    public function getPaypalPaymentStatus(Request $request)
    {
        $order_request = new OrdersCaptureRequest($request->orderID);

        try {
            $paypal = \Config::get('paypal');

            $clientId = $paypal['client_id'];
            $clientSecret = $paypal['secret'];
            $mode = $paypal['mode'];

            if($mode == 'live')
                $client = new PayPalHttpClient(new ProductionEnvironment($clientId, $clientSecret));
            else
                $client = new PayPalHttpClient(new SandboxEnvironment($clientId, $clientSecret));

            $response = $client->execute($order_request);

            if ($response->result->status == 'COMPLETED') {
               \Log::info('Purchase Code:'. $response->result->purchase_units[0]->payments->captures[0]->id);

                return response()->json(['response'=>$response], 200);
            }
            return response()->json(['message' => 'Something went wrong'],500);

        } catch (HttpException $ex) {
            \Log::error($ex->getMessage());

            return response()->json(['message' => 'Something went wrong'], $ex->statusCode);
        }
    }
}
