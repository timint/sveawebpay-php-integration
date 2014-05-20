<?php
namespace Svea;

$root = realpath(dirname(__FILE__));
require_once $root . '/../../../src/Includes.php';

$root = realpath(dirname(__FILE__));
require_once $root . '/../../TestUtil.php';

/**
 * @author Kristian Grossman-Madsen for Svea Webpay
 */
class GetOrdersRequestTest extends \PHPUnit_Framework_TestCase {

    public $builderObject;
    
    public function setUp() {        
        $this->builderObject = new OrderBuilder(SveaConfig::getDefaultConfig());  
        // TODO create classes w/methods for below
        $this->builderObject->orderId = 123456;
    }
    
    public function testClassExists() {
        $getOrdersRequestObject = new GetOrdersRequest( $this->builderObject );
        $this->assertInstanceOf('Svea\GetOrdersRequest', $getOrdersRequestObject);
    }
    
    public function test_validate_throws_exception_on_missing_OrderId() {

        $this->setExpectedException(
          'Svea\ValidationException', '-missing value : orderId is required.'
        );
        
        unset( $this->builderObject->orderId );
        $getOrdersRequestObject = new GetOrdersRequest( $this->builderObject );
        $request = $getOrdersRequestObject->prepareRequest();
    }    
}
