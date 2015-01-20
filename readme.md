# Dinkbit PayMe

[![Build Status](https://img.shields.io/travis/dinkbit/payme.svg?style=flat-square)](https://travis-ci.org/dinkbit/payme)

**Warning**: Beta version API can change.

Based on [Active Merchant](http://github.com/Shopify/active_merchant) for Ruby and [Aktive-Merchant](https://github.com/akDeveloper/Aktive-Merchant) for PHP

Supported Gateways:
* Conekta
* Conekta Oxxo
* Conekta Bank
* Banwire Recurrent
* Paypal Express (soon)

Examples

```php
$amount = 1000; //cents

$payme->driver('conekta')->charge($amount, 'tok_test_card_declined')

$payme->driver('conektaoxxo')->charge($amount, 'oxxo')

$payme->driver('conektabank')->charge($amount, 'banorte')

$payme->driver('banwirerecurrent')->charge($amount, '8305ab68d4acf7dc650364d3f31a7318', [
  'card_id' => '1407',
  'card_name' => 'Joseph Co',
  'customer' => '1'
]);

$payme->driver('conekta')->store('tok_test_visa_4242', ['name' => 'Joe Co', 'email' => 'store.guy@mail.com']);

$payme->driver('conekta')->store('tok_test_visa_4242', ['customer' => 'cus_test']);

$payme->driver('conekta')->unstore('cus_test', ['card_id' => 'tok_test_visa_4242']);

$payme->driver('conekta')->unstore('cus_test');

```

### Todo

- [ ] Add tests
- [ ] Fix credit cards implementation
- [ ] Add more gateways
