# Shoperti PayMe

[![Build Status](https://travis-ci.org/Shoperti/Payme.svg)](https://travis-ci.org/Shoperti/Payme)
[![StyleCI](https://styleci.io/repos/24345061/shield)](https://styleci.io/repos/24345061)

Supported Gateways:
* Conekta
* Stripe
* Paypal Express (soon)

## Installation

Begin by installing this package through Composer. Edit your project's `composer.json` file to require `Shoperti/payme`.

```json
"require": {
  "shoperti/payme": "2.0-dev"
}
```

Next, update Composer from the Terminal:

    composer update

### Examples

```php
// Create a new PayMe instance choosing the driver
$config = [
	'driver'      => 'stripe',
	'private_key' => 'secret_key',
	'public_key'  => 'public_key',
];


$payme = (new Shoperti\PayMe\PayMe($config);
or
$payme = PayMe::make($config);

// Make charge
$response = $gateway->charges()->create('100', 'tok_test', []);

if (!$response->success()) {
	return ':(';
}

return 'Hurray!';
```

### Todo

- [ ] Add Gateways tests
- [ ] Add Credit Card object
- [ ] Create a Laravel Bridge
- [ ] Add more gateways

## License

PayMe is licensed under [The MIT License (MIT)](LICENSE).
