<?php

namespace Shoperti\PayMe\Contracts;

/**
 * This is the customer interface.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
interface CustomerInterface
{
    /**
     * Create a customer.
     *
     * @param string[] $attributes
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($attributes = []);
}
