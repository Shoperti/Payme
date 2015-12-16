<?php

namespace Shoperti\PayMe\Contracts;

/**
 * This is the event interface.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
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
