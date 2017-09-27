<?php

return [
    'bogus' => [
        'driver' => 'bogus',
    ],

    'compro_pago' => [
        'driver'      => 'compro_pago',
        'private_key' => 'sk_test_75c7b279365b4449d',
        'public_key'  => 'pk_test_613ed49349849c194',
    ],

    'conekta' => [
        'driver'      => 'conekta',
        'private_key' => 'key_TzsZY8UkWKDqKkfRbR9isA',
    ],

    'manual' => [
        'driver' => 'manual',
    ],

    'open_pay' => [
        'driver'      => 'open_pay',
        'id'          => 'mygwhzzmbmrlcn0eu7b5',
        'private_key' => 'sk_7494dfec2c514ca1a4bd40f8c4000d7b',
        'public_key'  => 'pk_330f394b94574e70a4023c35f335b5e3',
        'test'        => true,
    ],

    'paypal' => [
        'driver'    => 'paypal_express',
        'username'  => 'activemerchant-test_api1.example.com',
        'password'  => 'HBC6A84QLRWC923A',
        'signature' => 'AFcWxV21C7fd0v3bYYYRCpSSRl31AC-11AKBL8FFO9tjImL311y8a0hx',
        'test'      => true,
    ],

    'stripe' => [
        'driver'      => 'stripe',
        'private_key' => 'sk_test_3OD4TdKSIOhDOL2146JJcC79',
    ],

    'mercadopago' => [
        'driver'      => 'mercadopago',
        'private_key' => 'TEST-8527269031909288-071213-0fc96cb7cd3633189bfbe29f63722700__LB_LA__-263489584',
    ],
];
