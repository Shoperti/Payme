<?php

namespace Shoperti\PayMe\Gateways\Conekta;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Support\Helper;

/**
 * This is the Conekta charges class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
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
        $params = $this->addPaymentMethod($params, $amount, $payment, $options);
        $params = $this->addOrderDetails($params, $options);

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('orders'), $params);
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
        $params = [];

        if ($amount !== null) {
            $params['amount'] = $this->gateway->amount($amount);
        }

        if (array_key_exists('reason', $options)) {
            $params['reason'] = $options['reason'];
        }

        $url = sprintf($this->gateway->buildUrlFromString('orders').'/%s/refund', $reference);

        return $this->gateway->commit('post', $url, $params);
    }

    /**
     * Add order params to request.
     *
     * @param string[] $params
     * @param int      $amount
     * @param string[] $options
     *
     * @return array
     */
    protected function addOrder(array $params, $amount, array $options)
    {
        $params['metadata'] = ['reference' => Arr::get($options, 'reference')];
        $params['currency'] = Arr::get($options, 'currency', $this->gateway->getCurrency());
        $params['amount'] = (int) $this->gateway->amount($amount);

        return $params;
    }

    /**
     * Add payment method to request.
     *
     * @param string[] $params
     * @param int      $amount
     * @param mixed    $payment
     * @param string[] $options
     *
     * @return array
     */
    protected function addPaymentMethod(array $params, $amount, $payment, array $options)
    {
        if (is_string($payment)) {
            if ($payment == 'spei') {
                $params['charges'][0]['payment_method']['type'] = 'spei';
                $params['charges'][0]['payment_method']['expires_at'] = Arr::get($options, 'expires', strtotime(date('Y-m-d H:i:s')) + 172800);
            } elseif ($payment == 'oxxo') {
                $params['charges'][0]['payment_method']['type'] = 'oxxo';
                $params['charges'][0]['payment_method']['expires_at'] = Arr::get($options, 'expires', strtotime(date('Y-m-d H:i:s')) + 172800);
            } elseif ($payment == 'oxxo_cash') {
                $params['charges'][0]['payment_method']['type'] = 'oxxo_cash';
                $params['charges'][0]['payment_method']['expires_at'] = Arr::get($options, 'expires', strtotime(date('Y-m-d H:i:s')) + 172800);
            } elseif (Helper::startsWith($payment, 'cus_')) {
                $params['customer_info']['customer_id'] = $payment;
                $params['charges'][0]['payment_method']['type'] = 'default';
            } elseif (Helper::startsWith($payment, 'payee_')) {
                $params['charges'][0]['payment_method']['type'] = 'payout';
                $params['charges'][0]['payment_method']['payee_id'] = $payment;
            } else {
                $params['charges'][0]['payment_method']['type'] = 'card';
                $params['charges'][0]['payment_method']['token_id'] = $payment;
            }
        } elseif ($payment instanceof CreditCard) {
            $params['charges'][0]['payment_method']['card'] = [];
            $params['charges'][0]['payment_method']['card']['name'] = $payment->getName();
            $params['charges'][0]['payment_method']['card']['cvc'] = $payment->getCvv();
            $params['charges'][0]['payment_method']['card']['number'] = $payment->getNumber();
            $params['charges'][0]['payment_method']['card']['exp_month'] = $payment->getExpiryMonth();
            $params['charges'][0]['payment_method']['card']['exp_year'] = $payment->getExpiryYear();
            $params['charges'][0]['payment_method']['card'] = $this->addAddress($params['charges'][0]['source']['card'], $options);
        }

        if (isset($options['monthly_installments']) && in_array($options['monthly_installments'], [3, 6, 9, 12])) {
            $params['charges'][0]['payment_method']['monthly_installments'] = (int) $options['monthly_installments'];
        }

        $params['charges'][0]['amount'] = (int) $this->gateway->amount($amount);

        return $params;
    }

    /**
     * Add address to request.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array|null
     */
    protected function addAddress(array $params, array $options)
    {
        if ($address = Arr::get($options, 'address') ?: Arr::get($options, 'billing_address')) {
            $params['address']['street1'] = Arr::get($address, 'address1');
            if ($address2 = Arr::get($address, 'address2')) {
                $params['address']['street2'] = $address2;
            }
            if ($address3 = Arr::get($address, 'address3')) {
                $params['address']['street3'] = $address3;
            }
            if ($externalNumber = Arr::get($address, 'external_number')) {
                $params['address']['external_number'] = $externalNumber;
            }
            $params['address']['city'] = Arr::get($address, 'city');
            $params['address']['country'] = Arr::get($address, 'country');
            $params['address']['state'] = Arr::get($address, 'state');
            $params['address']['postal_code'] = Arr::get($address, 'zip');

            return $params;
        }
    }

    /**
     * Add order details params.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addOrderDetails(array $params, array $options)
    {
        if (isset($options['name'])) {
            $params['customer_info']['name'] = Arr::get($options, 'name', '');
        }

        if (isset($options['email'])) {
            $params['customer_info']['email'] = Arr::get($options, 'email', '');
        }

        if (isset($options['phone'])) {
            $params['customer_info']['phone'] = Arr::get($options, 'phone', '');
        }

        $params = $this->addLineItems($params, $options);
        $params = $this->addDiscountLines($params, $options);
        $params = $this->addBillingAddress($params, $options);
        $params = $this->addShippingAddress($params, $options);

        return $params;
    }

    /**
     * Add order line items param.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addLineItems(array $params, array $options)
    {
        if (isset($options['line_items']) && is_array($options['line_items'])) {
            foreach ($options['line_items'] as $lineItem) {
                $params['line_items'][] = [
                    'name'        => Arr::get($lineItem, 'name'),
                    'description' => Arr::get($lineItem, 'description'),
                    'unit_price'  => (int) $this->gateway->amount(Arr::get($lineItem, 'unit_price')),
                    'quantity'    => Arr::get($lineItem, 'quantity', 1),
                    'sku'         => Arr::get($lineItem, 'sku'),
                    'category'    => Arr::get($lineItem, 'category'),
                    'type'        => Arr::get($lineItem, 'type', 'physical'),
                    'tags'        => ['none'],
                ];
            }
        }

        return $params;
    }

    /**
     * Add order discount lines param.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addDiscountLines(array $params, array $options)
    {
        if (isset($options['discount'])) {
            $type = Arr::get($options, 'discount_type');
            if (!in_array($type, ['loyalty', 'campaign', 'coupon', 'sign'])) {
                $type = 'loyalty';
            }

            $code = Arr::get($options, 'discount_code', '---');
            if (strlen($code) < 3) {
                $code .= str_repeat('-', 3 - strlen($code));
            }

            $params['discount_lines'][] = [
                'type'   => $type,
                'code'   => $code,
                'amount' => $options['discount'],
            ];
        }

        return $params;
    }

    /**
     * Add Billing address to request.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addBillingAddress(array $params, array $options)
    {
        if ($address = Arr::get($options, 'billing_address') && $taxId = Arr::get($address, 'tax_id') && $companyName = Arr::get($address, 'company_name')) {
            $addressFiltered = Arr::filters([
                'street1'         => Arr::get($address, 'address1'),
                'street2'         => Arr::get($address, 'address2'),
                'street3'         => Arr::get($address, 'address3'),
                'external_number' => Arr::get($address, 'external_number'),
                'city'            => Arr::get($address, 'city'),
                'country'         => Arr::get($address, 'country'),
                'state'           => Arr::get($address, 'state'),
                'postal_code'     => Arr::get($address, 'zip'),
            ]);

            if (!empty($addressFiltered)) {
                $params['fiscal_entity']['address'] = $addressFiltered;
            }

            $params['fiscal_entity']['phone'] = Arr::get($address, 'phone', Arr::get($options, 'phone', 'none'));
            $params['fiscal_entity']['email'] = Arr::get($address, 'email', Arr::get($options, 'email', 'none'));
            $params['fiscal_entity']['tax_id'] = $taxId;
            $params['fiscal_entity']['company_name'] = $companyName;
        }

        return $params;
    }

    /**
     * Add Shipping address to request.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addShippingAddress(array $params, array $options)
    {
        if ($address = Arr::get($options, 'shipping_address')) {
            $addressFiltered = Arr::filters([
                'street1'         => Arr::get($address, 'address1'),
                'street2'         => Arr::get($address, 'address2'),
                'street3'         => Arr::get($address, 'address3'),
                'external_number' => Arr::get($address, 'external_number'),
                'city'            => Arr::get($address, 'city'),
                'country'         => Arr::get($address, 'country'),
                'state'           => Arr::get($address, 'state'),
                'postal_code'     => Arr::get($address, 'zip'),
            ]);

            if (!empty($addressFiltered)) {
                $params['shipping_contact']['address'] = $addressFiltered;
            }

            $params['shipping_contact']['receiver'] = Arr::get($address, 'name', Arr::get($options, 'name', ''));
            $params['shipping_contact']['phone'] = Arr::get($address, 'phone', Arr::get($options, 'phone', ''));
            $params['shipping_contact']['email'] = Arr::get($address, 'email', Arr::get($options, 'email', ''));

            $params['shipping_lines'] = [];
            $params['shipping_lines'][0]['description'] = Arr::get($address, 'carrier');
            $params['shipping_lines'][0]['carrier'] = Arr::get($address, 'carrier');
            $params['shipping_lines'][0]['method'] = Arr::get($address, 'service');
            $params['shipping_lines'][0]['amount'] = (int) $this->gateway->amount(Arr::get($address, 'price'));

            if ($trackingNumber = Arr::get($address, 'tracking_number')) {
                $params['shipping_lines'][0]['tracking_number'] = $trackingNumber;
            }
        }

        return $params;
    }
}
