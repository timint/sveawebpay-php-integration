<?php
namespace Svea;

require_once SVEA_REQUEST_DIR . '/Includes.php';

/**
 * Update order rows in an non-delivered invoice or payment plan order, 
 * or lower amount to charge in non-confirmed card orders. Supports Invoice 
 * and Payment Plan orders, limited support for Card orders. (Direct Bank 
 * orders are not supported.)
 * 
 * For Invoice and Payment Plan orders, the order row status of the order is updated
 * to reflect the added order rows. For Card orders, the original order row statuses
 * are not changed, but the order amount is lowered to the new order amount. If the
 * updated rows order total exceeds the original order total, an error is returned
 * by the service. 
 * 
 * Use setCountryCode() to specify the country code matching the original create
 * order request.
 * 
 * Use addOrderRow() or addOrderRows() to specify the order row(s) to add to the order. 
 * The order row numbers must match those given in setRow(s)ToUpdate() and the order. 
 * // TODO what if they don't?? 
 * 
 * Then use either updateInvoiceOrderRows() or updatePaymentPlanOrderRows(), or
 * updateCardOrderRows(), which ever matches the payment method used in the original order request.
 * 
 * The final doRequest() will send the updateOrderRows request to Svea, and the 
 * resulting response code specifies the outcome of the request. 
 * 
 * @author Kristian Grossman-Madsen for Svea WebPay
 */
class UpdateOrderRowsBuilder {

    /** @var ConfigurationProvider $conf  */
    public $conf;
    
    /** @var NumberedOrderRows[] $numberedOrderRows  the updated order rows */
    public $numberedOrderRows;

    /** @var int[] $rowsToUpdate  the order rows to update, must match both numberedOrderRows above and the actual order order rows */
    public $rowsToUpdate;
    
    public function __construct($config) {
         $this->conf = $config;
         $this->orderRows = array();
    }

    /**
     * Required. Use SveaOrderId recieved with createOrder response.
     * @param string $orderIdAsString
     * @return $this
     */
    public function setOrderId($orderIdAsString) {
        $this->orderId = $orderIdAsString;
        return $this;
    }
    /** string $orderId  Svea order id to query, as returned in the createOrder request response, either a transactionId or a SveaOrderId */
    public $orderId;
    
    /**
     * Required. Use same countryCode as in createOrder request.
     * @param string $countryCode
     * @return $this
     */
    public function setCountryCode($countryCodeAsString) {
        $this->countryCode = $countryCodeAsString;
        return $this;
    }
    /** @var string $countryCode */
    public $countryCode;

    /**
     * Required.
     * @param string $orderType -- one of ConfigurationProvider::INVOICE_TYPE, ::PAYMENTPLAN_TYPE
     * @return $this
     */
    public function setOrderType($orderTypeAsConst) {
        $this->orderType = $orderTypeAsConst;
        return $this;
    }
    /** @var string $orderType -- one of ConfigurationProvider::INVOICE_TYPE, ::PAYMENTPLAN_TYPE */
    public $orderType;    

    /**
     * Required.
     * @param int $row
     * @return $this
     */
    public function setRowToUpdate( $row ) {
        $this->rowsToUpdate[] = $row;
        return $this;
    }    
    
    /**
     * Convenience method to add several rows at once.
     * @param int[] $rows
     * @return $this
     */
    public function setRowsToUpdate( $rows ) {
        array_merge( $this->rowsToUpdate, $rows );
        return $this;
    }      
    
    /**
     * Required.
     * @param NumberedOrderRow $row
     * @return $this
     */
    public function addOrderRow( $row ) {
        $this->numberedOrderRows[] = $row;
        return $this;
    }    
    
    /**
     * Convenience method to add several rows at once.
     * @param NumberedOrderRow[] $rows
     * @return $this
     */
    public function addOrderRows( $rows ) {
        array_merge( $this->numberedOrderRows, $rows );
        return $this;
    }    

    /**
     * Use updateInvoiceOrderRows() to update an Invoice order using AdminServiceRequest UpdateOrderRows request
     * @return UpdateOrderRowsRequest 
     */
    public function updateInvoiceOrderRows() {
        $this->setOrderType(\ConfigurationProvider::INVOICE_TYPE );
        return new UpdateOrderRowsRequest($this);
    }
    
    /**
     * Use updatePaymentPlanOrderRows() to update a PaymentPlan order using AdminServiceRequest UpdateOrderRows request
     * @return UpdateOrderRowsRequest 
     */
    public function updatePaymentPlanOrderRows() {
        $this->setOrderType(\ConfigurationProvider::PAYMENTPLAN_TYPE );
        return new UpdateOrderRowsRequest($this);
    }
}