<?php

namespace Shoperti\PayMe\Gateways\OpenPay;

use BadMethodCallException;
use InvalidArgumentException;
use Shoperti\PayMe\Contracts\WebhookInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
 * This is the OpenPay events class.
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
        throw new BadMethodCallException();
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
