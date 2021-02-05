<?php

namespace Shoperti\PayMe\Gateways\Bogus;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
 * This is the bogus charges class.
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
        $params = [];

        $params['transaction'] = 'fail';

        if ($payment === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->gateway->commit('post', 'charges', $params);
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
        throw new BadMethodCallException();
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
}
