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
 * Adminhtml sales recurring profiles grid
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Sales_Recurring_Profile_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_recurring_profile_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Retrieve collection class
     *
     * @return string
     */
    protected function _getCollectionClass()
    {
        return 'sales/recurring_profile_collection';
    }

    /**
     * Prepare grid collection object
     *
     * @return Mage_Adminhtml_Block_Sales_Recurring_Profile_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @return Mage_Adminhtml_Block_Sales_Recurring_Profile_Grid
     */
    protected function _prepareColumns()
    {

        $_recurringProfileModel = Mage::getSingleton('sales/recurring_profile');
        
        $this->addColumn('profile_id', array(
            'header'=> $_recurringProfileModel->getFieldLabel('profile_id'),
            'width' => '80px',
            'type'  => 'text',
            'index' => 'profile_id',
        ));

        $this->addColumn('internal_reference_id', array(
            'header' => $_recurringProfileModel->getFieldLabel('internal_reference_id'),
            'index' => 'internal_reference_id',
        ));
        
        $this->addColumn('method_code', array(
            'header' => $_recurringProfileModel->getFieldLabel('method_code'),
            'index' => 'method_code',
        ));
        
        $this->addColumn('reference_id', array(
            'header' => $_recurringProfileModel->getFieldLabel('reference_id'),
            'index' => 'reference_id',
        ));
        
        $this->addColumn('state', array(
            'header' => $_recurringProfileModel->getFieldLabel('state'),
            'index' => 'state',
            'type'  => 'options',
            'width' => '70px',
            'options' => $_recurringProfileModel->getAllStates(),
        ));
        
        $this->addColumn('created_at', array(
            'header' => $_recurringProfileModel->getFieldLabel('created_at'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
        ));
        
        $this->addColumn('updated_at', array(
            'header' => $_recurringProfileModel->getFieldLabel('updated_at'),
            'index' => 'updated_at',
            'type' => 'datetime',
            'width' => '100px',
        ));
        
        $this->addColumn('schedule_description', array(
            'header' => $_recurringProfileModel->getFieldLabel('schedule_description'),
            'index' => 'schedule_description',
        ));

        return parent::_prepareColumns();
    }

    /**
     * Return row url for js event handlers
     *
     * @param Varien_Object
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/sales_recurring_profile/view', array('recurring_profile_id' => $row->getId()));
    }

    /**
     * Return grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
