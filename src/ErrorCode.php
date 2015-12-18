<?php

namespace Shoperti\PayMe;

use InvalidArgumentException;

/**
 * This is the error code class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class ErrorCode
{
    /**
     * Provided code.
     *
     * @var string
     */
    protected $code;

    /**
     * Valid codes.
     *
     * @var string[]
     */
    protected $codes = [
        'incorrect_number',
        'invalid_number',
        'invalid_expiry_date',
        'invalid_cvc',
        'expired_card',
        'incorrect_cvc',
        'incorrect_zip',
        'incorrect_address',
        'incorrect_pin',
        'card_declined',
        'processing_error',
        'call_issuer',
        'pick_up_card',
        'config_error',
        'insufficient_funds',
        'suspected_fraud',
    ];

    /**
     * Create a new code instance.
     *
     * @param string $code
     *
     * @return void
     */
    public function __construct($code)
    {
        $this->disallowInvalidStatus($code);
        $this->code = $code;
    }

    /**
     * Validate code provided.
     *
     * @param string $code
     *
     * @throws \InvalidArgumentException
     */
    protected function disallowInvalidStatus($code)
    {
        if (!in_array($code, $this->codes)) {
            throw new InvalidArgumentException('Invalid code provided.');
        }
    }

    /**
     * Return code to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->code;
    }
}
