# Shoperti PayMe

[![Build Status](https://travis-ci.org/Shoperti/Payme.svg)](https://travis-ci.org/Shoperti/Payme)
[![StyleCI](https://styleci.io/repos/24345061/shield)](https://styleci.io/repos/24345061)

Supported Gateways:
* Conekta
* Conekta Oxxo
* Conekta Bank
* Conekta Payouts
* Stripe
* Paypal Express (soon)

## Installation

Begin by installing this package through Composer. Edit your project's `composer.json` file to require `Shoperti/payme`.

```json
"require": {
  "shoperti/payme": "dev-master"
}
```

Next, update Composer from the Terminal:

    composer update

### Examples

```php
// Create a new PayMeFactory
$payme = new Shoperti\PayMe\PayMeFactory();

// Get a specific gateway.
$gateway = $payme->make([
	'driver'      => 'stripe',
	'private_key' => 'secret_key',
	'public_key'  => 'public_key',
]);

// Make transaction
$transaction = $gateway->charge('100', 'tok_test');

if (! $transaction->success()) {
	return ':(';
}

return 'Hurray!';
```

### Todo

- [ ] Add Gateways tests
- [ ] Add more gateways
- [ ] Add Credit Card object
- [ ] Create a Laravel Bridge

## License

PayMe is licensed under [The MIT License (MIT)](LICENSE).
