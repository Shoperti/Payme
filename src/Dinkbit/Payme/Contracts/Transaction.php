<?php

namespace Dinkbit\Payme\Contracts;

interface Transaction
{
    /**
     * Is the transaction successful?
     *
     * @return boolean
     */
    public function success();

    /**
     * Does the transaction require a redirect?
     *
     * @return boolean
     */
    public function isRedirect();

    /**
     * Return transaction status.
     *
     * @return string
     */
    public function test();

    /**
     * Return authorization status.
     *
     * @return string
     */
    public function authorization();

    /**
     * Return transaction status.
     *
     * @return string
     */
    public function status();

    /**
     * Response Message.
     *
     * @return string A response message from the payment gateway
     */
    public function message();

    /**
     * Transaction code.
     *
     * @return string A response code from the payment gateway
     */
    public function code();

    /**
     * Gateway Reference.
     *
     * @return string A reference provided by the gateway to represent this transaction
     */
    public function reference();

    /**
     * Gateway raw data.
     *
     * @return array
     */
    public function raw();
}
