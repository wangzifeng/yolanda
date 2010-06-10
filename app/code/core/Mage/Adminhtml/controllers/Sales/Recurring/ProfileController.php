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
 * Adminhtml sales recurring profile controller
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Sales_Recurring_ProfileController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Recurring profiles list
     *
     * @return void
     */
    public function indexAction()
    {
        $this
            ->_title($this->__('Sales'))->_title($this->__('Recurring Profiles'))
            ->loadLayout()
            ->_setActiveMenu('sales/recurring_profile')
            ->renderLayout();

        return $this;
    }

    /**
     * View recurring profile detales
     */
    public function viewAction()
    {
        $this->_title($this->__('Sales'))->_title($this->__('Recurring Profiles'));

        if ($recurringProfile = $this->_initRecurringProfile()) {
            $this->loadLayout();
            $this->_title(sprintf("#%s", $recurringProfile->getProfileId()));
            $this->renderLayout();
        }
    }

    /**
     * Initialize recurring profile model instance
     *
     * @return Mage_Sales_Model_Recurring_Profile || false
     */
    protected function _initRecurringProfile()
    {
        $id = $this->getRequest()->getParam('recurring_profile_id');
        $recurringProfile = Mage::getModel('sales/recurring_profile')->load($id);

        if (!$recurringProfile->getProfileId()) {
            $this->_getSession()->addError($this->__('This recurring profile no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        Mage::register('current_recurring_profile', $recurringProfile);
        return $recurringProfile;
    }
}
