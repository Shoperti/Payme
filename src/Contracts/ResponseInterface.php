<?php

namespace Shoperti\PayMe\Contracts;

/**
 * This is the response interface.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
interface ResponseInterface
{
    /**
     * Get if the response is successful.
     *
     * @return bool
     */
    public function success();

    /**
     * Get if the response require a redirect.
     *
     * @return bool
     */
    public function isRedirect();

    /**
     * Get the reference id to represent this response.
     *
     * @return string
     */
    public function reference();

    /**
     * Get if gateway is in test mode.
     *
     * @return string
     */
    public function test();

    /**
     * Get the authorization code.
     *
     * @return string
     */
    public function authorization();

    /**
     * Get the response message.
     *
     * @return string
     */
    public function message();

    /**
     * Get the error code from the response.
     *
     * @return string
     */
    public function errorCode();

    /**
     * Get the response status.
     *
     * @return string
     */
    public function status();

    /**
     * Get the gateway response type.
     *
     * @return string
     */
    public function type();

    /**
     * Get the gateway raw response.
     *
     * @return array
     */
    public function data();
}
