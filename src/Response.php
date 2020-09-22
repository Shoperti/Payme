<?php

namespace Shoperti\PayMe;

use ArrayAccess;
use Shoperti\PayMe\Contracts\ResponseInterface;

/**
 * This is the response class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class Response implements ArrayAccess, ResponseInterface
{
    /**
     * Has the response made by the Gateway?
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
     * The reference id for the response.
     *
     * @var string
     */
    public $reference;

    /**
     * The response message from the response.
     *
     * @var string
     */
    public $message;

    /**
     * Is the Gateway in tests mode?
     *
     * @var bool
     */
    public $test;

    /**
     * The authorization code for the response.
     *
     * @var string
     */
    public $authorization;

    /**
     * Has the status sent by the Gateway?
     *
     * @var bool
     */
    public $status;

    /**
     * The error code for the response.
     *
     * @var string
     */
    public $errorCode;

    /**
     * The type of the gateway response.
     *
     * @var string
     */
    public $type;

    /**
     * The raw response information.
     *
     * @var array
     */
    public $response;

    /**
     * Get if the response is successful.
     *
     * @return bool
     */
    public function success()
    {
        return (bool) $this->success;
    }

    /**
     * Get if the response require a redirect.
     *
     * @return bool
     */
    public function isRedirect()
    {
        return (bool) $this->isRedirect;
    }

    /**
     * Get the reference id to represent this response.
     *
     * @return string
     */
    public function reference()
    {
        return $this->reference;
    }

    /**
     * Get if gateway is in test mode.
     *
     * @return bool
     */
    public function test()
    {
        return (bool) $this->test;
    }

    /**
     * Get the authorization code.
     *
     * @return string
     */
    public function authorization()
    {
        return $this->authorization;
    }

    /**
     * Get the response message.
     *
     * @return string
     */
    public function message()
    {
        return (string) $this->message;
    }

    /**
     * Get the error code from the response.
     *
     * @return string
     */
    public function errorCode()
    {
        return (string) $this->errorCode;
    }

    /**
     * Get the response status.
     *
     * @return string
     */
    public function status()
    {
        return (string) $this->status;
    }

    /**
     * Get the gateway response type.
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Get the gateway raw response.
     *
     * @return array
     */
    public function data()
    {
        return $this->response;
    }

    /**
     * Set the raw response array from the gateway.
     *
     * @param array $response
     *
     * @return \Shoperti\PayMe\Response
     */
    public function setRaw(array $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Map the given array onto the user's properties.
     *
     * @param array $attributes
     *
     * @return \Shoperti\PayMe\Response
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
        return array_key_exists($offset, $this->response);
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
        return $this->response[$offset];
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
        $this->response[$offset] = $value;
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
        unset($this->response[$offset]);
    }
}
