<?php

namespace Shoperti\PayMe\Contracts;

interface EventInterface
{
    /**
     * Find all events.
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function all();

    /**
     * Find an event by its id.
     *
     * @param int|string|null $id
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($id = null);
}
