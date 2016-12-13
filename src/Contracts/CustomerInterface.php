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
     * Find a customer.
     *
     * @param string $customer
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($customer);

    /**
     * Create a customer.
     *
     * @param string[] $attributes
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($attributes = []);

    /**
     * Update a customer.
     *
     * @param string   $customer
     * @param string[] $attributes
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function update($customer, $attributes = []);
}
