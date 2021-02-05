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
     * @param string[]  $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($amount, $payment, $options = [], $headers = [])
    {
        $params = $this->addOrder($amount, $options);

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('payment_intents'), $params, $options);
    }

    /**
     * Get a charge.
     *
     * @param string   $id
     * @param array    $options
     * @param string[] $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function get($id, $options = [], $headers = [])
    {
        return $this->gateway->commit('get', $this->gateway->buildUrlFromString(sprintf('payment_intents/%s', $id)));
    }

    /**
     * Complete a charge.
     *
     * @param string[] $options
     * @param string[] $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function complete($options = [], $headers = [])
    {
        throw new BadMethodCallException();
    }

    /**
     * Refund a charge.
     *
     * @param int|float $amount
     * @param string    $reference
     * @param string[]  $options
     * @param string[]  $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function refund($amount, $reference, array $options = [], $headers = [])
    {
        throw new BadMethodCallException();
    }

    /**
     * Add order params to request.
     *
     * @param int      $money
     * @param string[] $options
     *
     * @return array
     */
    protected function addOrder($money, array $options)
    {
        $params['description'] = Helper::ascii(Arr::get($options, 'description', 'PayMe Purchase'));
        $params['currency'] = Arr::get($options, 'currency', $this->gateway->getCurrency());
        $params['amount'] = $this->gateway->amount($money);
        $params['metadata'] = ['integration_check' => 'accept_a_payment'];

        if (isset($options['reference'])) {
            $params['metadata']['reference'] = Arr::get($options, 'reference');
        }

        return $params;
    }
}
