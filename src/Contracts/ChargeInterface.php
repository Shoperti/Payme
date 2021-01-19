<?php

namespace Shoperti\PayMe\Contracts;

/**
 * This is the charge interface.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
interface ChargeInterface
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
    public function create($amount, $payment, $options = [], $headers = []);

    /**
     * Get a charge.
     *
     * @param string   $id
     * @param array    $options
     * @param string[] $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function get($id, $options = [], $headers = []);

    /**
     * Complete a charge.
     *
     * @param string[] $options
     * @param string[] $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function complete($options = [], $headers = []);

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
    public function refund($amount, $reference, array $options = [], $headers = []);
}
