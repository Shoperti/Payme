<?php

namespace Shoperti\PayMe\Gateways\PaypalExpress;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Support\Helper;

/**
 * This is the PayPal express charges class.
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

        $params['METHOD'] = $payment;
        $params['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Sale';
        $params['RETURNURL'] = Arr::get($options, 'return_url');
        $params['CANCELURL'] = Arr::get($options, 'cancel_url');

        $params = $this->addOrder($params, $amount, $options);
        $params = $this->addBN($params, $options);

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString(''), $params, [
            'isRedirect' => true,
        ]);
    }

    /**
     * Complete a charge.
     *
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function complete($options = [])
    {
        $params = [];

        $params['METHOD'] = 'DoExpressCheckoutPayment';
        $params['PAYMENTREQUEST_0_PAYMENTACTION'] = Arr::get($options, 'action', 'Sale');
        $params['TOKEN'] = Arr::get($options, 'token');
        $params['PAYERID'] = Arr::get($options, 'payerid');

        $params['PAYMENTREQUEST_0_DESC'] = Helper::ascii(Arr::get($options, 'description', 'PayMe Purchase'));
        $params['PAYMENTREQUEST_0_INVNUM'] = Arr::get($options, 'reference');
        $params['PAYMENTREQUEST_0_CURRENCYCODE'] = Arr::get($options, 'currency', $this->gateway->getCurrency());
        $params['PAYMENTREQUEST_0_AMT'] = $this->gateway->amount(Arr::get($options, 'amount'));
        $params['PAYMENTREQUEST_0_NOTIFYURL'] = Arr::get($options, 'notify_url');

        if (isset($options['shipping_address']['price'])) {
            $params['PAYMENTREQUEST_0_SHIPPINGAMT'] = $this->gateway->amount($options['shipping_address']['price']);
        }

        $params = $this->addLineItems($params, $options);
        $params = $this->addBN($params, $options);

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString(''), $params);
    }

    /**
     * Refund a charge.
     *
     * @param int|float $amount
     * @param string    $reference
     * @param string[]  $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function refund($amount, $reference, array $options = [])
    {
        throw new BadMethodCallException();
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
        $params['PAYMENTREQUEST_0_DESC'] = Helper::ascii(Arr::get($options, 'description', 'PayMe Purchase'));
        $params['PAYMENTREQUEST_0_INVNUM'] = Arr::get($options, 'reference');
        $params['PAYMENTREQUEST_0_CURRENCYCODE'] = Arr::get($options, 'currency', $this->gateway->getCurrency());
        $params['PAYMENTREQUEST_0_AMT'] = $this->gateway->amount($money);

        $params = $this->addLineItems($params, $options);
        $params = $this->addShippingAddress($params, $options);

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
        if (isset($options['line_items']) && is_array($options['line_items'])) {
            $params['PAYMENTREQUEST_0_ITEMAMT'] = 0;

            foreach ($options['line_items'] as $n => $lineItem) {
                $params["L_PAYMENTREQUEST_0_NAME$n"] = Arr::get($lineItem, 'name');
                $params["L_PAYMENTREQUEST_0_DESC$n"] = Arr::get($lineItem, 'description');
                $params["L_PAYMENTREQUEST_0_QTY$n"] = Arr::get($lineItem, 'quantity', 1);
                $params["L_PAYMENTREQUEST_0_AMT$n"] = $this->gateway->amount(Arr::get($lineItem, 'unit_price'));
                $params['PAYMENTREQUEST_0_ITEMAMT'] += Arr::get($lineItem, 'quantity', 1) * Arr::get($lineItem, 'unit_price');
            }

            $params['PAYMENTREQUEST_0_ITEMAMT'] = $this->gateway->amount($params['PAYMENTREQUEST_0_ITEMAMT']);
        }

        return $params;
    }

    /**
     * Add button code to request.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addBN(array $params, array $options)
    {
        if (array_key_exists('application', $options)) {
            $params['BUTTONSOURCE'] = $options['application'];
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
            $params['ADDROVERRIDE'] = 1;
            $params['PAYMENTREQUEST_0_SHIPPINGAMT'] = $this->gateway->amount(Arr::get($address, 'price', 0));
            $params['PAYMENTREQUEST_0_SHIPTOSTREET'] = Arr::get($address, 'address1');
            $params['PAYMENTREQUEST_0_SHIPTOSTREET2'] = Arr::get($address, 'address2');
            $params['PAYMENTREQUEST_0_SHIPTOCITY'] = Arr::get($address, 'city');
            $params['PAYMENTREQUEST_0_SHIPTOSTATE'] = Arr::get($address, 'state');
            $params['PAYMENTREQUEST_0_SHIPTOZIP'] = Arr::get($address, 'zip');
            $params['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = Arr::get($address, 'country');
        }

        return $params;
    }
}
