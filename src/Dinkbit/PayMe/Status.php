<?php

namespace Dinkbit\Payme;

use InvalidArgumentException;

class Status
{
    /**
     * Provided status.
     *
     * @var string
     */
    protected $status;

    /**
     * Available statuses.
     *
     * @var string[]
     */
    protected $statuses = [
        'pending',
        'authorized',
        'paid',
        'partially_paid',
        'refunded',
        'voided',
        'partially_refunded',
        'unpaid',
        'failed',
        'active',
        'canceled',
        'trial',
    ];

    /**
     * Create a new status instance.
     *
     * @param $status
     *
     * @return void
     */
    public function __construct($status)
    {
        $this->disallowInvalidMethod($status);
        $this->status = $status;
    }

    /**
     * @param $status
     *
     * @throws \InvalidArgumentException
     */
    protected function disallowInvalidMethod($status)
    {
        if (! in_array($status, $this->statuses)) {
            throw new InvalidArgumentException('Invalid status provided.');
        }
    }

    /**
     * Return status to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->status;
    }
}
