<?php

namespace Dinkbit\Payme;

use ArrayAccess;

class Transaction implements ArrayAccess, Contracts\Transaction
{
    /**
     * Has the transaction made by the Gateway?
     *
     * @var bool
     */
    public $success;

    /**
     * Does the Gateway needs to redirect.
     *
     * @var bool
     */
    public $isRedirect;

    /**
     * Is the Gateway in tests mode?
     *
     * @var bool
     */
    public $test;

    /**
     * Has the message sent by the Gateway?
     *
     * @var bool
     */
    public $status;

    /**
     * The authorization code for the transaction.
     *
     * @var string
     */
    public $authorization;

    /**
     * The response message from the transaction.
     *
     * @var string
     */
    public $message;

    /**
     * The code for the transaction.
     *
     * @var string
     */
    public $code;

    /**
     * The reference code for the transaction.
     *
     * @var string
     */
    public $reference;

    /**
     * The raw transaction information.
     *
     * @var array
     */
    public $transaction;

    /**
     * Is the transaction successful?
     *
     * @return bool
     */
    public function success()
    {
        return (bool) $this->success;
    }

    /**
     * Does the transaction require a redirect?
     *
     * @return bool
     */
    public function isRedirect()
    {
        return (bool) $this->isRedirect;
    }

    /**
     * Return transaction gateway is in test mode.
     *
     * @return string
     */
    public function test()
    {
        return (bool) $this->test;
    }

    /**
     * Return authorization code.
     *
     * @return string
     */
    public function authorization()
    {
        return $this->authorization;
    }

    /**
     * Response Message from the payment gateway.
     *
     * @return string
     */
    public function message()
    {
        return (string) $this->message;
    }

    /**
     * Transaction code from the payment gateway.
     *
     * @return string
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * Return transaction status.
     *
     * @return string
     */
    public function status()
    {
        return (string) $this->status;
    }

    /**
     * Gateway reference to represent this transaction.
     *
     * @return string
     */
    public function reference()
    {
        return $this->reference;
    }

    /**
     * Gateway raw data.
     *
     * @return array
     */
    public function raw()
    {
        return $this->transaction;
    }

    /**
     * Set the raw transaction array from the gateway.
     *
     * @param array $transaction
     *
     * @return \Dinkbit\PayMe\Transaction
     */
    public function setRaw(array $transaction)
    {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Map the given array onto the user's properties.
     *
     * @param array $attributes
     *
     * @return \Dinkbit\PayMe\Transaction
     */
    public function map(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    /**
     * Determine if the given raw user attribute exists.
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->transaction);
    }

    /**
     * Get the given key from the raw user.
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->transaction[$offset];
    }

    /**
     * Set the given attribute on the raw user array.
     *
     * @param string $offset
     * @param mixed  $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->transaction[$offset] = $value;
    }

    /**
     * Unset the given value from the raw user array.
     *
     * @param string $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->transaction[$offset]);
    }
}
