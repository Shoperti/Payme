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
        return;
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
        return;
    }

    /**
     * Add payment array param
     *
     * @param array $params
     * @param float $amount
     * @param array $options
     * @return array
     */
    protected function addPayment($params, $amount,  $payment, $options)
    {
        return array_merge($params, [
            'payment' => [
                'external' => [
                    'transaction'     => Arr::get($options, 'reference'),
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
                'origin'    => [
                    'ip'    => Arr::get($options, 'ip_address', '127.0.0.1'),
                    'location' => [
                        'latitude'  => Arr::get($options, 'latitude', '0.00000'),
                        'longitude' => Arr::get($options, 'longitude', '0.00000'),
                    ]
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
                    'billingFirstName-D'   => Arr::get($options, 'first_name', ''),
                    'billingMiddleName-D'  => Arr::get($options, 'middle_name', ''),  
                    'billingLastName-D'    => Arr::get($options, 'last_name', ''),
                    'billingAddress-D'     => Arr::get($billing, 'address1', ''),
                    'billingAddress2-D'    => Arr::get($billing, 'address2', ''),
                    'billingPhoneNumber-D' => Arr::get($options, 'phone'),
                ],
                'shipping'      => [
                    'shippingFirstName-D'   => Arr::get($options, 'first_name', ''),
                    'shippingLastName-D'    => Arr::get($options, 'last_name', ''),
                    'shippingEmailAddress'  => Arr::get($options, 'email', ''),
                    'shippingAddress'       => Arr::get($shipping, 'address1', ''),
                    'shippingAddress2'      => Arr::get($shipping, 'address2', ''),
                    'shippingCity'          => Arr::get($shipping, 'city', ''),
                    'shippingState'         => Arr::get($shipping, 'state', ''),
                    'shippingPostalCode'    => Arr::get($shipping, 'zip', ''),
                    'shippingCountry'       => Arr::get($shipping, 'country', ' '),
                    'shippingPhoneNumber'   => Arr::get($options, 'phone'),
                ],
                'member' => [
                    'memberLoggedIn'        => 'Si',
                    'memberId'              => Arr::get($options, 'user_id', ''),  
                    'memberFullName'        => Arr::get($options, 'first_name', '').' '.Arr::get($options, 'last_name', ''),
                    'memberFirstName'       => Arr::get($options, 'first_name', ''),
                    'memberMiddleName'      => Arr::get($options, 'middle_name', ''),
                    'memberLastName'        => Arr::get($options, 'last_name', ''),
                    'memberEmailAddress'    => Arr::get($options, 'email', ''),
                    'memberAddressLine1'    => Arr::get($shipping, 'address1', ''),
                    'memberAddressLine2'    => Arr::get($shipping, 'address2', ''),
                    'memberCity'            => Arr::get($shipping, 'city', ''),
                    'memberState'           => Arr::get($shipping, 'state', ''),
                    'memberCountry'         => Arr::get($shipping, 'country', ''),
                    'memberPostalCode'      => Arr::get($shipping, 'zip', ''),
                    'latitude'              => Arr::get($options, 'latitude', '0.00000'),
                    'longitude'             => Arr::get($options, 'latitude', '0.00000'),
                    'memberPhone'           => Arr::get($options, 'phone'),
                ],
                'items' => [
                    'item' => $this->addItems($params, $options),
                ],
              
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
                    'itemBrandName'       => Arr::get($item, 'brandName'),
                ];
            }
        }

        return $items;
    }
}