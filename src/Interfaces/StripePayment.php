<?php

namespace App\Interfaces;

use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripePayment
{
    public function __construct(readonly private string $clientSecret)
    {
        Stripe::setApiKey($this->clientSecret);
        Stripe::setApiVersion('2020-08-27');
    }
    public function startPayment($panier)
    {

        $session = Session::create([
            'mode' => 'payment',
            'success_url' => 'http://localhost:8000/',
            'cancel_url' => 'http://localhost:8000/panier',
            'billing_address_collection' => 'required',
            'shipping_address_collection' => [
                'allowed_countries' => ['FR']
            ]
        ]);
    }
}