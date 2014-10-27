# Dinkbit Payme

**Warning**: Alpha version, do not use in production.

Based on [Active Merchant](http://github.com/Shopify/active_merchant) for Ruby and [Aktive-Merchant](https://github.com/akDeveloper/Aktive-Merchant) for PHP

Supported Gateways:
* Conekta
* Conekta Oxxo
* Conekta Bank
* Banwire Recurrent
* Paypal Express (soon)

Examples

```php

$payme->driver('conekta')->store('tok_test_visa_4242', ['name' => 'Joe Co', 'email' => 'store.guy@mail.com'])

$payme->driver('conekta')->store('tok_test_visa_4242', ['customer' => 'cus_test'])

$payme->driver('conekta')->charge('1000.00', 'tok_test_card_declined')

$payme->driver('conekta')->unstore('cus_test', ['card_id' => 'tok_test_visa_4242'])

$payme->driver('conekta')->unstore('cus_test')

$payme->driver('conektaoxxo')->charge('200.00', 'oxxo')

$payme->driver('conektabank')->charge('200.00', 'banorte')

$payme->driver('banwirerecurrent')->charge('200.00', '8305ab68d4acf7dc650364d3f31a7318', [
  'card_id' => '1407',
  'card_name' => 'Joseph Co',
  'customer' => '1'
])

```

### Todo

- [ ] Add tests
- [ ] Fix credit cards implementation
- [ ] Add CI
- [ ] Add more gateways
