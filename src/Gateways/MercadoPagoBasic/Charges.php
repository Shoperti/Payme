<?php

namespace Shoperti\PayMe\Gateways\MercadoPagoBasic;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Support\Helper;

/**
 * This is the MercadoPagoBasic charges class.
 *
 * @author Arturo Rodríguez <arturo.rodriguez@dinkbit.com>
 */
class Charges extends AbstractApi implements ChargeInterface
{
    /**
     * Create a charge.
     *
     * @param int|float $amount
     * @param mixed     $payment
     * @param string[]  $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($amount, $payment, $options = [])
    {
        $params = [];

        $params = $this->addOrder($params, $amount, $options);
        $params = $this->addPaymentMethod($params, $payment, $options);
        $params = $this->addCustomer($params, $options);
        $params = $this->addAdditionData($params, $options);

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('checkout/preferences'), $params, [
            'isRedirect' => true,
        ]);
    }

    /**
     * Complete a charge.
     *
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function complete($options = [])
    {
        $id = Arr::get($options, 'collection_id');

        $version = $this->gateway->getConfig()['version'];

        return $this->gateway->commit('get', $this->gateway->buildUrlFromString($version.'/payments').'/'.$id);
    }

    /**
     * Refund a charge.
     *
     * @param int|float $amount
     * @param string    $reference
     * @param string[]  $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function refund($amount, $reference, array $options = [])
    {
        $url = sprintf($this->gateway->buildUrlFromString('collections').'/%s/refunds', $reference);

        return $this->gateway->commit('post', $url, [
            'amount' => (float) $this->gateway->amount($amount),
        ]);
    }

    /**
     * Add payment method to request.
     *
     * @param string[] $params
     * @param mixed    $payment
     * @param string[] $options
     *
     * @return array
     */
    protected function addPaymentMethod(array $params, $payment, array $options)
    {
        $params['installments'] = isset($options['monthly_installments']) && in_array($options['monthly_installments'], [3, 6, 9, 12])
            ? (int) Arr::get($options, 'monthly_installments')
            : 1;

        return $params;
    }

    /**
     * Add order params to request.
     *
     * @param string[] $params
     * @param int      $money
     * @param string[] $options
     *
     * @return array
     */
    protected function addOrder(array $params, $money, array $options)
    {
        $params['items'][] = [
            'id'          => Arr::get($options, 'reference'),
            'title'       => Arr::get($options, 'description'),
            'description' => Arr::get($options, 'description'),
            'unit_price'  => (float) $this->gateway->amount($money),
            'quantity'    => 1,
            'currency_id' => Arr::get($options, 'currency'),
        ];

        return array_merge($params, [
            'currency_id'        => Arr::get($options, 'currency'),
            'external_reference' => Arr::get($options, 'reference'),
            'expires'            =>  Arr::get($options, 'expires', false),
        ]);
    }

    /**
     * Add customer to request.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addCustomer(array $params, array $options)
    {
        $params['payer'] = [
            'name'    => Arr::get($billingAddress, 'first_name', ''),
            'surname' => Arr::get($billingAddress, 'last_name', ''),
            'email'   => Arr::get($options, 'email', ''),
        ];

        if ($billingAddress = Arr::get($options, 'billing_address', [])) {
            $params['payer']['address'] = [
                'zip_code'    => Arr::get($billingAddress, 'zip', ''),
                'street_name' => trim(sprintf('%s %s', Arr::get($billingAddress, 'address1', ''), Arr::get($billingAddress, 'address2', ''))),
            ];
        }

        if ($shipping = Arr::get($options, 'shipping_address', [])) {
            $params['shipments']['receiver_address'] = [
                'zip_code'    => Arr::get($shipping, 'zip', ''),
                'street_name' => trim(sprintf('%s %s', Arr::get($shipping, 'address1', ''), Arr::get($shipping, 'address2', ''))),
            ];
        }

        return $params;
    }

    /**
     * Add button code to request.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addAdditionData(array $params, array $options)
    {
        if (array_key_exists('application', $options)) {
            $params['sponsor_id'] = $options['application'];
        }

        if (array_key_exists('notify_url', $options)) {
            $params['notification_url'] = $options['notify_url'];
        }

        $params['auto_return'] = 'all';

        $params['back_urls'] = [
            'success' => Arr::get($options, 'return_url'),
            'failure' => Arr::get($options, 'cancel_url'),
            'pending' => isset($options['pending_url']) 
                ? Arr::get($options, 'pending_url')
                : Arr::get($options, 'return_url'),
        ];

        return $params;
    }
}
