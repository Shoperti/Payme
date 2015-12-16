<?php

namespace Shoperti\PayMe\Gateways\Conekta;

use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Support\Helper;

/**
 * This is the conekta charges class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class Charges extends AbstractApi implements ChargeInterface
{
    /**
     * Create a charge.
     *
     * @param int|float $amount
     * @param mixed     $payment
     * @param string[]  $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($amount, $payment, $options = [])
    {
        $params = [];

        $params = $this->addOrder($params, $amount, $options);
        $params = $this->addPaymentMethod($params, $payment, $options);
        $params = $this->addOrderDetails($params, $options);

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('charges'), $params);
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
        $params['description'] = Helper::ascii(Arr::get($options, 'description', 'PayMe Purchase'));
        $params['reference_id'] = Arr::get($options, 'reference');
        $params['currency'] = Arr::get($options, 'currency', $this->gateway->getCurrency());
        $params['amount'] = $this->gateway->amount($money);

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
    protected function addPaymentMethod(array $params, $payment, array $options)
    {
        if (is_string($payment)) {
            if ($payment == 'spei') {
                $params['bank']['type'] = 'spei';
                $params['bank']['expires_at'] = Arr::get($options, 'expires', date('Y-m-d', time() + 172800));
            } elseif ($payment == 'oxxo') {
                $params['cash']['type'] = 'oxxo';
                $params['cash']['expires_at'] = Arr::get($options, 'expires', date('Y-m-d', time() + 172800));
            } elseif (Helper::startsWith($payment, 'payee_')) {
                $params['payee_id'] = $payment;
            } else {
                $params['card'] = $payment;
            }
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
        if ($address = Arr::get($options, 'address') || Arr::get($options, 'billing_address')) {
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
     * Add order details params.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addOrderDetails(array $params, array $options)
    {
        if (isset($options['name'])) {
            $params['details']['name'] = Arr::get($options, 'name', '');
        }

        if (isset($options['email'])) {
            $params['details']['email'] = Arr::get($options, 'email', '');
        }

        if (isset($options['phone'])) {
            $params['details']['phone'] = Arr::get($options, 'phone', '');
        }

        $params = $this->addCustomer($params, $options);
        $params = $this->addLineItems($params, $options);
        $params = $this->addBillingAddress($params, $options);
        $params = $this->addShippingAddress($params, $options);

        return $params;
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
    protected function addCustomer(array $params, array $options)
    {
        if ($customer = Arr::get($options, 'customer')) {
            $params['details']['customer'] = [];
            $params['details']['customer']['logged_in'] = Arr::get($customer, 'logged_in');
            $params['details']['customer']['successful_purchases'] = Arr::get($customer, 'successful_purchases');
            $params['details']['customer']['created_at'] = Arr::get($customer, 'created_at');
            $params['details']['customer']['updated_at'] = Arr::get($customer, 'updated_at');
            $params['details']['customer']['offline_payments'] = Arr::get($customer, 'offline_payments');
            $params['details']['customer']['score'] = Arr::get($customer, 'score');
        }

        return $params;
    }

    /**
     * Add order line items param.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addLineItems(array $params, array $options)
    {
        $params['details']['line_items'] = [];

        if (isset($options['line_items']) && is_array($options['line_items'])) {
            foreach ($options['line_items'] as $line_item) {
                $params['details']['line_items'][] = [
                    'name'        => Arr::get($line_item, 'name'),
                    'description' => Arr::get($line_item, 'description'),
                    'unit_price'  => $this->gateway->amount(Arr::get($line_item, 'unit_price')),
                    'quantity'    => Arr::get($line_item, 'quantity', 1),
                    'sku'         => Arr::get($line_item, 'sku'),
                    'category'    => Arr::get($line_item, 'category'),
                ];
            }
        }

        return $params;
    }

    /**
     * Add Billing address to request.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addBillingAddress(array $params, array $options)
    {
        if ($address = Arr::get($options, 'billing_address')) {
            $params['details']['billing_address'] = [];
            $params['details']['billing_address']['street1'] = Arr::get($address, 'address1');
            $params['details']['billing_address']['street2'] = Arr::get($address, 'address2');
            $params['details']['billing_address']['street3'] = Arr::get($address, 'address3');
            $params['details']['billing_address']['city'] = Arr::get($address, 'city');
            $params['details']['billing_address']['country'] = Arr::get($address, 'country');
            $params['details']['billing_address']['state'] = Arr::get($address, 'state');
            $params['details']['billing_address']['zip'] = Arr::get($address, 'zip');
            $params['details']['billing_address']['tax_id'] = Arr::get($address, 'tax_id');
            $params['details']['billing_address']['company_name'] = Arr::get($address, 'company_name');
            $params['details']['billing_address']['phone'] = Arr::get($address, 'phone');
            $params['details']['billing_address']['email'] = Arr::get($address, 'email');
        }

        return $params;
    }

    /**
     * Add Shipping address to request.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addShippingAddress(array $params, array $options)
    {
        if ($address = Arr::get($options, 'shipping_address')) {
            $params['details']['shipment'] = [];
            $params['details']['shipment']['carrier'] = Arr::get($address, 'carrier');
            $params['details']['shipment']['service'] = Arr::get($address, 'service');
            $params['details']['shipment']['price'] = Arr::get($address, 'price');
            $params['details']['shipment']['address']['street1'] = Arr::get($address, 'address1');
            $params['details']['shipment']['address']['street2'] = Arr::get($address, 'address2');
            $params['details']['shipment']['address']['street3'] = Arr::get($address, 'address3');
            $params['details']['shipment']['address']['city'] = Arr::get($address, 'city');
            $params['details']['shipment']['address']['state'] = Arr::get($address, 'state');
            $params['details']['shipment']['address']['zip'] = Arr::get($address, 'zip');
            $params['details']['shipment']['address']['country'] = Arr::get($address, 'country');
        }

        return $params;
    }
}
