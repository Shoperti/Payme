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
     * @param string[] $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function all($headers = []);

    /**
     * Find an event by its id.
     *
     * @param int|string $id
     * @param array      $options
     * @param string[]   $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($id, array $options = [], $headers = []);
}
