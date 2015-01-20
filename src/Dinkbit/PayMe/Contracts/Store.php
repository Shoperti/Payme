<?php

namespace Dinkbit\PayMe\Contracts;

interface Store
{
    /**
     * Stores a credit card.
     *
     * @param $creditcard
     * @param string[] $options
     *
     * @return mixed
     */
    public function store($creditcard, $options = []);

    /**
     * Unstores a credit card.
     *
     * @param $reference
     * @param string[] $options
     *
     * @return mixed
     */
    public function unstore($reference, $options = []);
}
