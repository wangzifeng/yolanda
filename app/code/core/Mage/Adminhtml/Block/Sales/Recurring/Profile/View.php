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
 * Adminhtml sales order view
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Sales_Recurring_Profile_View extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        $this->_objectId    = 'recurring_profile_id';
        $this->_controller  = 'sales_recurring_profile';
        $this->_mode        = 'view';

        parent::__construct();

        $this->_removeButton('delete');
        $this->_removeButton('reset');
        $this->_removeButton('save');
        $this->setId('sales_recurring_profile_view');
    }

    /**
     * Retrieve order model object
     *
     * @return Mage_Sales_Model_Order
     */
    public function getRecurringProfile()
    {
        return Mage::registry('current_recurring_profile');
    }

    /**
     * Retrieve Order Identifier
     *
     * @return int
     */
    public function getRecurringProfileId()
    {
        return $this->getRecurringProfile()->getId();
    }

    public function getHeaderText()
    {
        return Mage::helper('sales')->__('Recurring Profile # %s', $this->getRecurringProfileId());
    }

    /**
     * Return back url for view grid
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }
}
