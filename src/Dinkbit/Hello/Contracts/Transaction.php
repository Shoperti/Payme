<?php

namespace Dinkbit\Payme\Contracts;

interface Transaction
{
    /**
     * Is the transaction successful?
     *
     * @return bool
     */
    public function success();

    /**
     * Does the transaction require a redirect?
     *
     * @return bool
     */
    public function isRedirect();

    /**
     * Return transaction gateway is in test mode.
     *
     * @return string
     */
    public function test();

    /**
     * Return authorization code.
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
     * Response Message from the payment gateway.
     *
     * @return string
     */
    public function message();

    /**
     * Transaction code from the payment gateway.
     *
     * @return string
     */
    public function code();

    /**
     * Gateway reference to represent this transaction.
     *
     * @return string
     */
    public function reference();

    /**
     * Gateway raw data.
     *
     * @return array
     */
    public function raw();
}
