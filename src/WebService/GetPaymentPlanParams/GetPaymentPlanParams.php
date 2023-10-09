<?php

namespace Svea\WebPay\WebService\GetPaymentPlanParams;

use Svea\WebPay\Helper\Helper;
use Svea\WebPay\Response\SveaResponse;
use Svea\WebPay\Config\ConfigurationProvider;
use Svea\WebPay\WebService\SveaSoap\SveaAuth;
use Svea\WebPay\WebService\SveaSoap\SveaDoRequest;
use Svea\WebPay\WebService\SveaSoap\SveaRequest;


/**
 * Use getPaymentPlanParams() to fetch all campaigns associated with a given client number.
 *
 * Retrieves information about all the campaigns that are associated with the
 * current Client. Use this information to display information about the possible
 * payment plan options to customers. The returned CampaignCode is used when
 * creating a PaymentPlan order.
 *
 * @author Anneli Halld'n, Daniel Brolund for Svea Webpay
 */
class GetPaymentPlanParams {
	public $testmode = false;
	public $object;
	public $conf;
	public $countryCode;
	public $logFile;

	function __construct($config) {
		$this->conf = $config;
	}

	/*
	 * Enables raw HTTP logs
	 */
	public function setLogFile($file) {
		$this->logFile = $file;

		return $this;
	}
	/**
	 * Required
	 *
	 * @param string $countryCodeAsString
	 * @return $this
	 */
	public function setCountryCode($countryCodeAsString) {
		$this->countryCode = $countryCodeAsString;

		return $this;
	}

	/**
	 * Prepares and sends request
	 *
	 * @return \Svea\WebPay\WebService\WebServiceResponse\PaymentPlanParamsResponse
	 */
	public function doRequest() {
		$requestObject = $this->prepareRequest();
		$request = new SveaDoRequest($this->conf, ConfigurationProvider::PAYMENTPLAN_TYPE, 'GetPaymentPlanParamsEu', $requestObject, $this->logFile);

		$responseObject = new SveaResponse($request->result['requestResult'], '', null, null);

		return $responseObject->response;
	}

	/**
	 * @return SveaRequest
	 */
	public function prepareRequest() {
		$auth = new SveaAuth(
			$this->conf->getUsername(ConfigurationProvider::PAYMENTPLAN_TYPE, $this->countryCode),
			$this->conf->getPassword(ConfigurationProvider::PAYMENTPLAN_TYPE, $this->countryCode),
			$this->conf->getClientNumber(ConfigurationProvider::PAYMENTPLAN_TYPE, $this->countryCode)
		);

		$object = new SveaRequest();
		$object->request = (object)['Auth' => $auth];

		return $object;
	}
}
