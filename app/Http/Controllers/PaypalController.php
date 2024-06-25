<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaypalController extends Controller
{

    public function __construct()
    {
        $provider = new PayPalClient();

        $this->provider = $provider;
        $this->provider->setApiCredentials(config('paypal'));
        $this->provider->getAccessToken();
    }

    public function paypal(Request $request)
    {
        $response = $this->provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('success'),
                "cancel_url" => route('cancel'),
            ],
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $request->price
                    ]
                ]
            ]
        ]);

        if (isset($response['id']) && $response['id'] != null) {
            foreach ($response['links'] as $link) {
                if ($link['rel'] == 'approve') {
                    session()->put('product_name', $request->product_name);
                    session()->put('quantity', $request->quantity);
                    return redirect()->away($link['href']);
                }
            }
        } else {
            return redirect()->route('cancel');
        }
    }

    public function success(Request $request)
    {
        $paypalToken = $this->provider->getAccessToken();
        $response = $this->provider->capturePaymentOrder($request->token);

        if (isset($response['status']) && $response['status'] == 'COMPLETED') {
            try {
                $payment = new Payment();
                $payment->payment_id = $response['id'];
                $payment->product_name = session()->get('product_name');
                $payment->quantity = session()->get('quantity');
                $payment->amount = $response['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
                $payment->currency = $response['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'];
                $payment->payer_name = $response['payer']['name']['given_name'];
                $payment->payer_email = $response['payer']['email_address'];
                $payment->payment_status = $response['status'];
                $payment->payment_method = "PayPal";

                $payment->save();

                return "Payment is success";
                unset($_SESSION['product_name']);
                unset($_SESSION['quantity']);
            } catch (\Exception $e) {
                throw $e;
            }
        } else {
            return redirect()->route('cancel');
        }
    }

    public function cancel()
    {
        return "Payment is canceled";
    }
}
