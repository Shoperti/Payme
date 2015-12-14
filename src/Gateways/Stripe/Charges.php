<?php

namespace Shoperti\PayMe\Gateways\Stripe;

use Shoperti\PayMe\Gateways\AbstractApi;

/**
  * This is the stripe charges class.
  *
  * @author joseph.cohen@dinkbit.com
  */
 class Charges extends AbstractApi implements ChargeInterface
 {
     /**
     * Charge the credit card.
     *
     * @param int      $amount
     * @param string   $payment
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function charge($amount, $payment, $options = [])
    {
        $params = [];

        $params = $this->addOrder($params, $amount, $options);
        $params = $this->addCard($params, $payment, $options);
        $params = $this->addCustomer($params, $payment, $options);

        return $this->commit('post', $this->buildUrlFromString('charges'), $params);
    }

    /**
     * Add order params to request.
     *
     * @param string[] $params
     * @param int      $money
     * @param string[] $options
     *
     * @return array
     */
    protected function addOrder(array $params, $money, array $options)
    {
        $params['description'] = Helper::cleanAccents(Arr::get($options, 'description', 'PayMe Purchase'));
        $params['receipt_number'] = Arr::get($options, 'reference');
        $params['currency'] = Arr::get($options, 'currency', $this->getCurrency());
        $params['amount'] = $this->amount($money);

        return $params;
    }

    /**
     * Add payment method to request.
     *
     * @param string[] $params
     * @param mixed    $payment
     * @param string[] $options
     *
     * @return array
     */
    protected function addCard(array $params, $payment, array $options)
    {
        if (is_string($payment)) {
            $params['card'] = $payment;
        } elseif ($payment instanceof CreditCard) {
            $params['card'] = [];
            $params['card']['name'] = $payment->getName();
            $params['card']['cvc'] = $payment->getCvv();
            $params['card']['number'] = $payment->getNumber();
            $params['card']['exp_month'] = $payment->getExpiryMonth();
            $params['card']['exp_year'] = $payment->getExpiryYear();
            $params['card'] = $this->addAddress($params['card'], $options);
        }

        return $params;
    }

    /**
     * Add address to request.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addAddress(array $params, array $options)
    {
        if ($address = Arr::get($options, 'address') or Arr::get($options, 'billing_address')) {
            $params['address'] = [];
            $params['address']['street1'] = Arr::get($address, 'address1');
            $params['address']['street2'] = Arr::get($address, 'address2');
            $params['address']['street3'] = Arr::get($address, 'address3');
            $params['address']['city'] = Arr::get($address, 'city');
            $params['address']['country'] = Arr::get($address, 'country');
            $params['address']['state'] = Arr::get($address, 'state');
            $params['address']['zip'] = Arr::get($address, 'zip');

            return $params;
        }
    }

    /**
     * Add customer to request.
     *
     * @param string[] $params
     * @param string   $creditcard
     * @param string[] $options
     *
     * @return array
     */
    protected function addCustomer(array $params, $creditcard, array $options)
    {
        if (array_key_exists('customer', $options)) {
            $params['customer'] = $options['customer'];
        }

        return $params;
    }
 }
