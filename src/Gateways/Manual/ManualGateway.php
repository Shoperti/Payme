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
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function commit($method, $url, $params = [], $options = [])
    {
        return $this->mapResponse(true, $params);
    }

    /**
     * Map HTTP response to transaction object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function mapResponse($success, $response)
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
