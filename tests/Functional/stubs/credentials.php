<?php

return [
    'bogus' => [
        'driver'      => 'bogus',
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
        'driver'      => 'paypal_express',
        'username'    => 'activemerchant-test_api1.example.com',
        'password'    => 'HBC6A84QLRWC923A',
        'signature'   => 'AFcWxV21C7fd0v3bYYYRCpSSRl31AC-11AKBL8FFO9tjImL311y8a0hx',
        'test'        => true,
    ],
];