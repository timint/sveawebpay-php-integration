<?php
// WebPayAdmin class is excluded from Svea namespace

include_once SVEA_REQUEST_DIR . "/Includes.php";

/**
 * WebPayAdmin provides entrypoints to the various administrative functions 
 * provided by Svea.
 * 
 * @version 2.0b
 * @author Kristian Grossman-Madsen for Svea WebPay
 * @package WebPay
 * @api 
 */
class WebPayAdmin {

    /**
     * Cancel an undelivered/unconfirmed order. Supports Invoice, PaymentPlan 
     * and Card orders. (For Direct Bank orders, see CreditOrder instead.)
     *  
     * Use the following methods to set the order attributes needed in the request: 
     * ->setOrderId(sveaOrderId or transactionId from createOrder response)
     * ->setCountryCode()
     * 
     * Then select the correct ordertype and perform the request:
     * ->cancelInvoiceOrder() | cancelPartPaymentOrder() | cancelCardOrder()
     *   ->doRequest
     * 
     * The final doRequest() response is of one of the following types and may 
     * contain different attributes depending on the original payment method:
     * @see CloseOrderResult (Invoice or PartPayment orders) or
     * @see HostedAdminResponse (Card orders)
     * 
     * @param ConfigurationProvider $config  instance implementing ConfigurationProvider
     * @return Svea\CancelOrderBuilder object
     * @throws Exception
     */
    public static function cancelOrder($config = NULL) {
        if( $config == NULL ) { WebPay::throwMissingConfigException(); }        
        return new Svea\CancelOrderBuilder($config);
    }
    
    /**
     * Query information about an order. Supports all order payment methods.
     * Use the following methods (@see QueryOrderBuilder):
     * ->setOrderId()
     * ->setCountryCode()  
     * 
     * Then select the correct ordertype and perform the request:
     * ->queryInvoiceOrder() | queryPaymentPlanOrder() | queryCardOrder() | queryDirectBankOrder()
     *   ->doRequest()
     *  
     * The final doRequest() response is of one of the following types and may 
     * contain different attributes depending on the original payment method:
     * @see Svea\GetOrdersResponse (Invoice or PartPayment orders) or
     * @see Svea\QueryTransactionResponse (Card or DirectBank orders)
     * 
     * @param ConfigurationProvider $config  instance implementing ConfigurationProvider
     * @return Svea\QueryOrderBuilder
     * @throws Exception
     */
    public static function queryOrder( $config = NULL ) {
        if( $config == NULL ) { WebPay::throwMissingConfigException(); }
        return new Svea\QueryOrderBuilder($config);
    }
    
    
////////////////////////////////////////////////////////////////////////////////////////    
    
    // HostedRequest/HandleOrder
    
    /**
     * annulTransaction is used to cancel (annul) a card transaction. The 
     * transaction must have status AUTHORIZED or CONFIRMED at Svea. (Indicating 
     * that the transaction has not yet been captured (settled).)
     * 
     * Use the WebPayAdmin::annulTransaction() entrypoint to get an instance of
     * AnnulTransaction. Then provide more information about the transaction and
     * send the request using @see AnnulTransaction methods.
     * 
     * @param ConfigurationProvider $config
     * @return \Svea\AnnulTransaction
     */
    static function annulTransaction($config) {
        return new Svea\AnnulTransaction($config);
    }
    
    /**
     * confirmTransaction can be performed on card transaction having the status 
     * AUTHORIZED. This will result in a CONFIRMED transaction that will be
     * captured on the given capturedate.
     * 
     * Note that this method only supports Card transactions.
     * 
     * Use the WebPayAdmin::confirmTransaction() entrypoint to get an instance of
     * ConfirmTransaction. Then provide more information about the transaction and
     * send the request using @see ConfirmTransaction methods.
     * 
     * @param ConfigurationProvider $configs
     * @return \Svea\ConfirmTransaction
     */
    static function confirmTransaction($config) {
        return new Svea\ConfirmTransaction($config);
    }
    
    /**
     * creditTransaction can be used to credit transactions. Only transactions that
     * have reached the status SUCCESS can be credited.
     * 
     * Use the WebPayAdmin::creditTransaction() entrypoint to get an instance of
     * CreditTransaction. Then provide more information about the transaction and
     * send the request using @see CreditTransaction methods.
     * 
     * @param ConfigurationProvider $configs
     * @return \Svea\CreditTransaction
     */
    static function creditTransaction($config) {
        return new Svea\CreditTransaction($config);
    }    
    
    /**
     * listPaymentMethods fetches all paymentmethods connected to the given 
     * ConfigurationProvider and country.
     *
     * Use the WebPayAdmin::listPaymentMethods() entrypoint to get an instance of
     * ListPaymentMethods. Then provide more information about the transaction and
     * send the request using @see ListPaymentMethod methods. 
     * 
     * Following the ->doRequest call you receive a @see \Svea\ListPaymentMethodsResponse
     * 
     * @param ConfigurationProvider $configs
     * @return \Svea\ListPaymentMethods
     */
    static function listPaymentMethods($config) {
        return new Svea\ListPaymentMethods($config);
    }  

    /**
     * lowerTransaction modifies the amount in an existing card transaction 
     * having status AUTHORIZED or CONFIRMED. If the amount is lowered by an 
     * amount equal to the transaction authorized amount, then after a 
     * successful request the transaction will get the status ANNULLED.
     * 
     * Use the WebPayAdmin::lowerTransaction() entrypoint to get an instance of
     * LowerTransaction. Then provide more information about the transaction and
     * send the request using @see LowerTransaction methods.
     * 
     * Following the ->doRequest call you receive a @see \Svea\LowerTransactionResponse
     * 
     * @param ConfigurationProvider $config instance implementing ConfigurationProvider
     * @return Svea\LowerTransaction
     * @throws Exception
     * 
     */
    public static function lowerTransaction( $config = NULL ) {
        if( $config == NULL ) { WebPay::throwMissingConfigException(); }
        
        return new Svea\LowerTransaction($config);
    }
    
    /**
     * Query information about an existing card or direct bank transaction.
     * 
     * Use the WebPayAdmin::queryTransaction() entrypoint to get an instance of
     * QueryTransaction. Then provide more information about the transaction and
     * send the request using @see QueryTransaction methods.
     * 
     * Note that this only supports queries based on the Svea transactionId.
     *
     * @param ConfigurationProvider $config  instance of implementation class of ConfigurationProvider Interface
     * @return Svea\QueryTransaction
     * @throws Exception
     */
    public static function queryTransaction( $config = NULL ) {
        if( $config == NULL ) { WebPay::throwMissingConfigException(); }
        return new Svea\QueryTransaction($config);
    }
    
    // WebserviceRequest/HandleOrder
    
    
    /** helper function, throws exception if no config is given */
    private static function throwMissingConfigException() {
        throw new Exception('-missing parameter: This method requires an ConfigurationProvider object as parameter. Create a class that implements class ConfigurationProvider. Set returnvalues to configuration values. Create an object from that class. Alternative use static function from class SveaConfig e.g. SveaConfig::getDefaultConfig(). You can replace the default config values to return your own config values in the method.');   
    }
}
