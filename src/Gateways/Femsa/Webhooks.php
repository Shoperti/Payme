<?php

namespace Shoperti\PayMe\Gateways\Femsa;

use InvalidArgumentException;
use Shoperti\PayMe\Contracts\WebhookInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the conekta events class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class Webhooks extends AbstractApi implements WebhookInterface
{
    /**
     * Get all webhooks.
     *
     * @param array $params
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function all($params = [])
    {
        return $this->gateway->commit('get', $this->gateway->buildUrlFromString('webhooks'));
    }

    /**
     * Find a webhook by its id.
     *
     * @param int|string $id
     * @param array      $params
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($id = null, $params = [])
    {
        if (!$id) {
            throw new InvalidArgumentException('We need an id');
        }

        return $this->gateway->commit('get', $this->gateway->buildUrlFromString('webhooks/'.$id));
    }

    /**
     * Create a webhook.
     *
     * @param array $params
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($params = [])
    {
        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('webhooks'), $params);
    }

    /**
     * Update a webhook.
     *
     * @param array $params
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function update($params = [])
    {
        $id = Arr::get($params, 'id', null);

        if (!$id) {
            throw new InvalidArgumentException('We need an id');
        }

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('webhooks/'.$id), $params);
    }

    /**
     * Delete a webhook.
     *
     * @param int|string $id
     * @param array      $params
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function delete($id, $params = [])
    {
        return $this->gateway->commit('delete', $this->gateway->buildUrlFromString('webhooks/'.$id));
    }
}
