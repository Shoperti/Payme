<?php

namespace PayMe\Gateways\SrPago;

use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Support\Arr;

class Charges implements ChargeInterface
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

        $params = $this->addPayment($params, $amount, $options);
        $params = $this->addMetadata($params, $amount, $options);

        print_r($params);
        die();

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('payment/card'), $params);
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

    }

    /**
     * Undocumented function
     *
     * @param [type] $params
     * @param [type] $amount
     * @param [type] $options
     * @return void
     */
    protected function addPayment($params, $amount, $options)
    {
        return array_merge($params, [
            'external' => [
                'transaction' => '',
                'application_key' => $this->gateway->getApplicationKey(),
            ],
            'reference' => [
                'number' => Arr::get($options, 'reference'),
                'description' => '',
            ],
            'total' => [
                'amount' => $this->gateway->amount($amount),
                'currency' => Arr::get($options, 'currency', $this->gateway->getCurrency()),
            ],
            'origin' => [
                "device" => "A007-23",
                "ip" => "187.188.104.19",
                "location" => [
                    "latitude" => "19.003408",
                    "longitude" =>  "-98.267280"
                ],
            ],
        ]);
    }

    protected function addMetadata($params, $amount, $options)
    {
        return array_merge($params, [
            'salesTax' => '',
            'browserCookie' => '',
            'orderMessage' => Arr::get($options, 'orderMessage'),
            'billing'      => [
                'billingEmailAddress'  => Arr::get($options, 'email', ''),
                'billingFirstName-D'   => Arr::get($options, 'first_name', ''),
                'billingMiddleName-D'  => Arr::get($options, 'middle_name', ''),  
                'billingLastName-D'    => Arr::get($options, 'last_name', ''),
                'billingAddress-D'     => Arr::get($options, 'billing_address1', ''),
                'billingAddress2-D'    => Arr::get($options, 'billing_address2', ''),
                'billingPhoneNumber-D' => Arr::get($options, 'phone'),
            ],
            'items' => $this->addItems($options),
        ]);
    }

    /**
     * Add items array param
     *
     * @param array $options
     * @return array
     */
    protected function addItems($options)
    {
        if (isset($options['items']) && is_array($options['items'])) {
            foreach ($options['items'] as $item) {
                $items['item'][] = [
                    'itemNumber'          => Arr::get($item, 'number'),
                    'itemDescription'     => Arr::get($item, 'description'),
                    'itemPrice'           => (int) $this->gateway->amount(Arr::get($item, 'price')),
                    'itemQuantity'        => Arr::get($item, 'quantity', 1),
                    'itemMeasurementUnit' => Arr::get($item, 'measurementUnit'),
                    'itemBrandName'       => Arr::get($item, 'brandName'),
                    'itemCategory'        => Arr::get($item, 'category'),
                    'itemTax'             => Arr::get($item, 'tax'),
                ];
            }
        }

        return $params;
    }
}