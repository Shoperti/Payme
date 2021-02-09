<?php

namespace Shoperti\PayMe\Gateways\Bogus;

use Shoperti\PayMe\ErrorCode;
use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\Status;

/**
 * This is the bogus gateway class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class BogusGateway extends AbstractGateway
{
    /**
     * Gateway API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://example.com';

    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'bogus';

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
        $response = [];

        $success = $params['transaction'] == 'success';

        return $this->respond($response, ['success' => $success]);
    }

    /**
     * Respond with an array of responses or a single response.
     *
     * @param array $response
     * @param array $params
     *
     * @return array|\Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function respond($response, $params = [])
    {
        return $this->mapResponse($params['success'], $response);
    }

    /**
     * Map HTTP response to transaction object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    protected function mapResponse($success, $response)
    {
        return (new Response())->setRaw($response)->map([
            'isRedirect'    => false,
            'success'       => $success,
            'reference'     => $success ? '12345' : null,
            'message'       => $success ? 'Approved' : 'Error',
            'test'          => false,
            'authorization' => $success ? '123' : '',
            'status'        => $success ? new Status('paid') : new Status('failed'),
            'errorCode'     => $success ? false : new ErrorCode('card_declined'),
            'type'          => 'charge',
        ]);
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->endpoint;
    }
}
