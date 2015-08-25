<?php

namespace Shoperti\PayMe\Contracts;

interface Charge
{
    /**
     * Charge the credit card.
     *
     * @param int|float $amount
     * @param mixed     $payment
     * @param string[]  $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function charge($amount, $payment, $options = []);
}
