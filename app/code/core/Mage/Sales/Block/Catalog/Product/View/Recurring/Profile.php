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
 * Recurring profile options for product view page
 *
 * @category   Mage
 * @package    Mage_Sales
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Sales_Block_Catalog_Product_View_Recurring_Profile extends Mage_Core_Block_Template
{
    /**
     * Current product recurring profile
     * @var Varien_Object
     */
    protected $_profile = null;

    /**
     * Assign block variables and prepare layout
     *
     * @return Mage_Core_Block_Template
     */
    protected function _prepareLayout()
    {
        $this->setCustomerName(Mage::helper('customer')->getCustomerName());
        if ($this->_getRecurringProfile() && $this->_getRecurringProfile()->getStartDateIsEditable()) {
            $this->setIsStartDateEditable(true);
        }
        return parent::_prepareLayout();
    }

    /**
     * JS Calendar html
     *
     * @return string
     */
    public function getDateHtml()
    {
        $calendar = $this->getLayout()
            ->createBlock('core/html_date')
            ->setId('recurring_start_date')
            ->setName('recurring_start_date')
            ->setImage($this->getSkinUrl('images/calendar.gif'))
            ->setFormat(Mage::app()->getLocale()->getDateStrFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
        return $calendar->getHtml();
    }

    /**
     * Getter for product instance
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _getProduct()
    {
        return Mage::registry('current_product');
    }

    /**
     * Getter for $_profile.
     *
     * @return Varien_Object
     */
    protected function _getRecurringProfile ()
    {
        if (!$this->_getProduct()->isRecurring()) {
            return null;
        }
        if ($this->_profile === null) {
            $this->_profile = new Varien_Object($this->_getProduct()->getRecurringProfile());
        }
        return $this->_profile;
    }

    /**
     * Disable block output when product doesn't supply recurring payments
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->_getRecurringProfile()) {
            return '';
        }
        return parent::_toHtml();
    }
}
