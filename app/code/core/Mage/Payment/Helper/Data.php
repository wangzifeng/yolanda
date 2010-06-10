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
 * @package     Mage_Payment
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Payment module base helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_PAYMENT_METHODS = 'payment';

    /**
     * Retrieve method model object
     *
     * @param   string $code
     * @return  Mage_Payment_Model_Method_Abstract
     */
    public function getMethodInstance($code)
    {
        $key = self::XML_PATH_PAYMENT_METHODS.'/'.$code.'/model';
        $class = Mage::getStoreConfig($key);
        if (!$class) {
            Mage::throwException($this->__('Cannot load configuration for payment method "%s"', $code));
        }
        return Mage::getModel($class);
    }

    /**
     * Get and sort available payment methods for specified or current store
     *
     * array structure:
     *  $index => Varien_Simplexml_Element
     *
     * @param   mixed $store
     * @return  array
     */
    public function getStoreMethods($store = null, $quote = null)
    {
        $res = array();
        foreach ($this->getPaymentMethods($store) as $code => $methodConfig) {
            $prefix = self::XML_PATH_PAYMENT_METHODS . '/' . $code . '/';
            if (!$model = Mage::getStoreConfig($prefix . 'model', $store)) {
                continue;
            }
            $methodInstance = Mage::getModel($model);
            $methodInstance->setStore($store);
            if (!$methodInstance->isAvailable($quote)) {
                /* if the payment method cannot be used at this time */
                continue;
            }
            $sortOrder = (int)$methodInstance->getConfigData('sort_order', $store);
            $methodInstance->setSortOrder($sortOrder);
            $res[] = $methodInstance;
        }

        usort($res, array($this, '_sortMethods'));
        return $res;
    }

    protected function _sortMethods($a, $b)
    {
        if (is_object($a)) {
            return (int)$a->sort_order < (int)$b->sort_order ? -1 : ((int)$a->sort_order > (int)$b->sort_order ? 1 : 0);
        }
        return 0;
    }

    /**
     * Retreive payment method form html
     *
     * @param   Mage_Payment_Model_Abstract $method
     * @return  Mage_Payment_Block_Form
     */
    public function getMethodFormBlock(Mage_Payment_Model_Method_Abstract $method)
    {
        $block = false;
        $blockType = $method->getFormBlockType();
        if ($this->getLayout()) {
            $block = $this->getLayout()->createBlock($blockType);
            $block->setMethod($method);
        }
        return $block;
    }

    /**
     * Retrieve payment information block
     *
     * @param   Mage_Payment_Model_Info $info
     * @return  Mage_Core_Block_Template
     */
    public function getInfoBlock(Mage_Payment_Model_Info $info)
    {
        $blockType = $info->getMethodInstance()->getInfoBlockType();
        if ($this->getLayout()) {
            $block = $this->getLayout()->createBlock($blockType);
        }
        else {
            $className = Mage::getConfig()->getBlockClassName($blockType);
            $block = new $className;
        }
        $block->setInfo($info);
        return $block;
    }

    /**
     * Retrieve available billing agreement methods
     *
     * @return array
     */
    public function getBillingAgreementMethods()
    {
        $result = array();
        foreach ($this->getStoreMethods() as $method) {
            if ($method->canManageBillingAgreements()) {
                $result[] = $method;
            }
        }
        return $result;
    }

    /**
     * Retrieve all payment methods
     *
     * @param mixed $store
     * @return array
     */
    public function getPaymentMethods($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_PAYMENT_METHODS, $store);
    }

    /**
     * Retrieve all billing agreement methods (code and label)
     *
     * @return array
     */
    public function getAllBillingAgreementMethods()
    {
        $result = array();
        $interface = 'Mage_Payment_Model_Billing_Agreement_MethodInterface';
        foreach ($this->getPaymentMethods() as $code => $data) {
            if (!isset($data['model'])) {
                continue;
            }
            $method = Mage::app()->getConfig()->getModelClassName($data['model']);
            if (in_array($interface, class_implements($method))) {
                $result[$code] = $data['title'];
            }
        }
        return $result;
    }
}
