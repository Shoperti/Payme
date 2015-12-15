<?php

namespace Shoperti\PayMe\Contracts;

interface CustomerInterface
{
    /**
     * Create a customer.
     *
     * @param string[]  $attributes
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($attributes = []);
}
