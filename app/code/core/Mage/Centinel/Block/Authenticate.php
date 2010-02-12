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
 * Centinel validation form lookup
 */

class Mage_Centinel_Block_Authenticate extends Mage_Core_Block_Template
{
    /**
     * Return url for payment authentication request
     *
     * @return string
     */
	public function getAuthenticationUrl()
    {
    	return $this->_getValidator()->getAuthenticationUrl();
    }
    
    /**
     * Return flag - is centinel validation enrolled 
     *
     * @return bool
     */
    public function isAuthenticationEnrolled()
    {
    	return $this->isValidationEnabled() && $this->_getValidator()->isAuthenticationEnrolled();
    }
    
    /**
     * Return flag - is centinel validation enabled 
     *
     * @return bool
     */
    public function isValidationEnabled()
    {
        return $this->_getValidator() != false;
    }
    
    /**
     * Return Centinel validation model
     *
     * @return Mage_Centinel_Model_Validator
     */
    private function _getValidator()
    {
        $instance = Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethodInstance();
        if ($instance->getIsCentinelValidationEnabled()) {
            return $instance->getCentinelValidator();
        }
        return false;
    }
    
}