<?php

namespace Shoperti\PayMe\Contracts;

interface RecipientInterface
{
    /**
     * Stores a new recipient.
     *
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($options = []);

    /**
     * Deletes an existing recipient.
     *
     * @param string   $id
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function delete($id, $options = []);
}
