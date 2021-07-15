<?php

namespace Svea\WebPay\AdminService;

use SoapVar;
use Svea\WebPay\AdminService\AdminSoap\CancelAccountCreditAmountRequest;
use Svea\WebPay\BuildOrder\CreditOrderRowsBuilder;
use Svea\WebPay\AdminService\AdminSoap\Authentication;
use Svea\WebPay\AdminService\AdminSoap\CancellationRow;
use Svea\WebPay\Helper\Helper;
use Svea\WebPay\WebService\Helper\WebServiceRowFormatter;
use Svea\WebPay\BuildOrder\Validator\ValidationException;
use Svea\WebPay\AdminService\AdminSoap\CancelPaymentPlanAmountRequest;

/**
 * Admin Service CreditAmountRequest class
 *
 * @author ann-hal
 */
class CreditAmountAccountCreditRequest extends AdminServiceRequest
{
	/**
	 * @var CreditOrderRowsBuilder $orderBuilder
	 */
	public $orderBuilder;


	/**
	 * @param CreditAmountBuilder $creditAmountBuilder
	 */
	public function __construct($creditAmountBuilder)
	{
		$this->action = "CancelAccountCreditAmount";
		$this->orderBuilder = $creditAmountBuilder;
	}

	/**
	 * populate and return soap request contents using AdminSoap helper classes to get the correct data format
	 * @param bool $resendOrderWithFlippedPriceIncludingVat
	 * @return CreditOrderRowsRequest
	 * @throws ValidationException
	 */
	public function prepareRequest($resendOrderWithFlippedPriceIncludingVat = false)
	{
		$this->validateRequest();
		$soapRequest = new CancelAccountCreditAmountRequest(
			new Authentication(
				$this->orderBuilder->conf->getUsername(($this->orderBuilder->orderType), $this->orderBuilder->countryCode),
				$this->orderBuilder->conf->getPassword(($this->orderBuilder->orderType), $this->orderBuilder->countryCode)
			),
			$this->orderBuilder->amountIncVat,
			$this->orderBuilder->description,
			$this->orderBuilder->conf->getClientNumber(($this->orderBuilder->orderType), $this->orderBuilder->countryCode),
			$this->orderBuilder->orderId

		);

		return $soapRequest;
	}

	public function validate()
	{
		$errors = [];
		$errors = $this->validateOrderId($errors);
		$errors = $this->validateCountryCode($errors);
		$errors = $this->validateAmount($errors);

		return $errors;
	}

	public function validateOrderId($errors)
	{
		if (isset($this->orderBuilder->orderId) == FALSE) {
			$errors[] = ['missing value' => "orderId is required, use setOrderId()."];
		}

		return $errors;
	}

	private function validateCountryCode($errors)
	{
		if (isset($this->orderBuilder->countryCode) == FALSE) {
			$errors[] = ['missing value' => "countryCode is required, use setCountryCode()."];
		}

		return $errors;
	}

	private function validateAmount($errors)
	{
		if (!isset($this->orderBuilder->amountIncVat) || $this->orderBuilder->amountIncVat <= 0) {
			$errors[] = ['incorrect value' => "amountIncVat is too small."];
		} elseif (isset($this->orderBuilder->amountIncVat) &&
				  !(is_float($this->orderBuilder->amountIncVat) || is_int($this->orderBuilder->amountIncVat))) {
			$errors[] = ['incorrect datatype' => "amountIncVat is not of type float or int."];
		}

		return $errors;
	}

	protected function getAdminSoapOrderRowsFromBuilderOrderRowsUsingVatFlag($builderOrderRows, $priceIncludingVat = NULL)
	{
		$amount = 0;
		$orderRows = [];
		foreach ($builderOrderRows as $orderRow) {
			if (isset($orderRow->vatPercent) && isset($orderRow->amountExVat)) {
				$amount = WebServiceRowFormatter::convertExVatToIncVat($orderRow->amountExVat, $orderRow->vatPercent);
			} elseif (isset($orderRow->vatPercent) && isset($orderRow->amountIncVat)) {
				$amount = $orderRow->amountIncVat;
			} else {
				$amount = $orderRow->amountIncVat;
				$orderRow->vatPercent = WebServiceRowFormatter::calculateVatPercentFromPriceExVatAndPriceIncVat($orderRow->amountIncVat, $orderRow->amountExVat);
			}
			$orderRows[] = new SoapVar(
				new CancellationRow(
					$amount,
					$this->formatRowNameAndDescription($orderRow),
					$orderRow->rowNumber,
					$orderRow->vatPercent
				), SOAP_ENC_OBJECT, null, null, 'CancellationRow', "http://schemas.datacontract.org/2004/07/DataObjects.Webservice"
			);
		}

		return $orderRows;
	}
}
