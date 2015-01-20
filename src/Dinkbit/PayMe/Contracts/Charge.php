<?php

namespace Dinkbit\PayMe\Contracts;

interface Charge
{
    /**
     * Charge the credit card.
     *
     * @param $amount
     * @param $payment
     * @param string[] $options
     *
     * @return \Dinkbit\Payme\Transaction
     */
    public function charge($amount, $payment, $options = []);
}
