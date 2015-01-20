<?php namespace Dinkbit\Payme\Contracts;

interface Store
{
    /**
     * @param $creditcard
     * @param array $options
     *
     * @return mixed
     */
    public function store($creditcard, $options = []);

    /**
     * @param $reference
     * @param array $options
     *
     * @return mixed
     */
    public function unstore($reference, $options = []);
}
