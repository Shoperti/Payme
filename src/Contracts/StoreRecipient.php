<?php

namespace Shoperti\PayMe\Contracts;

interface StoreRecipient
{
    /**
     * Stores a new recipient.
     *
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function storeRecipient($options = []);

    /**
     * Unstores an existing recipient.
     *
     * @param string   $reference
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function unstoreRecipient($reference, $options = []);
}
