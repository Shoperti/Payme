<?php namespace Dinkbit\Payme;

class Status {

	/**
	 * @var
	 */
	public $status;

	/**
	 * @var array
	 */
	protected $statuses = [
		'pending',
		'authorized',
		'paid',
		'partially_paid',
		'refunded',
		'voided',
		'partially_refunded',
		'unpaid',
		'failed',
		'active',
		'canceled',
		'trial',
	];

	/**
	 * @param $status
	 */
	public function __construct($status)
	{
		$this->disallowInvalidMethod($status);

		$this->status = $status;
	}

	/**
	 * @param $status
	 * @throws InvalidArgumentException
	 */
	protected function disallowInvalidMethod($status)
	{
		if ( ! in_array($status, $this->statuses))
		{
			throw new InvalidArgumentException('Invalid status provided.');
		}
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->status;
	}

}
