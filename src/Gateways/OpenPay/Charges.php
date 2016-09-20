<?php

namespace Shoperti\PayMe\Gateways\OpenPay;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Support\Helper;

/**
 * This is the OpenPay charges class.
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

        $params = $this->addPaymentMethod($params, $payment, $options);
        $params = $this->addOrder($params, $amount, $options);
        $params = $this->addCustomer($params, $options);

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('charges'), $params);
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
        $url = sprintf($this->gateway->buildUrlFromString('charges').'/%s/refund', $reference);

        return $this->gateway->commit('post', $url, [
            'amount'      => $this->gateway->amount($amount),
            'description' => Arr::get($options, 'description'),
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
        if (is_string($payment)) {
            // offline & SPEI
            if (in_array($payment, ['store', 'bank_account'])) {
                return array_merge($params, [
                    'method' => $payment,
                ]);
            }

            // cards
            $paymentData = [
                'method'            => 'card',
                'source_id'         => $payment,
                'device_session_id' => Arr::get($options, 'device_id'),
            ];

            if ($installments = Arr::get($options, 'monthly_installments')) {
                if (is_numeric($installments) && in_array($installments, [3, 6, 9, 12])) {
                    $paymentData['payment_plan'] = ['payments' => (int) $installments];
                }
            }

            return array_merge($params, $paymentData);
        }

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
        return array_merge($params, [
            'amount'      => $this->gateway->amount($money),
            'currency'    => Arr::get($options, 'currency', $this->gateway->getCurrency()),
            'description' => Helper::ascii(Arr::get($options, 'description', 'PayMe Purchase')),
            'order_id'    => Arr::get($options, 'reference'),
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
        $address = Arr::get($options, 'shipping_address', []);

        $params['customer'] = [
            'name'             => Arr::get($options, 'first_name', ''),
            'last_name'        => Arr::get($options, 'last_name', ''),
            'phone_number'     => Arr::get($options, 'phone', ''),
            'email'            => Arr::get($options, 'email', ''),
            'requires_account' => false,
            'address'          => [
                'city'         => Arr::get($address, 'city', ''),
                'state'        => Arr::get($address, 'state', ''),
                'line1'        => Arr::get($address, 'address1', ''),
                'postal_code'  => Arr::get($address, 'zip', ''),
                'line2'        => Arr::get($address, 'address2', ''),
                'line3'        => '',
                'country_code' => Arr::get($address, 'country', ''),
            ],
        ];

        return $params;
    }
}
