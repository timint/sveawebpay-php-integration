<?php

namespace Svea\WebPay\WebService\SveaSoap;

use Svea\WebPay\Helper\Helper;
use Svea\WebPay\Config\ConfigurationProvider;

/**
 * Create SoapObject
 * Do request
 * - return Response Object
 */
class SveaDoRequest {
	private $svea_server;
	private $client;

	public $result;

	/**
	 * Constructor, sets up soap server and SoapClient
	 * @param ConfigurationProvider $config
	 * @param string $ordertype -- see Svea\WebPay\Config\ConfigurationProvider:: constants
	 * @param string $method Method to call by soap
	 * @param object $object Object to pass in soap call
	 * @param string $logFile
	 */
	public function __construct($config, $ordertype, $method, $object, $logFile) {
		$this->svea_server = $config->getEndPoint($ordertype);
		$this->client = $this->SetSoapClient($config);
		$this->result = $this->CallSoap($method, $object, $logFile);
	}

	private function CallSoap($method, $order, $logFile) {

		$params = (array)$order;

		if ($logFile) {
			$timestampStart = time();
			$microtimeStart = microtime(true);
		}

		try {
			$result = ["requestResult" => $this->client->__soapCall($method, [$params])];
		} catch (\SoapFault $soapFault) {
			$error = $soapFault;
		}

		if ($logFile) {
			$log = [
				"request" => [
					"timestamp" => $timestampStart,
					"headers" => $this->client->__getLastRequestHeaders(),
					"body" => $this->prettyPrintXml($this->client->__getLastRequest())
				],
				"response" => [
					"timestamp" => time(),
					"headers" => $this->client->__getLastResponseHeaders(),
					"body" => $this->prettyPrintXml($this->client->__getLastResponse()),
					"dataAmount" => strlen($this->client->__getLastResponseHeaders()) + strlen($this->client->__getLastResponse()),
					"duration" => round(microtime(true) - $microtimeStart, 3)
				]
			];

			file_put_contents($logFile,
				'##'. str_pad(' ['. date('Y-m-d H:i:s', $log['request']['timestamp']) .'] Request ', 70, '#', STR_PAD_RIGHT) . "\r\n\r\n" .
				$log['request']['headers'] . "\r\n" .
				$log['request']['body'] . "\r\n\r\n" .
				'##'. str_pad(' ['. date('Y-m-d H:i:s', $log['response']['timestamp']) .'] Response — '. $log['response']['dataAmount'] .' bytes transferred in '. $log['response']['duration'] .' s ', 72, '#', STR_PAD_RIGHT) . "\r\n\r\n" .
				$log['response']['headers'] . "\r\n" .
				$log['response']['body']
			);
		}

		if (!empty($error)) {
			throw $error;
		}

		return $result;
	}

	private function SetSoapClient($config) {
		$libraryProperties = Helper::getSveaLibraryProperties();
		$libraryName = $libraryProperties['library_name'];
		$libraryVersion = $libraryProperties['library_version'];

		$integrationProperties = Helper::getSveaIntegrationProperties($config);
		$integrationPlatform = $integrationProperties['integration_platform'];
		$integrationCompany = $integrationProperties['integration_company'];
		$integrationVersion = $integrationProperties['integration_version'];

		$client = new \SoapClient(
			$this->svea_server,
			[
				"trace" => 1,
				'stream_context' => stream_context_create(['http' => [
					'header' => 'X-Svea-Library-Name: ' . $libraryName . "\n" .
						'X-Svea-Library-Version: ' . $libraryVersion . "\n" .
						'X-Svea-Integration-Platform: ' . $integrationPlatform . "\n" .
						'X-Svea-Integration-Company: ' . $integrationCompany . "\n" .
						'X-Svea-Integration-Version: ' . $integrationVersion
				]]),
				"soap_version" => SOAP_1_2
			]
		);
		return $client;
	}

	private function prettyPrintXml(string $xml) {

		$dom = new \DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml);
		$xml = $dom->saveXML();

		return $xml;
	}
}