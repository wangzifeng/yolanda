<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
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
 * @category    Enterprise
 * @package     Enterprise_Reward
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/* @var $installer Mage_Centinel_Model_Mysql4_Setup */
$installer = $this;

$installer->getConnection()->delete($installer->getTable('cms/page'), '`identifier` = \'centinel-verified-by-visa\'');

$now = new Zend_Date(time());
$now = $now->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
$installer->getConnection()->insert($installer->getTable('cms/page'), array(
    'title'           => 'Verified by Visa',
    'root_template'   => 'empty',
    'identifier'      => 'centinel-verified-by-visa',
    'content_heading' => 'Verified by Visa',
    'creation_time'   => $now,
    'update_time'     => $now,
    'is_active'       => 1,
    'content'         => "<h2 class=\"subtitle\">Get an extra layer of security when you shop online</h2>\r\n<p>In addition to our other ways of preventing, detecting, and resolving fraud, we offer Verified by Visa, a free, simple-to-use service that confirms your identity with an extra password when you make an online transaction.</p>\r\n<h2 class=\"subtitle\">How it works</h2>\r\n<ol class=\"ol\">\r\n    <li>\r\n        <p><strong>Shop at participating online merchants</strong><br />\r\n            Visit online merchants that display the Verified by Visa symbol; it's your assurance that your transaction will have an added layer of protection.</p>\r\n    </li>\r\n    <li>\r\n        <p><strong>Activate Verified by Visa While You Shop</strong><br />\r\n            You can enroll your card in the Verified by Visa program while shopping online. You may also be able to enroll and activate Verified by Visa through your issuer, please contact your issuer for details.</p>\r\n    </li>\r\n    <li>\r\n        <p><strong>Enjoy enhanced security</strong><br />\r\n            You can enjoy the peace of mind of Verified by Visa by activating both Visa credit and check cards</p>\r\n    </li>\r\n</ol>\r\n<h2 class=\"subtitle\">Activate Verified by Visa while you shop</h2>\r\n<p class=\"a-center\"><img src=\"{{skin url='images/centinel/v_activate_steps.gif'}}\" alt=\"Activate Verified by Visa\" title=\"Activate Verified by Visa\" /></p>\r\n<p>You may be prompted to activate your Visa card during the checkout process at one of our participating online merchants.</p>\r\n<p>It's quick and easy. If your issuer is participating in Verified by Visa you may be asked to complete a brief activation process. You will verify your identity, create your Verified by Visa password and you are done.</p>\r\n<p>Once your card is activated, your card number will be recognized whenever you shop at a participating VbV merchant. You will enter your password in the Verified by Visa window, your identity will be verified and the transaction will be completed. In stores that are not yet participating in Verified by Visa enabled, your Visa card will continue to work as usual.</p>",
));

$pageId = $installer->getConnection()->lastInsertId();
$installer->getConnection()->insert(
    $installer->getTable('cms/page_store'), array('page_id' => $pageId, 'store_id' => 0)
);

