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
     * @param array $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function all($params = []);

    /**
     * Find a webhook by its id.
     *
     * @param int|string|null $id
     * @param array           $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($id = null, $params = []);

    /**
     * Create a webhook.
     *
     * @param array $params
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($params = []);

    /**
     * Update a webhook.
     *
     * @param array $params
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function update($params = []);

    /**
     * Delete a webhook.
     *
     * @param int|string $id
     * @param array      $params
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function delete($id, $params = []);
}
