<?php

namespace Shoperti\PayMe\Gateways\PaypalPlus;

use InvalidArgumentException;
use Shoperti\PayMe\Contracts\WebhookInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the PayPal plus webhooks class.
 *
 * @see https://developer.paypal.com/docs/api/webhooks/v1/
 *
 * @author Arturo Rodríguez <arturo.rodriguez@dinkbit.com>
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
        return $this->gateway->commit(
            'get',
            $this->gateway->buildUrlFromString('notifications/webhooks'),
            [],
            $params,
            $headers
        );
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

        return $this->gateway->commit(
            'get',
            $this->gateway->buildUrlFromString('notifications/webhooks/'.$id),
            [],
            $params,
            $headers
        );
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
        if (!array_key_exists('event_types', $params)) {
            $params['event_types'] = [[
                'name' => '*',
            ]];
        }

        return $this->gateway->commit(
            'post',
            $this->gateway->buildUrlFromString('notifications/webhooks'),
            $params,
            [],
            $headers
        );
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

        // json_patch required
        return $this->gateway->commit(
            'patch',
            $this->gateway->buildUrlFromString('notifications/webhooks/'.$id),
            [],
            $params,
            $headers
        );
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
        return $this->gateway->commit(
            'delete',
            $this->gateway->buildUrlFromString('notifications/webhooks/'.$id),
            [],
            $params,
            $headers
        );
    }
}
