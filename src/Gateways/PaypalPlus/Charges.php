<?php

namespace Shoperti\PayMe\Gateways\PaypalPlus;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the Paypal Plus charges class.
 *
 * @property \Shoperti\PayMe\Gateways\PaypalPlus\PaypalPlusGateway $gateway
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
        $shippingAddress = Arr::get($options, 'shipping_address', []);
        $billingAddress = Arr::get($options, 'billing_address', []);
        $currency = Arr::get($options, 'currency', $this->gateway->getCurrency());

        $items = [];
        $itemsTotal = 0;
        foreach (Arr::get($options, 'line_items', []) as $item) {
            $items[] = [
                'name'        => $item['name'],
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'price'       => $this->gateway->amount($item['unit_price']),
                'sku'         => $item['sku'],
                'currency'    => $currency,
            ];
            $itemsTotal += $item['unit_price'] * $item['quantity'];
        }

        $discount = (int) Arr::get($options, 'discount');
        $discountType = $discount > 0 ? $options['discount_type'] : null;
        $shipping = Arr::get($shippingAddress, 'price');
        $hasShipping = $shipping > 0 || $discountType === 'shipping';

        if ($discount > 0) {
            if ($options['discount_type'] === 'shipping') {
                $shipping -= $discount;
                if ($shipping < 0) {
                    $itemsTotal += $shipping;
                    $shipping = 0;
                }
            } else {
                $itemsTotal -= $discount;
            }

            $items[] = [
                'name'        => Arr::get($options, 'discount_concept', 'Discount'),
                'description' => $options['discount_type'],
                'price'       => '-'.$this->gateway->amount($discount),
                'currency'    => $currency,
                'sku'         => $options['discount_code'],
                'quantity'    => 1,
            ];
        }

        $payload = [
            'intent'              => 'sale',
            'application_context' => [
                'shipping_preference' => $hasShipping ? 'SET_PROVIDED_ADDRESS' : 'NO_SHIPPING',
            ],
            'payer' => [
                'payment_method' => 'paypal',
                'payer_info'     => [
                    'billing_address' => [
                        'line1'        => Arr::get($billingAddress, 'address1', ''),
                        'line2'        => Arr::get($billingAddress, 'address2', ''),
                        'city'         => Arr::get($billingAddress, 'city', ''),
                        'country_code' => Arr::get($billingAddress, 'country', ''),
                        'postal_code'  => Arr::get($billingAddress, 'zip', ''),
                        'state'        => Arr::get($billingAddress, 'state', ''),
                    ],
                ],
            ],
            'transactions' => [
                [
                    'amount' => [
                        'currency' => $currency,
                        'total'    => $this->gateway->amount($amount),
                        'details'  => [
                            'subtotal' => $this->gateway->amount($itemsTotal),
                            'shipping' => $this->gateway->amount($shipping),
                        ],
                    ],
                    'description'     => Arr::get($options, 'description', ''),
                    'payment_options' => ['allowed_payment_method' => 'IMMEDIATE_PAY'],
                    'invoice_number'  => Arr::get($options, 'reference', ''),
                    'item_list'       => [
                        'items'            => $items,
                        'shipping_address' => [
                            'recipient_name' => trim(
                                Arr::get($options, 'first_name', '').' '.Arr::get($options, 'last_name', '')
                            ),
                            'line1'          => Arr::get($shippingAddress, 'address1', ''),
                            'line2'          => Arr::get($shippingAddress, 'address2', ''),
                            'city'           => Arr::get($shippingAddress, 'city', ''),
                            'country_code'   => Arr::get($shippingAddress, 'country', ''),
                            'postal_code'    => Arr::get($shippingAddress, 'zip', ''),
                            'state'          => Arr::get($shippingAddress, 'state', ''),
                            'phone'          => Arr::get($options, 'phone', ''),
                        ],
                    ],
                ],
            ],
            'redirect_urls' => [
                'cancel_url' => Arr::get($options, 'cancel_url', ''),
                'return_url' => Arr::get($options, 'return_url', ''),
            ],
        ];

        return $this->gateway->commit(
            'post',
            $this->gateway->buildUrlFromString('payments/payment'),
            $payload,
            [
                'token'        => Arr::get($options, 'token'),
                'continue_url' => Arr::get($options, 'continue_url'),
            ]
        );
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
        return $this->gateway->commit(
            'post',
            $this->gateway->buildUrlFromString(sprintf('payments/payment/%s/execute', $options['payment'])),
            ['payer_id' => Arr::get($options, 'payer_id')],
            ['token'    => Arr::get($options, 'token')]
        );
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
}
