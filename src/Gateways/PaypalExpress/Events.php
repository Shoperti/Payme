<?php

namespace Shoperti\PayMe\Gateways\PaypalExpress;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\EventInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the PayPalExpress events class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class Events extends AbstractApi implements EventInterface
{
    /**
     * Find all events.
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function all()
    {
        throw new BadMethodCallException();
    }

    /**
     * Find an event by its id.
     *
     * @param int|string $id
     * @param array      $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($id, array $options = [])
    {
        if (empty($options)) {
            return $this->gateway->commit(
                'post',
                $this->gateway->buildUrlFromString(''),
                ['METHOD'  => 'GetTransactionDetails', 'TRANSACTIONID' => $id],
                ['partner' => Arr::get($options, 'partner')]
            );
        }

        $test = $this->gateway->getConfig()['test'];

        foreach ($options as $key => $value) {
            $options[$key] = $value == null ? '' : $value;
        }

        $params = array_merge(
            ['cmd' => '_notify-validate'],
            $options
        );

        $url = $test
            ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr'
            : 'https://ipnpb.paypal.com/cgi-bin/webscr';

        return $this->gateway->commit(
            'post',
            $url,
            $params,
            ['partner' => Arr::get($options, 'partner')]
        );
    }
}
