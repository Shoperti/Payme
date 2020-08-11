<?php

namespace Shoperti\PayMe;

use Exception;

/**
 * This is the ResponseException class.
 */
class ResponseException extends Exception
{
    /**
     * The raw response.
     *
     * @var array|null
     */
    private $response;

    /**
     * Create a new instance.
     *
     * @param \Exception $exception
     * @param array|null $response
     */
    public function __construct(Exception $exception, $response)
    {
        parent::__construct($exception->getMessage(), 0, $exception);

        $this->response = $response;
    }

    /**
     * Get the raw response.
     *
     * @return array|null
     */
    protected function getResponse()
    {
        return $this->response;
    }
}
