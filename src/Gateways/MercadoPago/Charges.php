<?php

namespace Shoperti\PayMe\Gateways\MercadoPago;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Support\Helper;

/**
 * This is the MercadoPago charges class.
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
        $params = $this->addAdditionData($params, $options);

        $params['binary_mode'] = true;

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('payments'), $params);
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
        $url = sprintf($this->gateway->buildUrlFromString('payments').'/%s/refunds', $reference);

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
        if ($card = Arr::get($options, 'card')) {
            $params['payment_method_id'] = Arr::get($card, 'brand');
            $params['token'] = $payment;
            $params['installments'] = isset($options['monthly_installments']) && ctype_digit((string) $options['monthly_installments'])
                ? (int) Arr::get($options, 'monthly_installments')
                : 1;
        } else {
            $params['payment_method_id'] = $payment;

            if (isset($options['days_to_expire']) && ctype_digit((string) $options['days_to_expire'])) {
                $daysToExpire = $options['days_to_expire'];
                $expirationDate = date('Y-m-d', strtotime("+{$daysToExpire} days")).'T00:00:00.000-00:00';

                $params['date_of_expiration'] = $expirationDate;
            }
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
        $params['transaction_amount'] = (float) $this->gateway->amount($money);
        $params['external_reference'] = Arr::get($options, 'reference');

        if (isset($options['description'])) {
            $params['statement_descriptor'] = Helper::ascii(Arr::get($options, 'description', 'PayMe Purchase'));
        }

        return $params;
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
        $params['payer']['email'] = Arr::get($options, 'email', '');

        $params['additional_info']['payer'] = [
            'first_name' => Arr::get($options, 'first_name', ''),
            'last_name'  => Arr::get($options, 'last_name', ''),
        ];

        if ($billingAddress = Arr::get($options, 'billing_address', [])) {
            $params['additional_info']['payer']['address'] = [
                'zip_code'    => Arr::get($billingAddress, 'zip', ''),
                'street_name' => trim(sprintf('%s %s', Arr::get($billingAddress, 'address1', ''), Arr::get($billingAddress, 'address2', ''))),
            ];
        }

        if ($shipping = Arr::get($options, 'shipping_address', [])) {
            $params['additional_info']['shipments']['receiver_address'] = [
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
        if (isset($options['application']) && ctype_digit((string) $options['application'])) {
            $params['sponsor_id'] = (int) $options['application'];
        }

        if (isset($options['notify_url'])) {
            $params['notification_url'] = $options['notify_url'];
        }

        return $params;
    }
}
