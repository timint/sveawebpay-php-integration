<?php
// Integration tests should not need to use the namespace

$root = realpath(dirname(__FILE__));
require_once $root . '/../../src/Includes.php';
require_once $root . '/../TestUtil.php';

/**
 * @author Kristian Grossman-Madsen for Svea WebPay
 */
class WebPayAdminIntegrationTest extends PHPUnit_Framework_TestCase {

    /// cancelOrder()
    // invoice
    // partpayment
    // card
    public function test_cancelOrder_cancelInvoiceOrder_returns_CloseOrder() {
        $cancelOrder = WebPayAdmin::cancelOrder( Svea\SveaConfig::getDefaultConfig() );
        $request = $cancelOrder->cancelInvoiceOrder();        
        $this->assertInstanceOf( "Svea\WebService\CloseOrder", $request );
        $this->assertEquals(\ConfigurationProvider::INVOICE_TYPE, $request->orderBuilder->orderType); 
    }
    
    public function test_cancelOrder_cancelPaymentPlanOrder_returns_CloseOrder() {
        $cancelOrder = WebPayAdmin::cancelOrder( Svea\SveaConfig::getDefaultConfig() );
        $request = $cancelOrder->cancelPaymentPlanOrder();        
        $this->assertInstanceOf( "Svea\WebService\CloseOrder", $request );
        $this->assertEquals(\ConfigurationProvider::PAYMENTPLAN_TYPE, $request->orderBuilder->orderType); 
    }

    public function test_cancelOrder_cancelCardOrder_returns_AnnulTransaction() {
        $cancelOrder = WebPayAdmin::cancelOrder( Svea\SveaConfig::getDefaultConfig() );
        $request = $cancelOrder->cancelCardOrder();        
        $this->assertInstanceOf( "Svea\HostedService\AnnulTransaction", $request );
    }

    /// queryOrder()
    // invoice
    // partpayment
    // card
    // direct bank
    public function test_queryOrder_queryInvoiceOrder_returns_GetOrdersRequest() {
        $queryOrder = WebPayAdmin::queryOrder( Svea\SveaConfig::getDefaultConfig() );
        $request = $queryOrder->queryInvoiceOrder();        
        $this->assertInstanceOf( "Svea\AdminService\GetOrdersRequest", $request );
        $this->assertEquals(\ConfigurationProvider::INVOICE_TYPE, $request->orderBuilder->orderType); 
    }    
    
    public function test_queryOrder_queryPaymentPlanOrder_returns_GetOrdersRequest() {
        $queryOrder = WebPayAdmin::queryOrder( Svea\SveaConfig::getDefaultConfig() );
        $request = $queryOrder->queryPaymentPlanOrder();        
        $this->assertInstanceOf( "Svea\AdminService\GetOrdersRequest", $request );
        $this->assertEquals(\ConfigurationProvider::PAYMENTPLAN_TYPE, $request->orderBuilder->orderType); 
    }       

    public function test_queryOrder_queryCardOrder_returns_QueryTransaction() {
        $queryOrder = WebPayAdmin::queryOrder( Svea\SveaConfig::getDefaultConfig() );
        $request = $queryOrder->queryCardOrder();        
        $this->assertInstanceOf( "Svea\HostedService\QueryTransaction", $request );
    } 

    public function test_queryOrder_queryDirectBankOrder_returns_QueryTransaction() {
        $queryOrder = WebPayAdmin::queryOrder( Svea\SveaConfig::getDefaultConfig() );
        $request = $queryOrder->queryDirectBankOrder();
        $this->assertInstanceOf( "Svea\HostedService\QueryTransaction", $request );
    }     
    
    /// cancelOrderRows()
    // invoice
    // partpayment
    // card
    
    
    
}
