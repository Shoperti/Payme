<?php

namespace Dinkbit\PayMe\Contracts;

interface StoreRecipient
{
    /**
     * Stores a Recipient.
     *
     * @param string[] $options
     *
     * @return mixed
     */
    public function storeRecipient($options = []);

    /**
     * Unstores a Recipient.
     *
     * @param $reference
     * @param string[] $options
     *
     * @return mixed
     */
    public function unstoreRecipient($reference, $options = []);
}
