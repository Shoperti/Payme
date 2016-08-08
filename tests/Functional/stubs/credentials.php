<?php

return [
    'bogus' => [
        'driver' => 'bogus',
    ],

    'manual' => [
        'driver' => 'manual',
    ],

    'conekta' => [
        'driver'      => 'conekta',
        'private_key' => 'key_eYvWV7gSDkNYXsmr',
    ],

    'stripe' => [
        'driver'      => 'stripe',
        'private_key' => 'sk_test_3OD4TdKSIOhDOL2146JJcC79',
    ],

    'paypal' => [
        'driver'    => 'paypal_express',
        'username'  => 'activemerchant-test_api1.example.com',
        'password'  => 'HBC6A84QLRWC923A',
        'signature' => 'AFcWxV21C7fd0v3bYYYRCpSSRl31AC-11AKBL8FFO9tjImL311y8a0hx',
        'test'      => true,
    ],

    'compro_pago' => [
        'driver'      => 'compro_pago',
        'private_key' => 'sk_test_75c7b279365b4449d',
        'public_key'  => 'pk_test_613ed49349849c194',
    ],
];
