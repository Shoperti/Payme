<?php

namespace Shoperti\PayMe\Gateways\SrPago;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;

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
     * Get a charge.
     *
     * @param string $id
     * @param array  $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function get($id, $options = [])
    {
        throw new BadMethodCallException();
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
        throw new BadMethodCallException();
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
        throw new BadMethodCallException();
    }

    /**
     * Add payment array params.
     *
     * @param array $params
     * @param float $amount
     * @param mixed $payment
     * @param array $options
     *
     * @return array
     */
    protected function addPayment($params, $amount, $payment, $options)
    {
        return array_merge($params, [
            'payment' => [
                'external' => [
                    'transaction'     => Arr::get($options, 'reference'),
                    'application_key' => $this->gateway->getApplicationKey(),
                ],
                'reference' => [
                    'number'      => Arr::get($options, 'reference'),
                    'description' => Arr::get($options, 'description'),
                ],
                'total' => [
                    'amount'   => $this->gateway->amount($amount),
                    'currency' => Arr::get($options, 'currency', $this->gateway->getCurrency()),
                ],
                'origin'    => [
                    'ip'       => Arr::get($options, 'ip', ''),
                    'location' => [
                        'latitude'  => Arr::get($options, 'latitude', '0.00000'),
                        'longitude' => Arr::get($options, 'longitude', '0.00000'),
                    ],
                ],
            ],
            'recurrent' => $payment,
            'total'     => [
                'amount' => $this->gateway->amount($amount),
            ],
        ]);
    }

    /**
     * Add metadata array params.
     *
     * @param array $params
     * @param float $amount
     * @param array $options
     *
     * @return array
     */
    protected function addMetadata($params, $amount, $options)
    {
        $billing = Arr::get($options, 'billing_address');
        $shipping = Arr::get($options, 'shipping_address');

        return array_merge($params, [
            'metadata' => [
                'salesTax'      => Arr::get($options, 'sales_tax', ''),
                'browserCookie' => Arr::get($options, 'browser_cookie', ''),
                'orderMessage'  => Arr::get($options, 'order_message'),
                'billing'       => [
                    'billingEmailAddress'  => Arr::get($billing, 'email', ''),
                    'billingFirstName-D'   => Arr::get($options, 'first_name', ''),
                    'billingMiddleName-D'  => Arr::get($options, 'middle_name', ''),
                    'billingLastName-D'    => Arr::get($options, 'last_name', ''),
                    'billingAddress-D'     => Arr::get($billing, 'address1', ''),
                    'billingAddress2-D'    => Arr::get($billing, 'address2', ''),
                    'billingPhoneNumber-D' => Arr::get($options, 'phone'),
                    'billingCity-D'        => Arr::get($billing, 'city', ''),
                    'billingState-D'       => Arr::get($billing, 'state', ''),
                    'billingPostalCode-D'  => Arr::get($billing, 'zip', ''),
                    'billingCountry-D'     => Arr::get($billing, 'country', ''),
                ],
                'shipping'      => [
                    'shippingFirstName'     => Arr::get($options, 'first_name', ''),
                    'shippingMiddleName'    => Arr::get($options, 'middle_name', ''),
                    'shippingLastName'      => Arr::get($options, 'last_name', ''),
                    'shippingEmailAddress'  => Arr::get($options, 'email', ''),
                    'shippingAddress'       => Arr::get($shipping, 'address1', ''),
                    'shippingAddress2'      => Arr::get($shipping, 'address2', ''),
                    'shippingCity'          => Arr::get($shipping, 'city', ''),
                    'shippingState'         => Arr::get($shipping, 'state', ''),
                    'shippingPostalCode'    => Arr::get($shipping, 'zip', ''),
                    'shippingCountry'       => Arr::get($shipping, 'country', ''),
                    'shippingPhoneNumber'   => Arr::get($options, 'phone'),
                ],
                'member' => [
                    'memberLoggedIn'        => Arr::get($options, 'logged_id', 'No'),
                    'memberId'              => Arr::get($options, 'user_id'),
                    'memberFullName'        => trim(
                        Arr::get($options, 'first_name', '').' '.
                                                (empty($options['middle_name']) ? '' : Arr::get($options, 'middle_name').' ').
                                                Arr::get($options, 'last_name', '')
                    ),
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
                    'item' => $this->addItems($options),
                ],
            ],
        ]);
    }

    /**
     * Add items array param.
     *
     * @param array $options
     *
     * @return array
     */
    protected function addItems($options)
    {
        $items = [];

        if (isset($options['line_items']) && is_array($options['line_items'])) {
            foreach ($options['line_items'] as $item) {
                $items[] = [
                    'itemNumber'      => Arr::get($item, 'sku'),
                    'itemDescription' => Arr::get($item, 'description'),
                    'itemPrice'       => $this->gateway->amount(Arr::get($item, 'unit_price')),
                    'itemQuantity'    => (string) Arr::get($item, 'quantity', 1),
                    'itemBrandName'   => Arr::get($item, 'brand_name', ''),
                ];
            }
        }

        return $items;
    }
}
