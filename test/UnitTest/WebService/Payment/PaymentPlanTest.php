<?php

namespace Svea\WebPay\Test\UnitTest\WebService\Payment;

use Svea\WebPay\WebPay;
use Svea\WebPay\WebPayItem;
use Svea\WebPay\Test\TestUtil;
use PHPUnit_Framework_TestCase;
use Svea\WebPay\Config\SveaConfig;


/**
 * @author Anneli Halld'n, Daniel Brolund for Svea Webpay
 */
class PaymentPlanTest extends PHPUnit_Framework_TestCase
{

    /**
     * Use to get paymentPlanParams to be able to test PaymentPlanRequest
     * @return type
     */
    public function getGetPaymentPlanParamsForTesting()
    {
        $config = SveaConfig::getDefaultConfig();
        $addressRequest = WebPay::getPaymentPlanParams($config);
        $response = $addressRequest
            ->setCountryCode("SE")
            ->doRequest();

        return $response->campaignCodes[0]->campaignCode;
    }

    public function testPaymentPlanRequestObjectSpecifics()
    {
        $config = SveaConfig::getDefaultConfig();
        $rowFactory = new TestUtil();
        $request = WebPay::createOrder($config)
            ->addOrderRow(TestUtil::createOrderRow())
            ->run($rowFactory->buildShippingFee())
            ->addCustomerDetails(WebPayItem::individualCustomer()->setNationalIdNumber(194605092222))
            ->setCountryCode("SE")
            ->setCustomerReference("33")
            ->setClientOrderNumber("nr26")
            ->setOrderDate("2012-12-12")
            ->setCurrency("SEK")
            ->usePaymentPlanPayment("camp1")// returnerar InvoiceOrder object
            ->prepareRequest();

        $this->assertEquals('camp1', $request->request->CreateOrderInformation->CreatePaymentPlanDetails['CampaignCode']);
        $this->assertEquals(0, $request->request->CreateOrderInformation->CreatePaymentPlanDetails['SendAutomaticGiroPaymentForm']);
    }

    public function testInvoiceRequestObjectWithRelativeDiscountOnTwoProducts()
    {
        $config = SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
            ->addOrderRow(WebPayItem::orderRow()
                ->setArticleNumber("1")
                ->setQuantity(2)
                ->setAmountExVat(240.00)
                ->setAmountIncVat(300.00)
                ->setDescription("CD")
            )
            ->addDiscount(WebPayItem::relativeDiscount()
                ->setDiscountId("1")
                ->setDiscountPercent(10)
                ->setDescription("RelativeDiscount")
            )
            ->addCustomerDetails(WebPayItem::individualCustomer()->setNationalIdNumber(194605092222))
            ->setCountryCode("SE")
            ->setCustomerReference("33")
            ->setOrderDate("2012-12-12")
            ->setCurrency("SEK")
            ->useInvoicePayment()
            ->prepareRequest();
        //couponrow
        $this->assertEquals('1', $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->ArticleNumber);
        $this->assertEquals('RelativeDiscount', $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->Description);
        $this->assertEquals(-60.00, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->PricePerUnit);
        $this->assertEquals(1, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->NumberOfUnits);
        $this->assertEquals('', $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->Unit);
        $this->assertEquals(25, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->VatPercent);
        $this->assertEquals(0, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->DiscountPercent);
    }

    public function testPaymentPlanWithPriceAsDecimal()
    {
        $config = SveaConfig::getDefaultConfig();
        $campaign = $this->getGetPaymentPlanParamsForTesting();
        $request = WebPay::createOrder($config)
            ->addOrderRow(WebPayItem::orderRow()
                ->setArticleNumber("1")
                ->setQuantity(2)
                ->setAmountExVat(240.00)
                ->setAmountIncVat(300.00)
                ->setDescription("CD")
            )
            ->addCustomerDetails(WebPayItem::individualCustomer()->setNationalIdNumber(194605092222))
            ->setCountryCode("SE")
            ->setCustomerReference("33")
            ->setOrderDate("2012-12-12")
            ->setCurrency("SEK")
            ->usePaymentPlanPayment($campaign)
            ->prepareRequest();
        //couponrow

        $this->assertEquals(300.00, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PricePerUnit);
        $this->assertEquals(2, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->NumberOfUnits);
        $this->assertEquals(25, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->VatPercent);
    }


    /**
     * Tests for rounding**
     */

    public function testDiscountSetAsExVatWhenPriceSetAsExVatAndVatPercent()
    {
        $config = SveaConfig::getDefaultConfig();
        $campaign = $this->getGetPaymentPlanParamsForTesting();
        $request = WebPay::createOrder($config)
            ->addOrderRow(
                WebPayItem::orderRow()
                    ->setAmountExVat(80.00)
                    ->setVatPercent(24)
                    ->setQuantity(1)
            )
            ->addDiscount(WebPayItem::fixedDiscount()->setAmountExVat(8))
            ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
            ->setCountryCode("SE")
            ->setOrderDate("2012-12-12")
            ->usePaymentPlanPayment($campaign)
            ->prepareRequest();
        $this->assertEquals(80, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->VatPercent);

        $this->assertEquals(-8, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->VatPercent);

    }

    public function testDiscountSetAsExVatAndVatPercentWhenPriceSetAsExVatAndVatPercent()
    {
        $config = SveaConfig::getDefaultConfig();
        $campaign = $this->getGetPaymentPlanParamsForTesting();
        $request = WebPay::createOrder($config)
            ->addOrderRow(
                WebPayItem::orderRow()
                    ->setAmountExVat(80.00)
                    ->setVatPercent(24)
                    ->setQuantity(1)
            )
            ->addDiscount(WebPayItem::fixedDiscount()
                ->setAmountExVat(8)
                ->setVatPercent(0))
            ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
            ->setCountryCode("SE")
            ->setOrderDate("2012-12-12")
            ->usePaymentPlanPayment($campaign)
            ->prepareRequest();
        $this->assertEquals(80, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->VatPercent);

        $this->assertEquals(-8, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->PricePerUnit);
        $this->assertEquals(0, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->VatPercent);

    }

    public function testDiscountPercentAndVatPercentWhenPriceSetAsExVatAndVatPercent()
    {
        $config = SveaConfig::getDefaultConfig();
        $campaign = $this->getGetPaymentPlanParamsForTesting();
        $request = WebPay::createOrder($config)
            ->addOrderRow(
                WebPayItem::orderRow()
                    ->setAmountExVat(80.00)
                    ->setVatPercent(24)
                    ->setQuantity(1)
            )
            ->addDiscount(WebPayItem::relativeDiscount()
                ->setDiscountPercent(10)
            )
            ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
            ->setCountryCode("SE")
            ->setOrderDate("2012-12-12")
            ->usePaymentPlanPayment($campaign)
            ->prepareRequest();
        $this->assertEquals(80, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->VatPercent);

        $this->assertEquals(-8, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->VatPercent);

    }

    public function testFeeSetAsExVatAndVatPercentWhenPriceSetAsExVatAndVatPercent()
    {
        $config = SveaConfig::getDefaultConfig();
        $campaign = $this->getGetPaymentPlanParamsForTesting();
        $request = WebPay::createOrder($config)
            ->addOrderRow(
                WebPayItem::orderRow()
                    ->setAmountExVat(80.00)
                    ->setVatPercent(24)
                    ->setQuantity(1)
            )
            ->addFee(WebPayItem::shippingFee()
                ->setAmountExVat(80.00)
                ->setVatPercent(24)
            )
            ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
            ->setCountryCode("SE")
            ->setOrderDate("2012-12-12")
            ->usePaymentPlanPayment($campaign)
            ->prepareRequest();

        $this->assertEquals(80, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->VatPercent);

        $this->assertEquals(80, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->VatPercent);

    }

    public function testOrderRowPriceSetAsInkVatAndVatPercentSetAmountAsIncVat()
    {
        $config = SveaConfig::getDefaultConfig();
        $campaign = $this->getGetPaymentPlanParamsForTesting();
        $request = WebPay::createOrder($config)
            ->addOrderRow(
                WebPayItem::orderRow()
                    ->setAmountIncVat(123.9876)
                    ->setVatPercent(24)
                    ->setQuantity(1)
            )
            ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
            ->setCountryCode("SE")
            ->setOrderDate("2012-12-12")
            ->usePaymentPlanPayment($campaign)
            ->prepareRequest();

        $this->assertEquals(123.9876, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->VatPercent);
        $this->assertTrue($request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PriceIncludingVat);

    }

    public function testFeeSetAsIncVatAndVatPercentWhenPriceSetAsIncVatAndVatPercent()
    {
        $config = SveaConfig::getDefaultConfig();
        $campaign = $this->getGetPaymentPlanParamsForTesting();
        $request = WebPay::createOrder($config)
            ->addOrderRow(
                WebPayItem::orderRow()
                    ->setAmountIncVat(123.9876)
                    ->setVatPercent(24)
                    ->setQuantity(1)
            )
            ->addFee(WebPayItem::shippingFee()
                ->setAmountIncVat(100.00)
                ->setVatPercent(24)
            )
            ->addFee(WebPayItem::invoiceFee()
                ->setAmountIncVat(100.00)
                ->setVatPercent(24)
            )
            ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
            ->setCountryCode("SE")
            ->setOrderDate("2012-12-12")
            ->usePaymentPlanPayment($campaign)
            ->prepareRequest();

        $this->assertEquals(123.9876, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->VatPercent);

        $this->assertEquals(100, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->VatPercent);

        $this->assertEquals(100, $request->request->CreateOrderInformation->OrderRows['OrderRow'][2]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][2]->VatPercent);

    }

    public function testDiscountSetAsIncVatWhenPriceSetAsIncVatAndVatPercent()
    {
        $config = SveaConfig::getDefaultConfig();
        $campaign = $this->getGetPaymentPlanParamsForTesting();
        $request = WebPay::createOrder($config)
            ->addOrderRow(
                WebPayItem::orderRow()
                    ->setAmountIncVat(123.9876)
                    ->setVatPercent(24)
                    ->setQuantity(1)
            )
            ->addDiscount(WebPayItem::fixedDiscount()->setAmountIncVat(10))
            ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
            ->setCountryCode("SE")
            ->setOrderDate("2012-12-12")
            ->usePaymentPlanPayment($campaign)
            ->prepareRequest();
        $this->assertEquals(123.9876, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->VatPercent);
        $this->assertTrue($request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PriceIncludingVat);

        $this->assertEquals(-10, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->VatPercent);
        $this->assertTrue($request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PriceIncludingVat);


    }

    public function testDiscountSetAsExVatAndVatPercentWhenPriceSetAsIncVatAndVatPercent()
    {
        $config = SveaConfig::getDefaultConfig();
        $campaign = $this->getGetPaymentPlanParamsForTesting();
        $request = WebPay::createOrder($config)
            ->addOrderRow(
                WebPayItem::orderRow()
                    ->setAmountIncVat(123.9876)
                    ->setVatPercent(24)
                    ->setQuantity(1)
            )
            ->addDiscount(WebPayItem::fixedDiscount()
                ->setAmountIncVat(10)
                ->setVatPercent(0))
            ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
            ->setCountryCode("SE")
            ->setOrderDate("2012-12-12")
            ->usePaymentPlanPayment($campaign)
            ->prepareRequest();
        $this->assertEquals(123.9876, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->VatPercent);
        $this->assertTrue($request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PriceIncludingVat);

        $this->assertEquals(-10, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->PricePerUnit);
        $this->assertEquals(0, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->VatPercent);
        $this->assertTrue($request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PriceIncludingVat);

    }


    public function testDiscountPercentAndVatPercentWhenPriceSetAsIncVatAndVatPercent()
    {
        $config = SveaConfig::getDefaultConfig();
        $campaign = $this->getGetPaymentPlanParamsForTesting();
        $request = WebPay::createOrder($config)
            ->addOrderRow(
                WebPayItem::orderRow()
                    ->setAmountIncVat(123.9876)
                    ->setVatPercent(24)
                    ->setQuantity(1)
            )
            ->addDiscount(WebPayItem::relativeDiscount()
                ->setDiscountPercent(10)
            )
            ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
            ->setCountryCode("SE")
            ->setOrderDate("2012-12-12")
            ->useInvoicePayment()
            ->prepareRequest();
        $this->assertEquals(123.9876, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->VatPercent);
        $this->assertTrue($request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PriceIncludingVat);

        $this->assertEquals(-12.39876, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->PricePerUnit);
        $this->assertEquals(24, $request->request->CreateOrderInformation->OrderRows['OrderRow'][1]->VatPercent);
        $this->assertTrue($request->request->CreateOrderInformation->OrderRows['OrderRow'][0]->PriceIncludingVat);

    }

}
