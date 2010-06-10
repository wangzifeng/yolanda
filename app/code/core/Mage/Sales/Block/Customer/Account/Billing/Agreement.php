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
 * @package     Mage_Sales
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Customer account billing agreements block
 *
 * @author Magento Core Team <core@magentocommerce.com>
 */
class Mage_Sales_Block_Customer_Account_Billing_Agreement extends Mage_Core_Block_Template
{
    /**
     * Billing Agreement instance
     *
     * @var Mage_Sales_Model_Billing_Agreement
     */
    protected $_billingAgreementInstance = null;

    /**
     * Payment methods array
     *
     * @var array
     */
    protected $_paymentMethods = array();

    /**
     * Set Billing Agreement instance
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        if (is_null($this->_billingAgreementInstance)) {
            $this->_billingAgreementInstance = Mage::registry('current_billing_agreement');
        }
        return parent::_prepareLayout();
    }

    /**
     * Retrieve billing agreements collection
     *
     * @return Mage_Sales_Model_Mysql4_Billing_Agreement_Collection
     */
    public function getBillingAgreements()
    {
        $agreementsCollection = Mage::getResourceModel('sales/billing_agreement_collection');
        $agreementsCollection
            ->addFieldToFilter('customer_id', Mage::getSingleton('customer/session')->getCustomerId())
            ->setOrder('created_at');
        return $agreementsCollection;
    }

    /**
     * Retrieve item value by key
     *
     * @param Varien_Object $item
     * @param string $key
     * @return mixed
     */
    public function getItemValue(Mage_Sales_Model_Billing_Agreement $item, $key)
    {
        switch ($key) {
            case 'created_at':
            case 'updated_at':
                $value = ($item->getData($key))
                    ? $this->helper('core')->formatDate($item->getData($key), 'short', true) : $this->__('N/A');
                break;
            case 'edit_url':
                $value = $this->getUrl('*/customer_billing_agreement/view', array('agreement' => $item->getAgreementId()));
                break;
            case 'payment_method_label':
                $this->_loadPaymentMethods();
                $value = isset($this->_paymentMethods[$item->getMethodCode()])
                    ? $this->_paymentMethods[$item->getMethodCode()] : $this->__('N/A');
                break;
            case 'status':
                $value = $item->getStatusLabel();
                break;
            default:
                $value = ($item->getData($key)) ? $item->getData($key) : $this->__('N/A');
        }
        return $this->escapeHtml($value);
    }

    /**
     * Load available billing agreement methods
     *
     * @return array
     */
    protected function _loadPaymentMethods()
    {
        if (!$this->_paymentMethods) {
            foreach ($this->helper('payment')->getBillingAgreementMethods() as $paymentMethod) {
                if ($paymentMethod->getConfigData('allow_billing_agreement_wizard') == 1) {
                    $this->_paymentMethods[$paymentMethod->getCode()] = $paymentMethod->getTitle();
                }
            }
        }
        return $this->_paymentMethods;
    }

    /**
     * Set data to block
     *
     * @return string
     */
    protected function _toHtml()
    {
        $this->_loadPaymentMethods();
        $this->setPaymentMethodOptions($this->_paymentMethods);
        $this->setCreateUrl($this->getUrl('*/customer_billing_agreement/startWizard'));
        $this->setBackUrl($this->getUrl('*/customer_billing_agreement/'));
        if ($this->_billingAgreementInstance) {
            $this->setReferenceId($this->_billingAgreementInstance->getReferenceId());

            $this->setCanCancel($this->_billingAgreementInstance->canCancel());
            $this->setCancelUrl(
                $this->getUrl('*/customer_billing_agreement/cancel', array(
                    '_current' => true,
                    'payment_method' => $this->_billingAgreementInstance->getMethodCode()))
            );

            $this->setPaymentMethodTitle(
                $this->_paymentMethods[$this->_billingAgreementInstance->getMethodCode()]
            );
            $this->setAgreementCreatedAt(
                $this->getItemValue($this->_billingAgreementInstance, 'created_at')
            );
            if ($this->_billingAgreementInstance->getUpdatedAt()) {
                $this->setAgreementUpdatedAt(
                    $this->getItemValue($this->_billingAgreementInstance, 'updated_at')
                );
            }
            $this->setAgreementStatus($this->_billingAgreementInstance->getStatusLabel());
        }

        return parent::_toHtml();
    }
}
