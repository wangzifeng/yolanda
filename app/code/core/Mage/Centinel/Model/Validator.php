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
 * @category   Mage
 * @package    Mage_Centinel
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * 3D Secure Validation Model
 */
class Mage_Centinel_Model_Validator extends Varien_Object
{
    /**
     * States of validation
     *
     */
    const STATE_NEED_VALIDATION = 'no_validation';
    const STATE_AUTENTICATION_DISABLED = 'disabled';
    const STATE_AUTENTICATION_ENROLLED = 'enrolled';
    const STATE_AUTENTICATION_COMPLETE = 'complete';
    const STATE_AUTENTICATION_FAILED   = 'failed';

    /**
     * Validation api model
     *
     * @var Mage_Centinel_Model_Validator_Api
     */
    protected $_api;

    /**
     * Validation session object
     *
     * @var Mage_Centinel_Model_Validator_Session
     */
    protected $_session;

    /**
     * Code of payment method
     *
     * @var string
     */
    protected $_paymentMethodCode;

    /**
     * Return validation api model
     *
     * @return Mage_Centinel_Model_Validator_Api
     */
    protected function _getApi()
    {
        if (!is_null($this->_api)) {
            return $this->_api;
        }

        $this->_api = Mage::getSingleton('centinel/validator_api');
        $this->_api
           ->setProcessorId($this->_getConfig('processor_id'))
           ->setMerchantId($this->_getConfig('merchant_id'))
           ->setTransactionPwd(Mage::helper('core')->decrypt($this->_getConfig('password')))
           ->setIsTestMode((bool)(int)$this->_getConfig('test_mode'))
           ->setApiEndpointUrl($this->getCustomApiEndpointUrl());
        return $this->_api;
    }

    /**
     * Return value from section of centinel config
     *
     * @param string $path
     * @return string
     */
    protected function _getConfig($path)
    {
        return Mage::getStoreConfig('payment_services/centinel/' . $path, $this->getStore());
    }

    /**
     * Return validation session object
     *
     * @return Mage_Centinel_Model_Validator_Session
     */
    protected function _getSession()
    {
        if (!is_null($this->_session)) {
            return $this->_session;
        }
        $this->_session = Mage::getSingleton('centinel/validator_session');
        return $this->_session;
    }

    /**
     * Setter for data stored in session
     *
     * @param string|array $key
     * @param string $value
     * @return Mage_Centinel_Model_Validator
     */
    protected function _setDataStoredInSession($key, $value = null)
    {
        $key = $this->_paymentMethodCode . '_' . $key;
        $this->_getSession()->setData($key, $value);
        return $this;
    }

    /**
     * Getter for data stored in session
     *
     * @param string $key
     * @return string
     */
    protected function _getDataStoredInSession($key)
    {
        $key = $this->_paymentMethodCode . '_' . $key;
        return $this->_getSession()->getData($key);
    }

    /**
     * Generate checksum from all passed parameters
     *
     * @param string $cardNumber
     * @param string $cardExpMonth
     * @param string $cardExpYear
     * @param double $amount
     * @param string $currencyCode
     * @return string
     */
    protected function _generateChecksum($cardNumber, $cardExpMonth, $cardExpYear, $amount, $currencyCode)
    {
    	return md5(implode(func_get_args(), '_'));
    }
    
    /**
     * Unified validation/authentication URL getter
     *
     * @param string $suffix
     * @param bool $current
     * @return string
     */
    private function _getUrl($suffix, $current = false)
    {
        $request = (Mage::app()->getStore()->isAdmin() ? '*/centinel_authenticate/' : 'centinel/authenticate/') . $suffix;
        return Mage::getUrl($request, array(
            '_secure'  => true,
            '_current' => $current,
            'form_key' => Mage::getSingleton('core/session')->getFormKey(),
            'method'   => $this->getPaymentMethodCode())
        );
    }
    
    /**
     * Return URL for term response from Centinel
     *
     * @return string
     */
    public function getTermUrl()
    {
        return $this->_getUrl('result', true);
    }
    
    /**
     * Return URL for authentication
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return $this->_getUrl('redirect');
    }
    
    /**
     * Return URL for validation
     *
     * @return string
     */
    public function getValidationUrl()
    {
        return $this->_getUrl('validatedata');
    }
    
    /**
     * Export cmpi lookups information stored in session into array
     *
     * @param mixed $to
     * @param array $map
     * @return mixed $to
     */
    public function exportCmpi($to, array $map)
    {
        // collect available data intersected by requested map
        $data = array();
        $cmpiLookup = $this->_getDataStoredInSession('cmpi_lookup');
        if ($cmpiLookup && isset($cmpiLookup['enrolled'])) {
            $data = Varien_Object_Mapper::accumulateByMap($cmpiLookup, $data, array_keys($cmpiLookup));
            if ('Y' === $cmpiLookup['enrolled'] && $cmpiAuth = $this->_getDataStoredInSession('cmpi_authenticate')) {
                $data = Varien_Object_Mapper::accumulateByMap($cmpiAuth, $data, array_keys($cmpiAuth));
            }
        }
        return Varien_Object_Mapper::accumulateByMap($data, $to, $map);
    }
    
    /**
     * Return flag - is authentication enrolled
     *
     * @return bool
     */
    public function isAuthenticationEnrolled()
    {
    	return $this->getAuthenticationStatus() == self::STATE_AUTENTICATION_ENROLLED;
    }
    
    /**
     * Payment code setter
     *
     * @param string $value
     * @return Mage_Centinel_Model_Validator
     */
    public function setPaymentMethodCode($value)
    {
        $this->_paymentMethodCode = $value;
        return $this;
    }

    /**
     * Payment code getter
     *
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return $this->_paymentMethodCode;
    }

    /**
     * Process lookup validation
     *
     * @param Varien_Object $data
     * @return bool
     */
    public function lookup($data)
    {
        $api = $this->_getApi();
        $api->setCardNumber($data->getCardNumber())
            ->setCardExpMonth($data->getCardExpMonth())
            ->setCardExpYear($data->getCardExpYear())
            ->setAmount($data->getAmount())
            ->setCurrencyCode($data->getCurrencyCode())
            ->setOrderNumber($data->getOrderNumber())
            ->callLookup();

        $newChecksum = $this->_generateChecksum(
            $data->getCardNumber(), 
            $data->getCardExpMonth(), 
            $data->getCardExpYear(),
            $data->getAmount(), 
            $data->getCurrencyCode()
        );
            
        if (!$api->getErrorNo()) {
            $this->_setDataStoredInSession('cmpi_lookup', array(
                'eci_flag' => $api->getEciFlag(),
                'enrolled' => $api->getEnrolled(),
            ));
            if ($api->getEnrolled() == 'Y' && $api->getAcsUrl()) {
            	$this->setAuthenticationStatus(self::STATE_AUTENTICATION_ENROLLED)
                    ->setAcsUrl($api->getAcsUrl())
                    ->setPayload($api->getPayload())
                    ->setTransactionId($api->getTransactionId())
                    ->setControlSum($newChecksum);
                return true;
            }
        }
        $this->setAuthenticationStatus(self::STATE_AUTENTICATION_DISABLED)
            ->setControlSum($newChecksum);;
        return false;
    }

    /**
     * Process authenticate validation
     *
     * @param string $PaResPayload
     * @param string $MD
     * @return bool
     */
    public function authenticate($paResPayload, $MD)
    {
        $api = $this->_getApi();
        $api->setPaResPayload($paResPayload)
            ->setTransactionId($MD)
            ->callAuthentication();

        if ($api->getErrorNo() == 0 && $api->getSignature() == 'Y' && $api->getPaResStatus() != 'N') {
            $this->setAuthenticationStatus(self::STATE_AUTENTICATION_COMPLETE)
                ->_setDataStoredInSession('cmpi_authenticate', Varien_Object_Mapper::accumulateByMap($api, array(), array(
                    'eci_flag', 'pa_res_status', 'signature_verification', 'xid', 'cavv'
                )))
            ;
            return true;
        }

        $this->setAuthenticationStatus(self::STATE_AUTENTICATION_FAILED);
        return false;
    }

    /**
     * Validate payment data
     *
     * @param Varien_Object $data
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function validate($data)
    {
        if ($this->getIsValidationLock()) {
            return true;	
        }
        
    	$currentStatus = $this->getAuthenticationStatus(); 
    	$newChecksum = $this->_generateChecksum(
            $data->getCardNumber(), 
            $data->getCardExpMonth(), 
            $data->getCardExpYear(),
            $data->getAmount(), 
            $data->getCurrencyCode()
        );
    	
    	if ($this->getIsPlaceOrder()) {
	        if ($this->getControlSum() != $newChecksum) {
                Mage::throwException(Mage::helper('centinel')->__('Centinel validation is filed. Please check payment information and try again'));
	        }
	        if (($currentStatus == self::STATE_AUTENTICATION_COMPLETE) ||
	            ($currentStatus == self::STATE_AUTENTICATION_DISABLED && !$this->getIsValidationRequired()) ||
	            ($currentStatus == self::STATE_AUTENTICATION_FAILED && !$this->getIsAuthenticationRequired())) {
	            return true;
	        }
            Mage::throwException(Mage::helper('centinel')->__('Centinel validation is not complete'));
    	}
    	
        if ($this->getControlSum() != $newChecksum) {
            $this->reset();
            $this->lookup($data);
        } 
        
        if ($this->getAuthenticationStatus() == self::STATE_AUTENTICATION_ENROLLED) {
            return true;
        }

        if ($this->getAuthenticationStatus() == self::STATE_AUTENTICATION_DISABLED) {
            if ($this->getIsValidationRequired()) {
                Mage::throwException(Mage::helper('centinel')->__('Centinel validation is filed. Please check payment information and try again'));
            }
            return true;
        }

        return true;
    }

    /**
     * Reset data, api and state
     *
     * @return Mage_Centinel_Model_Validator
     */
    public function reset()
    {
        $this->_getSession()->setData(array());
        $this->_api = null;
        $this->setAuthenticationStatus(self::STATE_NEED_VALIDATION);
        return $this;
    }
    
    /**
     * Setter for ControlSum
     *
     * @param string $value
     * @return Mage_Centinel_Model_Validator
     */
    public function setControlSum($value)
    {
        return $this->_setDataStoredInSession('ControlSum', $value);
    }

    /**
     * Getter for ControlSum
     *
     * @return string
     */
    public function getControlSum()
    {
        return $this->_getDataStoredInSession('ControlSum');
    }

    /**
     * Setter for AuthenticationStatus
     *
     * @param string $value
     * @return Mage_Centinel_Model_Validator
     */
    public function setAuthenticationStatus($value){
        return $this->_setDataStoredInSession('authenticationStatus', $value);
    }

    /**
     * Getter for AuthenticationStatus
     *
     * @return string
     */
    public function getAuthenticationStatus()
    {
        if ($this->_getDataStoredInSession('authenticationStatus')) {
            return $this->_getDataStoredInSession('authenticationStatus');
        }
        return self::STATE_NEED_VALIDATION;
    }

    /**
     * Setter for AcsUrl
     *
     * @param string $value
     * @return Mage_Centinel_Model_Validator
     */
    public function setAcsUrl($value)
    {
        return $this->_setDataStoredInSession('AcsUrl', $value);
    }

    /**
     * Getter for AcsUrl
     *
     * @return string
     */
    public function getAcsUrl()
    {
        return $this->_getDataStoredInSession('AcsUrl');
    }

    /**
     * Setter for Payload
     *
     * @param string $value
     * @return Mage_Centinel_Model_Validator
     */
    public function setPayload($value)
    {
        return $this->_setDataStoredInSession('Payload', $value);
    }

    /**
     * Getter for Payload
     *
     * @return string
     */
    public function getPayload()
    {
        return $this->_getDataStoredInSession('Payload');
    }

    /**
     * Setter for TransactionId
     *
     * @param string $value
     * @return Mage_Centinel_Model_Validator
     */
    public function setTransactionId($value)
    {
        return $this->_setDataStoredInSession('TransactionId', $value);
    }

    /**
     * Getter for TransactionId
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->_getDataStoredInSession('TransactionId');
    }
}
