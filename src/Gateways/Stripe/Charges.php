<?php

namespace Shoperti\PayMe\Gateways\Stripe;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Support\Helper;

/**
 * This is the stripe charges class.
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
        $params = $this->addCard($params, $payment, $options);
        $params = $this->addCustomer($params, $payment, $options);

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('charges'), $params);
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
        $params['description'] = Helper::ascii(Arr::get($options, 'description', 'PayMe Purchase'));
        $params['currency'] = Arr::get($options, 'currency', $this->gateway->getCurrency());
        $params['amount'] = $this->gateway->amount($money);

        if (isset($options['reference'])) {
            $params['metadata']['reference'] = Arr::get($options, 'reference');
        }

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
            if (Helper::startsWith($payment, 'cus')) {
                $params['customer'] = $payment;
            } else {
                $params['source'] = $payment;
            }
        }

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
    protected function addCustomer(array $params, $creditcard, array $options)
    {
        if (array_key_exists('customer', $options)) {
            $params['customer'] = $options['customer'];
        }

        return $params;
    }
}
