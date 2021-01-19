<?php

namespace Shoperti\PayMe\Contracts;

/**
 * This is the webhook interface.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
interface WebhookInterface
{
    /**
     * Get all webhooks.
     *
     * @param array    $params
     * @param string[] $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function all($params = [], $headers = []);

    /**
     * Find a webhook by its id.
     *
     * @param int|string|null $id
     * @param array           $options
     * @param string[]        $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($id = null, $params = [], $headers = []);

    /**
     * Create a webhook.
     *
     * @param array    $params
     * @param string[] $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($params = [], $headers = []);

    /**
     * Update a webhook.
     *
     * @param array    $params
     * @param string[] $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function update($params = [], $headers = []);

    /**
     * Delete a webhook.
     *
     * @param int|string $id
     * @param array      $params
     * @param string[]   $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function delete($id, $params = [], $headers = []);
}
