<?php

namespace Shoperti\PayMe\Gateways\SrPago;

use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Gateways\AbstractApi;

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

        $params = $this->addPayment($params, $amount, $payment, $options);
        $params = Encryption::encryptParametersWithString($params);
        print_r(json_encode($params));
        die();
        $params = $this->addMetadata($params, $amount, $options);

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
    protected function addPayment($params, $amount,  $payment, $options)
    {
        return array_merge($params, [
            'payment' => [
                'external' => [
                    'transaction' => Arr::get($options, 'reference'),
                    'application_key' => $this->gateway->getApplicationKey(),
                ],
                'reference' => [
                    'number'      => Arr::get($options, 'reference'),
                    'description' => Arr::get($options, 'descriptions'),
                ],
                'total' => [
                    'amount'   => $this->gateway->amount($amount),
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
            ],
            'recurrent' => $payment,
            'total'     => [
                'amount' => $this->gateway->amount($amount)
            ],
        ]);
    }

    protected function addMetadata($params, $amount, $options)
    {
        $billing  = Arr::get($options, 'billing_address');
        $shipping = Arr::get($options, 'shipping_address');

        return array_merge($params, [
            'metadata' => [
                'salesTax' => '',
                'browserCookie' => '',
                'orderMessage' => Arr::get($options, 'orderMessage'),
                'billing'      => [
                    'billingEmailAddress'  => Arr::get($billing, 'email', ''),
                    'billingFirstName-D'   => Arr::get($billing, 'first_name', ''),
                    'billingMiddleName-D'  => Arr::get($billing, 'middle_name', ''),  
                    'billingLastName-D'    => Arr::get($billing, 'last_name', ''),
                    'billingAddress-D'     => Arr::get($billing, 'address1', ''),
                    'billingAddress2-D'    => Arr::get($billing, 'address2', ''),
                    'billingPhoneNumber-D' => Arr::get($billing, 'phone'),
                ],
                'shipping'      => [
                    'billingEmailAddress'  => Arr::get($shipping, 'email', ''),
                    'billingFirstName-D'   => Arr::get($shipping, 'first_name', ''),
                    'billingMiddleName-D'  => Arr::get($shipping, 'middle_name', ''),  
                    'billingLastName-D'    => Arr::get($shipping, 'last_name', ''),
                    'billingAddress-D'     => Arr::get($shipping, 'address1', ''),
                    'billingAddress2-D'    => Arr::get($shipping, 'address2', ''),
                    'billingPhoneNumber-D' => Arr::get($shipping, 'phone'),
                ],
                'items' => [
                    'item' => $this->addItems($params, $options),
                ]
            ],
        ]);
    }

    /**
     * Add items array param
     *
     * @param array $options
     * @return array
     */
    protected function addItems($params, $options)
    {
        $items = [];

        if (isset($options['line_items']) && is_array($options['line_items'])) {
            foreach ($options['line_items'] as $item) {
                $items[] = [
                    'itemNumber'          => Arr::get($item, 'sku'),
                    'itemDescription'     => Arr::get($item, 'description'),
                    'itemPrice'           => $this->gateway->amount(Arr::get($item, 'unit_price')),
                    'itemQuantity'        => (string) Arr::get($item, 'quantity', 1),
                    'itemMeasurementUnit' => Arr::get($item, 'measurementUnit'),
                    'itemBrandName'       => Arr::get($item, 'brandName'),
                    'itemCategory'        => Arr::get($item, 'category'),
                    'itemTax'             => Arr::get($item, 'tax'),
                ];
            }
        }

        return $items;
    }
}