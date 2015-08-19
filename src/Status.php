<?php

namespace Shoperti\PayMe;

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
     * Valid statuses.
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
        $this->disallowInvalidStatus($status);
        $this->status = $status;
    }

    /**
     * Validate status provided.
     *
     * @param $status
     *
     * @throws \InvalidArgumentException
     */
    protected function disallowInvalidStatus($status)
    {
        if (!in_array($status, $this->statuses)) {
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
