<?php

namespace Shoperti\PayMe\Gateways\Conekta;

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
     * @param array    $params
     * @param string[] $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function all($params = [], $headers = [])
    {
        return $this->gateway->commit('get', $this->gateway->buildUrlFromString('webhooks'));
    }

    /**
     * Find a webhook by its id.
     *
     * @param int|string $id
     * @param array      $params
     * @param string[]   $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($id = null, $params = [], $headers = [])
    {
        if (!$id) {
            throw new InvalidArgumentException('We need an id');
        }

        return $this->gateway->commit('get', $this->gateway->buildUrlFromString('webhooks/'.$id));
    }

    /**
     * Create a webhook.
     *
     * @param array    $params
     * @param string[] $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($params = [], $headers = [])
    {
        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('webhooks'), $params);
    }

    /**
     * Update a webhook.
     *
     * @param array    $params
     * @param string[] $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function update($params = [], $headers = [])
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
     * @param string[]   $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function delete($id, $params = [], $headers = [])
    {
        return $this->gateway->commit('delete', $this->gateway->buildUrlFromString('webhooks/'.$id));
    }
}
