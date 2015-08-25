<?php

namespace Shoperti\PayMe\Contracts;

interface Store
{
    /**
     * Stores a credit card.
     *
     * @param mixed    $creditcard
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function store($creditcard, $options = []);

    /**
     * Unstores a credit card.
     *
     * @param string   $reference
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function unstore($reference, $options = []);
}
