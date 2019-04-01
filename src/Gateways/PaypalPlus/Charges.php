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
        $payload = [
            'intent'              => 'sale',
            'application_context' => [],
            'payer'               => [
                'payment_method' => 'paypal',
                'payer_info'     => [],
            ],
            'transactions' => [
                [
                    'amount' => [
                        'currency' => Arr::get($options, 'currency', $this->gateway->getCurrency()),
                        'total'    => $this->gateway->amount($amount),
                        'details'  => [],
                    ],
                    'description'     => Arr::get($options, 'description', 'PayMe Purchase'),
                    'payment_options' => ['allowed_payment_method' => 'IMMEDIATE_PAY'],
                    'invoice_number'  => Arr::get($options, 'reference', ''),
                    'item_list'       => [],
                ],
            ],
            'redirect_urls' => [
                'cancel_url' => Arr::get($options, 'cancel_url', ''),
                'return_url' => Arr::get($options, 'return_url', ''),
            ],
        ];

        $payload = $this->addLineItems($payload, $options);
        $payload = $this->addBilling($payload, $options);
        $payload = $this->addShipping($payload, $options);
        $payload = $this->addDiscount($payload, $options);

        return $this->gateway->commit(
            'post',
            $this->gateway->buildUrlFromString('payments/payment'),
            $payload, $options
        );
    }

    /**
     * Add order line items params.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addLineItems(array $params, array $options)
    {
        $currency = Arr::get($options, 'currency', $this->gateway->getCurrency());

        $items = [];
        $itemsTotal = 0;
        foreach (Arr::get($options, 'line_items', []) as $item) {
            $items[] = [
                'name'        => $item['name'],
                'description' => Arr::get($item, 'description'),
                'quantity'    => $item['quantity'],
                'price'       => $this->gateway->amount($item['unit_price']),
                'sku'         => Arr::get($item, 'sku'),
                'currency'    => $currency,
            ];
            $itemsTotal += $item['unit_price'] * $item['quantity'];
        }

        if (!empty($items)) {
            $params['transactions'][0]['item_list']['items'] = $items;
            $params['transactions'][0]['amount']['details']['subtotal'] = $this->gateway->amount($itemsTotal);
        }

        return $params;
    }

    /**
     * Add billing params.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addBilling(array $params, array $options)
    {
        if ($address = Arr::get($options, 'billing_address')) {
            $params['payer']['payer_info']['billing_address'] = [
                'line1'        => Arr::get($address, 'address1', ''),
                'line2'        => Arr::get($address, 'address2', ''),
                'city'         => Arr::get($address, 'city', ''),
                'country_code' => Arr::get($address, 'country', ''),
                'postal_code'  => Arr::get($address, 'zip', ''),
                'state'        => Arr::get($address, 'state', ''),
            ];
        }

        return $params;
    }

    /**
     * Add shipping params.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addShipping(array $params, array $options)
    {
        if ($address = Arr::get($options, 'shipping_address')) {
            $params['application_context']['shipping_preference'] = 'SET_PROVIDED_ADDRESS';
            $params['transactions'][0]['amount']['details']['shipping'] = $this->gateway
                ->amount(Arr::get($address, 'price', 0));

            $params['transactions'][0]['item_list']['shipping_address'] = [
                'recipient_name' => trim(Arr::get($options, 'name')),
                'line1'          => Arr::get($address, 'address1', ''),
                'line2'          => Arr::get($address, 'address2', ''),
                'city'           => Arr::get($address, 'city', ''),
                'country_code'   => Arr::get($address, 'country', ''),
                'postal_code'    => Arr::get($address, 'zip', ''),
                'state'          => Arr::get($address, 'state', ''),
                'phone'          => Arr::get($options, 'phone', ''),
            ];
        } else {
            $params['application_context']['shipping_preference'] = 'NO_SHIPPING';
        }

        return $params;
    }

    /**
     * Add discount params.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addDiscount(array $params, array $options)
    {
        $discount = (int) Arr::get($options, 'discount');

        if ($discount > 0) {
            $itemsTotal = $params['transactions'][0]['amount']['details']['subtotal'] * 100;
            $shipping = Arr::get($options, 'shipping_address', []);
            $shipping = Arr::get($shipping, 'price');

            $discountType = Arr::get($options, 'discount_type');
            if ($discountType === 'shipping' && $shipping > 0) {
                $shipping -= $discount;
                if ($shipping < 0) {
                    $itemsTotal += $shipping;
                    $shipping = 0;
                }
                $params['transactions'][0]['amount']['details']['shipping'] = $this->gateway->amount($shipping);
            } else {
                $itemsTotal -= $discount;
            }

            $params['transactions'][0]['amount']['details']['subtotal'] = $this->gateway->amount($itemsTotal);

            $params['transactions'][0]['item_list']['items'][] = [
                'name'        => Arr::get($options, 'discount_concept', 'Discount'),
                'description' => $options['discount_type'],
                'price'       => '-'.$this->gateway->amount($discount),
                'currency'    => Arr::get($options, 'currency', $this->gateway->getCurrency()),
                'sku'         => Arr::get($options, 'discount_code'),
                'quantity'    => 1,
            ];
        }

        return $params;
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
