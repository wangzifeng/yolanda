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
 * Recurring profile info object that returns labels and values for its public properties
 */
class Mage_Payment_Model_Recurring_Profile_Info
{
    /**
     * Getter for label
     *
     * @param string $key
     * @return string
     */
    public function getLabel($key)
    {
        return $key;
    }

    /**
     * Getter for value
     *
     * @param string $key
     * @return string
     */
    public function getValue($key)
    {
        return $this->_renderValue($key, isset($this->$key) ? $this->$key : null);
    }

    /**
     * Filter value before getting
     *
     * @param string $key
     * @param string $value
     * @return string
     */
    protected function _renderValue($key, $value)
    {
        return $value;
    }
}
