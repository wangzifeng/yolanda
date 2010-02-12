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
 * @package     Mage_Centinel
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Centinel Authenticate Controller
 *
 */
class Mage_Centinel_AuthenticateController extends Mage_Core_Controller_Front_Action
{
    /**
     * Process autenticate redirect action
     *
     */
	public function redirectAction()
	{
        $this->loadLayout();
        $block = $this->getLayout()->getBlock('root');
        
        $validator = $this->_getValidator();
        
        if ($validator && $validator->isAuthenticationEnrolled()) {
	        $block
	            ->setAcsUrl($validator->getAcsUrl())
	            ->setPayload($validator->getPayload())
	            ->setTermUrl($validator->getTermUrl())
	            ->setTransactionId($validator->getTransactionId())
	            ->setAuthenticationEnrolled(true);
        }
            
        $this->renderLayout();
	}

    /**
     * Process autenticate term action
     *
     */
	public function resultAction()
    {
        $this->loadLayout();
        $block = $this->getLayout()->getBlock('root');
        
        $request = $this->getRequest();
        $PAResPayload = $request->getParam('PaRes');
        $MD = $request->getParam('MD');

        if ($validator = $this->_getValidator()) {
	        $isRequired = $validator->getIsAuthenticationRequired();
	        if ($validator->authenticate($PAResPayload, $MD) || !$isRequired) {
	            $block->setResultMessage(Mage::helper('centinel')->__('Please continue.'));
	        } else {
	            $block->setResultMessage(Mage::helper('centinel')->__('Centinel validation is filed. Please check payment information and try again'));
	        }
        }
        
        $this->renderLayout();
    }

    /**
     * Return payment model
     *
     * @return Mage_Sales_Model_Quote_Payment
     */
    private function _getPayment()
    {
    	return Mage::getSingleton('checkout/session')->getQuote()->getPayment();
    }

    /**
     * Return Centinel validation model
     *
     * @return Mage_Centinel_Model_Validator
     */
    private function _getValidator()
    {
        if ($this->_getPayment()->getMethodInstance()->getIsCentinelValidationEnabled()) {
            return $this->_getPayment()->getMethodInstance()->getCentinelValidator();
        }
    	return false;
    }
}
