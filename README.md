## Laravel PayPal Documentation

### Introduction

The Laravel PayPal package provides an expressive and fluent interface for the PayPal API. It allows you to integrate
PayPal payments into your Laravel applications seamlessly.

### Installation

To get started with Laravel PayPal, install the package via Composer:

```bash 
composer require srmklive/paypal
```

After installation, you can use the following commands to publish the assets:

```bash
php artisan vendor:publish --provider "Srmklive\PayPal\Providers\PayPalServiceProvider"
```

After publishing the assets, add the following to your .env files .

```
#PayPal API Mode
# Values: sandbox or live (Default: live)
PAYPAL_MODE=

#PayPal Setting & API Credentials - sandbox
PAYPAL_SANDBOX_CLIENT_ID=
PAYPAL_SANDBOX_CLIENT_SECRET=

#PayPal Setting & API Credentials - live
PAYPAL_LIVE_CLIENT_ID= USER_ID
PAYPAL_LIVE_CLIENT_SECRET= CLIENT_SECRET
```

The configuration file paypal.php is located in the config folder. Following are its contents when published:

```php
return [
    'mode'    => env('PAYPAL_MODE', 'sandbox'), // Can only be 'sandbox' Or 'live'. If empty or invalid, 'live' will be used.
    'sandbox' => [
        'client_id'         => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
        'client_secret'     => env('PAYPAL_SANDBOX_CLIENT_SECRET', ''),
        'app_id'            => 'APP-80W284485P519543T',
    ],
    'live' => [
        'client_id'         => env('PAYPAL_LIVE_CLIENT_ID', ''),
        'client_secret'     => env('PAYPAL_LIVE_CLIENT_SECRET', ''),
        'app_id'            => '',
    ],

    'payment_action' => env('PAYPAL_PAYMENT_ACTION', 'Sale'), // Can only be 'Sale', 'Authorization' or 'Order'
    'currency'       => env('PAYPAL_CURRENCY', 'USD'),
    'notify_url'     => env('PAYPAL_NOTIFY_URL', ''), // Change this accordingly for your application.
    'locale'         => env('PAYPAL_LOCALE', 'en_US'), // force gateway language  i.e. it_IT, es_ES, en_US ... (for express checkout only)
    'validate_ssl'   => env('PAYPAL_VALIDATE_SSL', true), // Validate SSL when creating api client.
];
```

### Create Controller Paypal

```bash
php artiasan make:controller PaypalController
```

Copy this code to use paypal library on your controller:

```php
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
```

### Create Model Payment

```bash
php artisan make:model Payment -m
```

Customize your migration table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id');
            $table->string('product_name');
            $table->string('quantity');
            $table->string('amount');
            $table->string('currency');
            $table->string('payer_name');
            $table->string('payer_email');
            $table->string('payment_status');
            $table->string('payment_method');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
```

Migrate your model

```bash
php artisan migrate
```

### Run the app

```bash
php artisan serve
```



