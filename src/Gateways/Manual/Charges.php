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
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($amount, $payment, $options = [])
    {
        $params = [
            'type'   => 'charge',
            'amount' => $amount,
            'status' => new Status('pending'),
        ];

        return $this->gateway->commit(null, null, $params);
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
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function refund($amount, $reference, array $options = [])
    {
        $params = [
            'type'   => 'refund',
            'amount' => $amount,
            'status' => new Status('refunded'),
        ];

        return $this->gateway->commit(null, null, $params);
    }
}
