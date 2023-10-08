<?php

namespace Svea\WebPay\WebService\SveaSoap;

/**
 * Container class for the request attributes.
 */
class SveaRequest {

	/**
	 * mixed $request the request contents in a format ready for consumption by
	 * SveaDoRequest()
	 */
	public $request;

	/**
	 * @param mixed $request if not set, will do nothing
	 */
	function __construct($request = null) {
		if ($request) {
			$this->request = $request;
		}
	}
}
