<?php

namespace Shoperti\PayMe\Contracts;

interface Charge
{
    /**
     * Charge the credit card.
     *
     * @param $amount
     * @param $payment
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function charge($amount, $payment, $options = []);
}
