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
 * Recurring payment profiles resource model
 */
class Mage_Sales_Model_Mysql4_Recurring_Profile extends Mage_Sales_Model_Mysql4_Abstract
{
    /**
     * Initialize main table and column
     */
    protected function _construct()
    {
        $this->_init('sales/recurring_profile', 'profile_id');

        $this->_serializableFields = array(
            'order_item'      => array(new Varien_Object, new Varien_Object),
            'profile_info'    => array(null, new Mage_Payment_Model_Recurring_Profile_Info),
            'additional_info' => array(null, array()),
        );
    }

    /**
     * Return recurring profile child Orders Ids
     *
     * @param Mage_Sales_Model_Recurring_Profile
     * @return array
     */
    public function getChildOrderIds($object)
    {
        $select = $this->_getReadAdapter()->select()
            ->from(
                array('main_table' => $this->getTable('recurring_profile_order')),
                array('order_id'))
            ->where('profile_id=?', $object->getId());
        return $this->_getReadAdapter()->fetchCol($select);
    }
}
