<?php

namespace Shoperti\PayMe\Gateways\Manual;

use BadMethodCallException;
use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Response;

/**
 * This is the manual gateway class.
 *
 * @author Arturo Rodríguez <arturo.rodriguez@dinkbit.com>
 */
class ManualGateway extends AbstractGateway
{
    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'manual';

    /**
     * Gateway default currency.
     *
     * @var string
     */
    protected $defaultCurrency = 'USD';

    /**
     * Gateway money format.
     *
     * @var string
     */
    protected $moneyFormat = 'cents';

    /**
     * Commit a HTTP request.
     *
     * @param string   $method
     * @param string   $url
     * @param string[] $params
     * @param string[] $options
     * @param string[] $customHeaders
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function commit($method, $url, $params = [], $options = [], $customHeaders = [])
    {
        return $this->respond($params);
    }

    /**
     * Respond with an array of responses or a single response.
     *
     * @param array $response
     * @param array $_
     *
     * @return array|\Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function respond($response, $_ = [])
    {
        return $this->mapResponse($response);
    }

    /**
     * Map HTTP response to transaction object.
     *
     * @param array $response
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    protected function mapResponse($response)
    {
        return (new Response())->setRaw($response)->map([
            'isRedirect'    => false,
            'success'       => true,
            'reference'     => null,
            'message'       => null,
            'test'          => false,
            'authorization' => null,
            'status'        => $response['status'],
            'errorCode'     => false,
            'type'          => $response['type'],
        ]);
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        throw new BadMethodCallException();
    }
}
