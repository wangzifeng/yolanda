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
 * @package     Mage_Paypal
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Source model for settlement reports schedule
 */
class Mage_Paypal_Model_System_Config_Source_FetchingSchedule
{
    public function toOptionArray()
    {
        $options = array(
            '1' => Mage::helper('paypal')->__('1 day'),
            '3' => Mage::helper('paypal')->__('3 days'),
            '7' => Mage::helper('paypal')->__('7 days'),
            '10' => Mage::helper('paypal')->__('10 days'),
            '14' => Mage::helper('paypal')->__('14 days'),
            '30' => Mage::helper('paypal')->__('30 days'),
            '40' => Mage::helper('paypal')->__('40 days'),
        );
        return $options;
    }
}
