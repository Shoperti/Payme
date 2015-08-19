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
     * @return \Shoperti\Payme\Transaction
     */
    public function charge($amount, $payment, $options = []);
}
