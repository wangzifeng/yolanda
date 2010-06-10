<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Paypal
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * PayPal Website Payments Pro implementation for payment method instaces
 * This model was created because right now PayPal Direct and PayPal Express payment methods cannot have same abstract
 */
class Mage_Paypal_Model_Pro implements Mage_Payment_Model_Recurring_Profile_MethodInterface
{
    /**
     * Config instance
     *
     * @var Mage_Paypal_Model_Config
     */
    protected $_config = null;

    /**
     * API instance
     *
     * @var Mage_Paypal_Model_Api_Nvp
     */
    protected $_api = null;

    /**
     * API model type
     *
     * @var string
     */
    protected $_apiType = 'paypal/api_nvp';

    /**
     * Config model type
     *
     * @var string
     */
    protected $_configType = 'paypal/config';

    /**
     * Keys for passthrough variables in  sales/order_payment
     * Uses additional_information as storage
     * @var string
     */
    const CAN_REVIEW_PAYMENT = 'can_review_payment';

    /**
     * Payment method code setter. Also instantiates/updates config
     *
     * @param string $code
     * @param int|null $storeId
     */
    public function setMethod($code, $storeId = null)
    {
        if (null === $this->_config) {
            $params = array($code);
            if (null !== $storeId) {
                $params[] = $storeId;
            }
            $this->_config = Mage::getModel($this->_configType, $params);
        } else {
            $this->_config->setMethod($code);
            if (null !== $storeId) {
                $this->_config->setStoreId($storeId);
            }
        }
        return $this;
    }

    /**
     * Config instance setter
     *
     * @param Mage_Paypal_Model_Config $instace
     * @param int $storeId
     */
    public function setConfig(Mage_Paypal_Model_Config $instace, $storeId = null)
    {
        $this->_config = $instace;
        if (null !== $storeId) {
            $this->_config->setStoreId($storeId);
        }
        return $this;
    }

    /**
     * Config instance getter
     *
     * @return Mage_Paypal_Model_Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * API instance getter
     * Sets current store id to current config instance and passes it to API
     *
     * @return Mage_Paypal_Model_Api_Nvp
     */
    public function getApi()
    {
        if (null === $this->_api) {
            $this->_api = Mage::getModel($this->_apiType);
        }
        $this->_api->setConfigObject($this->_config);
        return $this->_api;
    }

    /**
     * Void transaction
     *
     * @param Varien_Object $payment
     */
    public function void(Varien_Object $payment)
    {
        if ($authTransactionId = $this->_getParentTransactionId($payment)) {
            $api = $this->getApi();
            $api->setPayment($payment)->setAuthorizationId($authTransactionId)->callDoVoid();
            Mage::getModel('paypal/info')->importToPayment($api, $payment);
        } else {
            Mage::throwException(Mage::helper('paypal')->__('Authorization transaction is required to void.'));
        }
    }

    /**
     * Attempt to capture payment
     * Will return false if the payment is not supposed to be captured
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return false|null
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $authTransactionId = $this->_getParentTransactionId($payment);
        if (!$authTransactionId) {
            return false;
        }
        /**
         * check transaction status before capture
         */
        $api = $this->getApi()
            ->setTransactionId($authTransactionId);
        if (!$this->_isCaptureNeeded()) {
            Mage::getModel('paypal/info')->importToPayment($api, $payment);
            return;
        }

        $api = $this->getApi()
            ->setAuthorizationId($authTransactionId)
            ->setIsCaptureComplete($payment->getShouldCloseParentTransaction())
            ->setAmount($amount)
            ->setCurrencyCode($payment->getOrder()->getBaseCurrencyCode())
            ->setInvNum($payment->getOrder()->getIncrementId())
            // TODO: pass 'NOTE' to API
        ;

        $api->callDoCapture();
        $this->_importCaptureResultToPayment($api, $payment);
    }



    /**
     * Refund a capture transaction
     *
     * @param Varien_Object $payment
     * @param float $amount
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $captureTxnId = $this->_getParentTransactionId($payment);
        if ($captureTxnId) {
            $api = $this->getApi();
            $order = $payment->getOrder();
            $api->setPayment($payment)
                ->setTransactionId($captureTxnId)
                ->setAmount($amount)
                ->setCurrencyCode($order->getBaseCurrencyCode())
            ;
            $canRefundMore = $order->canCreditmemo(); // TODO: fix this to be able to create multiple refunds
            $isFullRefund = !$canRefundMore
                && (0 == ((float)$order->getBaseTotalOnlineRefunded() + (float)$order->getBaseTotalOfflineRefunded()));
            $api->setRefundType($isFullRefund ? Mage_Paypal_Model_Config::REFUND_TYPE_FULL
                : Mage_Paypal_Model_Config::REFUND_TYPE_PARTIAL
            );
            $api->callRefundTransaction();
            $this->_importRefundResultToPayment($api, $payment, $canRefundMore);
        } else {
            Mage::throwException(Mage::helper('paypal')->__('Impossible to issue a refund transaction because the capture transaction does not exist.'));
        }
    }

    /**
     * Cancel payment
     *
     * @param Varien_Object $payment
     */
    public function cancel(Varien_Object $payment)
    {
        if (!$payment->getOrder()->getInvoiceCollection()->count()) {
            $this->void($payment);
        }
    }

    /**
     * Accept payment
     *
     * @param Varien_Object $payment
     */
    public function accept(Varien_Object $payment)
    {
        $captureTxnId = $payment->getLastTransId();
        if ($captureTxnId) {
            $api = $this->getApi();
            $api->setPayment($payment)
                ->setTransactionId($captureTxnId)
                ->setAction(Mage_Paypal_Model_Api_Nvp::PENDING_TRANSACTION_ACCEPT);

            $api->callManagePendingTransactionStatus();

            $this->_importAcceptResultToPayment($api, $payment);
        } else {
            Mage::throwException(Mage::helper('paypal')->__('Impossible to accept transaction because the capture transaction does not exist.'));
        }
    }

    /**
     * Deny payment
     *
     * @param Varien_Object $payment
     */
    public function deny(Varien_Object $payment)
    {
        $captureTxnId = $payment->getLastTransId();
        if ($captureTxnId) {
            $api = $this->getApi();
            $api->setPayment($payment)
                ->setTransactionId($captureTxnId)
                ->setAction(Mage_Paypal_Model_Api_Nvp::PENDING_TRANSACTION_DENY);

            $api->callManagePendingTransactionStatus();

            $this->_importDenyResultToPayment($api, $payment);
        } else {
            Mage::throwException(Mage::helper('paypal')->__('Impossible to deny transaction because the capture transaction does not exist.'));
        }
    }

    /**
     * Import accept results to payment
     *
     * @param Mage_Paypal_Model_Api_Nvp
     * @param Mage_Sales_Model_Order_Payment
     */
    protected function _importAcceptResultToPayment($api, $payment)
    {
        $payment
            ->setTransactionId($api->getTransactionId())
            ->setIsTransactionClosed(false)
            ->setIsPaymentCompleted($api->getIsPaymentCompleted());
        Mage::getModel('paypal/info')->importToPayment($api, $payment);
    }

    /**
     * Import deny results to payment
     *
     * @param Mage_Paypal_Model_Api_Nvp
     * @param Mage_Sales_Model_Order_Payment
     */
    protected function _importDenyResultToPayment($api, $payment)
    {
        $payment
            ->setTransactionId($api->getTransactionId())
            ->setIsTransactionClosed(true)
            ->setIsPaymentDenied($api->getIsPaymentDenied());
        Mage::getModel('paypal/info')->importToPayment($api, $payment);
    }

    /**
     * Fetch transaction details info
     *
     * @param string $transactionId
     * @return array
     */
    public function fetchTransactionInfo($transactionId)
    {
        $api = $this->getApi()
            ->setTransactionId($transactionId)
            ->setRawResponseNeeded(true);
        $api->callGetTransactionDetails();
        $data = $api->getRawSuccessResponseData();
        return ($data) ? $data : array();
    }

    /**
     * Validate RP data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @throws Mage_Core_Exception
     */
    public function validateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        $errors = array();
        if (strlen($profile->getSubscriberName()) > 32) { // up to 32 single-byte chars
            $errors[] = Mage::helper('paypal')->__('Subscriber name is too long.');
        }
        $refId = $profile->getInternalReferenceId(); // up to 127 single-byte alphanumeric
        if (strlen($refId) > 127) { //  || !preg_match('/^[a-z\d\s]+$/i', $refId)
            $errors[] = Mage::helper('paypal')->__('Merchant reference ID format is not supported.');
        }
        $scheduleDescr = $profile->getScheduleDescription(); // up to 127 single-byte alphanumeric
        if (strlen($refId) > 127) { //  || !preg_match('/^[a-z\d\s]+$/i', $scheduleDescr)
            $errors[] = Mage::helper('paypal')->__('Schedule description is too long.');
        }
        if ($errors) {
            Mage::throwException(implode(' ', $errors));
        }
    }

    /**
     * Submit RP to the gateway
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @throws Mage_Core_Exception
     */
    public function submitRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        $api = $this->getApi();
        $api->callCreateRecurringPaymentsProfile($profile);
        $profile->setReferenceId($api->getRecurringProfileId())
            ->setState($api->getIsProfileActive() ? Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE
                : Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED
            );
    }

    /**
     * Fetch RP details
     *
     * @param string $referenceId
     * @param Mage_Payment_Model_Recurring_Profile_Info $result
     */
    public function getRecurringProfileDetails($referenceId, Mage_Payment_Model_Recurring_Profile_Info $result)
    {

    }

    /**
     * Update RP data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        // not implemented
    }

    /**
     * Manage status
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfileStatus(Mage_Payment_Model_Recurring_Profile $profile)
    {

    }

    /**
     * Import capture results to payment
     *
     * @param Mage_Paypal_Model_Api_Nvp
     * @param Mage_Sales_Model_Order_Payment
     */
    protected function _importCaptureResultToPayment($api, $payment)
    {
        $payment->setTransactionId($api->getTransactionId())->setIsTransactionClosed(false);
        Mage::getModel('paypal/info')->importToPayment($api, $payment);
    }

    /**
     * Import refund results to payment
     *
     * @param Mage_Paypal_Model_Api_Nvp
     * @param Mage_Sales_Model_Order_Payment
     * @param bool $canRefundMore
     */
    protected function _importRefundResultToPayment($api, $payment, $canRefundMore)
    {
        $payment->setTransactionId($api->getRefundTransactionId())
                ->setIsTransactionClosed(1) // refund initiated by merchant
                ->setShouldCloseParentTransaction(!$canRefundMore)
            ;
        Mage::getModel('paypal/info')->importToPayment($api, $payment);
    }

    /**
     * Is capture request needed on this transaction
     *
     * @return true
     */
    protected function _isCaptureNeeded()
    {
        $this->_api->callGetTransactionDetails();
        if ($this->_api->isPaymentComplete()) {
            return false;
        }
        return true;
    }

    /**
     * Parent transaction id getter
     *
     * @param Varien_Object $payment
     * @return string
     */
    protected function _getParentTransactionId(Varien_Object $payment)
    {
        return $payment->getParentTransactionId();
    }
}
