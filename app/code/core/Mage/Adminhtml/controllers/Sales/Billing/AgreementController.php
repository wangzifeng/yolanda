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
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml billing agreement controller
 *
 * @author Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Sales_Billing_AgreementController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Billing agreements
     *
     */
    public function indexAction()
    {
        $this->_title($this->__('Sales'))
            ->_title($this->__('Billing Agreements'));

        $this->loadLayout()
            ->renderLayout();
    }

    /**
     * Ajax action for billing agreements
     *
     */
    public function gridAction()
    {
        $this->loadLayout(false)
            ->renderLayout();
    }

    /**
     * View billing agreement action
     *
     */
    public function viewAction()
    {
        $agreementModel = $this->_initBillingAgreement();

        if ($agreementModel) {
            $this->_title($this->__('Sales'))
                ->_title($this->__('Billing Agreements'))
                ->_title(sprintf("#%s", $agreementModel->getReferenceId()));

            Mage::register('billing_agreement', $agreementModel);
            $this->loadLayout()
                ->renderLayout();
            return;
        }

        $this->_redirect('*/*/');
        return;
    }

    /**
     * Related orders ajax action
     *
     */
    public function ordersGridAction()
    {
        $this->loadLayout(false)
            ->renderLayout();
    }

    /**
     * Cutomer billing agreements ajax action
     *
     */
    public function customerGridAction()
    {
        $this->_initCustomer();
        $this->loadLayout(false)
            ->renderLayout();
    }

    /**
     * Cancel billing agreement action
     *
     */
    public function cancelAction()
    {
        $agreementModel = $this->_initBillingAgreement();

        if ($agreementModel && $agreementModel->canCancel()) {
            $agreementModel->cancel();
            $this->_getSession()->addSuccess($this->__('The billing agreement has been canceled.'));
            $this->_redirect('*/*/view', array('_current' => true));
            return;
        }
        return $this->_redirect('*/*/');
    }

    /**
     * Initialize billing agreement by ID specified in request
     *
     * @return Mage_Sales_Model_Billing_Agreement | false
     */
    protected function _initBillingAgreement()
    {
        $agreementId = $this->getRequest()->getParam('agreement');
        $agreementModel = Mage::getModel('sales/billing_agreement')->load($agreementId);

        if (!$agreementModel->getId()) {
            $this->_getSession()->addError($this->__('Wrong billing agreement Id specified.'));
            return false;
        }
        return $agreementModel;
    }

    /**
     * Initialize customer by ID specified in request
     *
     * @return Mage_Adminhtml_Sales_Billing_AgreementController
     */
    protected function _initCustomer()
    {
        $customerId = (int) $this->getRequest()->getParam('id');
        $customer = Mage::getModel('customer/customer');

        if ($customerId) {
            $customer->load($customerId);
        }

        Mage::register('current_customer', $customer);
        return $this;
    }

    /**
     * Retrieve adminhtml session
     *
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
            case 'index':
            case 'grid' :
            case 'view' :
                return Mage::getSingleton('admin/session')->isAllowed('sales/billing_agreement/actions/view');
                break;
            case 'cancel':
                return Mage::getSingleton('admin/session')->isAllowed('sales/billing_agreement/actions/cancel');
                break;
            default:
                return Mage::getSingleton('admin/session')->isAllowed('sales/billing_agreement');
                break;
        }
    }
}
