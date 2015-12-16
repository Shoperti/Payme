<?php

namespace Shoperti\PayMe\Contracts;

/**
 * This is the card interface.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
interface CardInterface
{
    /**
     * Save a credit card.
     *
     * @param mixed    $creditcard
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($creditcard, $options = []);

    /**
     * Delete a credit card.
     *
     * @param string   $id
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function delete($id, $options = []);
}
