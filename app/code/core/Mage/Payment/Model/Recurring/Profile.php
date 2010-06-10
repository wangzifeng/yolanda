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
 * Recurring payment profile
 * Extends from Mage_Core_Abstract for a reason: to make descendants have its own resource
 */
class Mage_Payment_Model_Recurring_Profile extends Mage_Core_Model_Abstract
{
    /**
     * Errors collected during validation
     *
     * @var array
     */
    protected $_errors = array();

    /**
     *
     * @var Mage_Payment_Model_Method_Abstract
     */
    protected $_methodInstance = null;

    /**
     * Check whether the object data is valid
     * Returns true if valid.
     *
     * @return bool
     */
    public function isValid()
    {
        $this->_filterValues();
        $this->_errors = array();

        // start date, order ref ID, schedule description
        if (!$this->getStartDatetime()) {
// TODO: validate format?
            $this->_errors['start_datetime'][] = Mage::helper('payment')->__('Start date is undefined.');
        }
        if (!$this->getScheduleDescription()) {
            $this->_errors['schedule_description'][] = Mage::helper('payment')->__('Schedule description must be not empty.');
        }

        // period unit and frequency, trial period unit and trial frequency
        if (!$this->getPeriodUnit() || !in_array($this->getPeriodUnit(), $this->getAllPeriodUnits(false), true)) {
            $this->_errors['period_unit'][] = Mage::helper('payment')->__('Billing period unit is not defined or wrong.');
        } elseif ($this->getPeriodFrequency()) {
            $this->_validatePeriodFrequency('period_unit', 'period_frequency');
        }
        if ($this->getTrialPeriodUnit()) {
            if (!in_array($this->getTrialPeriodUnit(), $this->getAllPeriodUnits(false), true)) {
                $this->_errors['trial_period_unit'][] = Mage::helper('payment')->__('Trial billing period unit is wrong.');
            } elseif ($this->getTrialPeriodFrequency()) {
                $this->_validatePeriodFrequency('trial_period_unit', 'trial_period_frequency');
            }
        }

        // billing and other amounts
        if (!$this->getBillingAmount() || 0 >= $this->getBillingAmount()) {
            $this->_errors['billing_amount'][] = Mage::helper('payment')->__('Wrong or empty billing amount specified.');
        }
        foreach (array('trial_billing_abount', 'shipping_amount', 'tax_amount', 'init_amount') as $key) {
            if ($this->hasData($key) && 0 >= $this->getData($key)) {
                $this->_errors[$key][] = Mage::helper('payment')->__('Wrong %s specified.', $this->getFieldLabel($key));
            }
        }

        // currency code
        if (!$this->getCurrencyCode()) {
            $this->_errors['currency_code'][] = Mage::helper('payment')->__('Currency code is undefined.');
        }

        // payment method
        if (!$this->_methodInstance || !$this->getMethodCode()) {
            $this->_errors['method_code'][] = Mage::helper('payment')->__('Payment method code is undefined.');
        }
        if ($this->_methodInstance) {
            try {
                $this->_methodInstance->validateRecurringProfile($this);
            } catch (Mage_Core_Exception $e) {
                $this->_errors['payment_method'][] = $e->getMessage();
            }
        }

        return empty($this->_errors);
    }

    /**
     * Getter for errors that may appear after validation
     *
     * @param bool $isGrouped
     * @return array
     */
    public function getValidationErrors($isGrouped = true, $asMessage = false)
    {
        if ($isGrouped && $this->_errors) {
            $result = array();
            foreach ($this->_errors as $row) {
                $result[] = implode(' ', $row);
            }
            if ($asMessage) {
                return Mage::throwException(
                    Mage::helper('payment')->__("Payment profile is invalid:\n%s", implode("\n", $result))
                );
            }
            return $result;
        }
        return $this->_errors;
    }

    /**
     * Setter for payment method instance
     *
     * @param Mage_Payment_Model_Method_Abstract $object
     * @return Mage_Payment_Model_Recurring_Profile
     */
    public function setMethodInstance(Mage_Payment_Model_Method_Abstract $object)
    {
        $this->_methodInstance = $object;
        return $this;
    }

    /**
     * Import product recurring profile information
     * Returns false if it cannot be imported
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Payment_Model_Recurring_Profile|false
     */
    public function importProduct(Mage_Catalog_Model_Product $product)
    {
        if ($product->isRecurring() && is_array($product->getRecurringProfile())) {
            $this->addData($product->getRecurringProfile());
            if (!$this->hasScheduleDescription()) {
                $this->setScheduleDescription($product->getName());
            }
            if ($options = $product->getCustomOption('recurring_profile_options')) {
                $options = @unserialize($options->getValue());
                if (isset($options['start_date'])) {
                    $startDatetime = new Zend_Date($options['start_date'], Varien_Date::DATETIME_INTERNAL_FORMAT);
                    $this->setNearestStartDatetime($startDatetime);
                }
                if (isset($options['subscriber_name'])) {
                    $this->setSubscriberName($options['subscriber_name']);
                }
            } else {
                $this->setNearestStartDatetime();
            }
            return $this->_filterValues();
        }
        return false;
    }

    /**
     * Determine nearest possible profile start date
     *
     * @param Zend_Date $minAllowed
     * @return Mage_Payment_Model_Recurring_Profile
     */
    public function setNearestStartDatetime(Zend_Date $minAllowed = null)
    {
        // TODO: implement proper logic with invoking payment method instance
        if ($minAllowed) {
            $date = $minAllowed;
        } else {
            $date = new Zend_Date(time());
        }
        $this->setStartDatetime($date->toString(Varien_Date::DATETIME_INTERNAL_FORMAT));
        return $this;
    }

    /**
     * Getter for available period units
     *
     * @param bool $withLabels
     * @return array
     */
    public function getAllPeriodUnits($withLabels = true)
    {
        if ($withLabels) {
            return array(
                'day'        => Mage::helper('payment')->__('Day'),
                'week'       => Mage::helper('payment')->__('Week'),
                'semi_month' => Mage::helper('payment')->__('Two Weeks'),
                'month'      => Mage::helper('payment')->__('Month'),
                'year'       => Mage::helper('payment')->__('Year'),
            );
        }
        return array('day', 'week', 'semi_month', 'month', 'year');
    }

    /**
     * Getter for field label
     *
     * @param string $field
     * @return string|null
     */
    public function getFieldLabel($field)
    {
        switch ($field) {
            case 'subscriber_name':
                return Mage::helper('payment')->__('Subscriber Name');
            case 'start_datetime':
                return Mage::helper('payment')->__('Start Date');
            case 'internal_reference_id':
                return Mage::helper('payment')->__('Internal Reference ID');
            case 'schedule_description':
                return Mage::helper('payment')->__('Schedule Description');
            case 'suspension_threshold':
                return Mage::helper('payment')->__('Maximum Payment Failures');
            case 'bill_failed_later':
                return Mage::helper('payment')->__('Auto Bill on Next Cycle');
            case 'period_unit':
                return Mage::helper('payment')->__('Billing Period Unit');
            case 'period_frequency':
                return Mage::helper('payment')->__('Billing Frequency');
            case 'period_max_cycles':
                return Mage::helper('payment')->__('Maximum Billing Cycles');
            case 'billing_amount':
                return Mage::helper('payment')->__('Billing Amount');
            case 'trial_period_unit':
                return Mage::helper('payment')->__('Trial Billing Period Unit');
            case 'trial_period_frequency':
                return Mage::helper('payment')->__('Trial Billing Frequency');
            case 'trial_period_max_cycles':
                return Mage::helper('payment')->__('Maximum Trial Billing Cycles');
            case 'trial_billing_amount':
                return Mage::helper('payment')->__('Trial Billing Amount');
            case 'currency_code':
                return Mage::helper('payment')->__('Currency');
            case 'shipping_amount':
                return Mage::helper('payment')->__('Shipping Amount');
            case 'tax_amount':
                return Mage::helper('payment')->__('Tax Amount');
            case 'init_amount':
                return Mage::helper('payment')->__('Initial Fee');
            case 'init_may_fail':
                return Mage::helper('payment')->__('Allow Initial Fee Failure');
            case 'method_code':
                return Mage::helper('payment')->__('Payment Method');
            case 'reference_id':
                return Mage::helper('payment')->__('External Reference ID');
        }
    }

    /**
     * Getter for field comments
     *
     * @param string $field
     * @return string|null
     */
    public function getFieldComment($field)
    {
        switch ($field) {
            case 'subscriber_name':
                return Mage::helper('payment')->__('Full name of the person receiving the product or service paid for by the recurring payment.');
            case 'start_datetime':
                return Mage::helper('payment')->__('The date when billing for the profile begins.');
            case 'schedule_description':
                return Mage::helper('payment')->__('Short description of the recurring payment. By default equals to the product name.');
            case 'suspension_threshold':
                return Mage::helper('payment')->__('The number of scheduled payments that can fail before the profile is automatically suspended.');
            case 'bill_failed_later':
                return Mage::helper('payment')->__('Automatically bill the outstanding balance amount in the next billing cycle (if there were failed payments).');
            case 'period_unit':
                return Mage::helper('payment')->__('Unit for billing during the subscription period.');
            case 'period_frequency':
                return Mage::helper('payment')->__('Number of billing periods that make up one billing cycle.');
            case 'period_max_cycles':
                return Mage::helper('payment')->__('The number of billing cycles for payment period.');
            case 'init_amount':
                return Mage::helper('payment')->__('Initial non-recurring payment amount due immediately upon profile creation.');
            case 'init_may_fail':
                return Mage::helper('payment')->__('Whether to suspend the payment profile if the initial fee fails or add it to the outstanding balance.');
        }
    }

    /**
     * Filter self data to make sure it can be validated properly
     *
     * @return Mage_Payment_Model_Recurring_Profile
     */
    protected function _filterValues()
    {
        // determine payment method/code
        if ($this->_methodInstance) {
            $this->setMethodCode($this->_methodInstance->getCode());
        }
        elseif ($this->getMethodCode()) {
            $this->_initMethodInstance();
        }

        // unset redundant values, if empty
        foreach (array('schedule_description',
            'suspension_threshold', 'bill_failed_later', 'period_frequency', 'period_max_cycles', 'reference_id',
            'trial_period_unit', 'trial_period_frequency', 'trial_period_max_cycles', 'init_may_fail') as $key) {
            if ($this->hasData($key) && (!$this->getData($key) || '0' == $this->getData($key))) {
                $this->unsetData($key);
            }
        }

        // cast amounts
        foreach (array(
            'billing_amount', 'trial_billing_amount', 'shipping_amount', 'tax_amount', 'init_amount') as $key) {
            if ($this->hasData($key)) {
                if (!$this->getData($key) || 0 == $this->getData($key)) {
                    $this->unsetData($key);
                } else {
                    $this->setData($key, sprintf('%.4F', $this->getData($key)));
                }
            }
        }

        return $this;
    }

    /**
     * Initialize payment method instance from code
     *
     * @param int $storeId
     */
    protected function _initMethodInstance($storeId = null)
    {
        if (!$this->_methodInstance) {
            $this->setMethodInstance(Mage::helper('payment')->getMethodInstance($this->getMethodCode()));
        }
        if ($storeId) {
            $this->_methodInstance->setStore($storeId);
        }
    }

    /**
     * Check accordance of the unit and frequency
     *
     * @param string $unitKey
     * @param string $frequencyKey
     */
    protected function _validatePeriodFrequency($unitKey, $frequencyKey)
    {
// TODO: implement
        // check accordance of the unit and frequency
                // $this->_errors
        // if set, invoke payment method instance?
    }

    /**
     * Perform full validation before saving
     *
     * @throws Mage_Core_Exception
     */
    protected function _validateBeforeSave()
    {
        if (!$this->isValid()) {
            Mage::throwException($this->getValidationErrors(true, true));
        }
        if (!$this->getInternalReferenceId()) {
            Mage::throwException(
                Mage::helper('payment')->__('An internal reference ID is required to save the payment profile.')
            );
        }
    }

    /**
     * Validate before saving
     *
     * @return Mage_Payment_Model_Recurring_Profile
     */
    protected function _beforeSave()
    {
        $this->_validateBeforeSave();
        return parent::_beforeSave();
    }
}
