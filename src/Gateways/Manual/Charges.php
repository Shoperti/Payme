<?php

namespace Shoperti\PayMe\Gateways\Manual;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Status;

/**
 * This is the manual charges class.
 *
 * @author Arturo Rodríguez <arturo.rodriguez@dinkbit.com>
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
        $params = [
            'type'   => 'charge',
            'amount' => $amount,
            'status' => new Status('pending'),
        ];

        return $this->gateway->commit(null, null, $params);
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
        $params = [
            'type'   => 'charge',
            'status' => new Status('paid'),
        ];

        return $this->gateway->commit(null, null, $params);
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
        $params = [
            'type'   => 'refund',
            'amount' => $amount,
            'status' => new Status('refunded'),
        ];

        return $this->gateway->commit(null, null, $params);
    }
}
