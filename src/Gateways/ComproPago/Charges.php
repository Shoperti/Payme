<?php

namespace Shoperti\PayMe\Gateways\ComproPago;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Support\Helper;

/**
 * This is the Compro Pago charges class.
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
        $params = $this->addCustomer($params, $options);
        $params['payment_type'] = mb_strtoupper($payment, 'UTF-8');

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
        $params['order_id'] = Arr::get($options, 'reference');
        $params['order_name'] = Helper::ascii(Arr::get($options, 'description', 'PayMe Purchase'));
        $params['order_price'] = $this->gateway->amount($money);

        return $params;
    }

    /**
     * Add order details params.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addCustomer(array $params, array $options)
    {
        $params['customer_name'] = Arr::get($options, 'name', '');
        $params['customer_email'] = Arr::get($options, 'email', '');

        return $params;
    }
}
