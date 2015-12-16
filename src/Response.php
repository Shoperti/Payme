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
     * The authorization code for the response.
     *
     * @var string
     */
    public $authorization;

    /**
     * The response message from the response.
     *
     * @var string
     */
    public $message;

    /**
     * The code for the response.
     *
     * @var string
     */
    public $code;

    /**
     * The reference code for the response.
     *
     * @var string
     */
    public $reference;

    /**
     * The raw response information.
     *
     * @var array
     */
    public $response;

    /**
     * Is the response successful?
     *
     * @return bool
     */
    public function success()
    {
        return (bool) $this->success;
    }

    /**
     * Does the response require a redirect?
     *
     * @return bool
     */
    public function isRedirect()
    {
        return (bool) $this->isRedirect;
    }

    /**
     * Return response gateway is in test mode.
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
     * Return response status.
     *
     * @return string
     */
    public function status()
    {
        return (string) $this->status;
    }

    /**
     * Gateway reference to represent this response.
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
