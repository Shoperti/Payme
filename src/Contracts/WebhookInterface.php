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
     * Find all webhooks.
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function all();

    /**
     * Find an webhook by its id.
     *
     * @param int|string|null $id
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($id = null);

    /**
     * Create an webhook.
     *
     * @param array $params
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($params = []);

    /**
     * Update an webhook.
     *
     * @param array $params
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function update($params = []);

    /**
     * Delete an webhook.
     *
     * @param int|string $id
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function delete($id);
}
