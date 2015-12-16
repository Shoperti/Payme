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
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($amount, $payment, $options = []);
}
