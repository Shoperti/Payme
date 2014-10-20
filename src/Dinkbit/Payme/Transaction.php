<?php namespace Dinkbit\Payme;

use ArrayAccess;

class Transaction implements ArrayAccess, Contracts\Transaction {

	public $success;
	public $isRedirect;
	public $test;
	public $status;
	public $authorization;
	public $message;
	public $code;
	public $reference;
	public $transaction;

	/**
	 * Is the transaction successful?
	 *
	 * @return boolean
	 */
	public function success()
	{
		return (bool) $this->success;
	}

	/**
	 * Does the transaction require a redirect?
	 *
	 * @return boolean
	 */
	public function isRedirect()
	{
		return (bool) $this->isRedirect;
	}

	/**
	 * Return transaction status.
	 *
	 * @return string
	 */
	public function test()
	{
		return (bool) $this->test;
	}

	/**
	 * Return authorization code
	 *
	 * @return mixed
	 */
	public function authorization()
	{
		return $this->authorization;
	}

	/**
	 * Response Message
	 *
	 * @return string A response message from the payment gateway
	 */
	public function message()
	{
		return $this->message;
	}

	/**
	 * Transaction code
	 *
	 * @return string A response code from the payment gateway
	 */
	public function code()
	{
		return $this->code;
	}

	/**
	 * Return transaction status.
	 *
	 * @return string
	 */
	public function status()
	{
		return (string) $this->status;
	}

	/**
	 * Gateway Reference
	 *
	 * @return string A reference provided by the gateway to represent this transaction
	 */
	public function reference()
	{
		return $this->reference;
	}

	/**
	 * Gateway raw data
	 *
	 * @return array
	 */
	public function raw()
	{
		return $this->transaction;
	}

	/**
	 * Set the raw transaction array from the gateway.
	 *
	 * @param  array  $transaction
	 * @return $this
	 */
	public function setRaw(array $transaction)
	{
		$this->transaction = $transaction;

		return $this;
	}

	/**
	 * Map the given array onto the user's properties.
	 *
	 * @param  array  $attributes
	 * @return $this
	 */
	public function map(array $attributes)
	{
		foreach ($attributes as $key => $value)
		{
			$this->{$key} = $value;
		}

		return $this;
	}

	/**
	 * Determine if the given raw user attribute exists.
	 *
	 * @param  string $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->transaction);
	}

	/**
	 * Get the given key from the raw user.
	 *
	 * @param  string  $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->transaction[$offset];
	}

	/**
	 * Set the given attribute on the raw user array.
	 *
	 * @param  string  $offset
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->transaction[$offset] = $value;
	}

	/**
	 * Unset the given value from the raw user array.
	 *
	 * @param  string  $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->transaction[$offset]);
	}

}
