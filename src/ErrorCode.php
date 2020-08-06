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
        'incorrect_address',
        'incorrect_cvc',
        'incorrect_number',
        'incorrect_pin',
        'incorrect_zip',
        'invalid_cvc',
        'invalid_expiry_date',
        'invalid_number',
        'invalid_amount',
        'invalid_state', // something misconfigured with the remote resources so the action cannot be performed
        'expired_card',
        'card_declined',
        'processing_error',
        'call_issuer',
        'pick_up_card',
        'insufficient_funds',
        'suspected_fraud',
        'invalid_encryption',
        'config_error',
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
