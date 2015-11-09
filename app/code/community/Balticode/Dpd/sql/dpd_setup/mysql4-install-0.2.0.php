<?php

$installer = $this;

$installer->startSetup();

/**
 * SQL Table for delivery restriction 
 * Data from CSV
 */
$installer->run("
DROP TABLE IF EXISTS `{$installer->getTable('balticode_dpd_delivery_price')}`;
CREATE TABLE `{$installer->getTable('balticode_dpd_delivery_price')}` (
    `id_dpd_delivery_price` int(11) NOT NULL AUTO_INCREMENT,
    `postcode` int(11) NOT NULL,
    `price` text NOT NULL,
    `free_from_price` text NULL,
    `carrier_id` text NOT NULL,
    `weight` text NULL,
    `height` text NULL,
    `width` text NULL,
    `depth` text NULL,
    `oversized_price` text NULL,
    `overweight_price` text NULL,
    `id_shop` int(11) NOT NULL,
    PRIMARY KEY (`id_dpd_delivery_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='DPD Delivery Price by Postcode';
");

/**
 * SQL Table for delivery points
 * Data from DPD API about Parcel Store places
 */
$installer->run("
DROP TABLE IF EXISTS `{$installer->getTable('balticode_dpd_delivery_point')}`;
CREATE TABLE `{$installer->getTable('balticode_dpd_delivery_point')}` (
    `id_dpd_delivery_points` int(11) NOT NULL AUTO_INCREMENT,
    `parcelshop_id` int(11) NOT NULL,
    `company` text NOT NULL,
    `city` text NOT NULL,
    `pcode` text NOT NULL,
    `street` text NOT NULL,
    `country` text NOT NULL,
    `email` text NOT NULL,
    `phone` text NOT NULL,
    `comment` text NOT NULL,
    `created_time` text NOT NULL,
    `update_time` text NOT NULL,
    `active` int(1) DEFAULT '1',
    `deleted` int(1) DEFAULT '0',
    PRIMARY KEY (`id_dpd_delivery_points`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='DPD Delivery Points Data';
");

$installer->endSetup();

/** 
 * Add Attribute to Order to save order options
 */
$setup = new Mage_Sales_Model_Resource_Setup('core_setup');
$setup->startSetup();
    $setup->addAttribute('order', 'dpd_delivery_options', array(
        'type' => 'varchar',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'default' => null,
    ));
    $setup->addAttribute('quote', 'dpd_delivery_options', array(
        'type' => 'varchar',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'default' => null,
    ));
$setup->endSetup();

$installer = new Mage_Sales_Model_Resource_Setup('core_setup');
$installer->startSetup();
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'package_height',
array(
    'label'                      => Mage::helper('adminhtml')->__('Height'),
    'group'                      => 'General',
    'type'                       => 'decimal',
    'input'                      => 'text',
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'user_defined'               => false,
    'required'                   => false,
    'visible'                    => true,
    'source'                     => null,
    'backend'                    => null,
    'searchable'                 => false,
    'visible_in_advanced_search' => false,
    'visible_on_front'           => false,
    'is_configurable'            => false,
    'is_html_allowed_on_front'   => false,
    'sort_order'                 => '5',
    'apply_to' => 'simple',  // Apply to simple product type
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'package_width',
array(
    'label'                      => Mage::helper('adminhtml')->__('Width'),
    'group'                      => 'General',
    'type'                       => 'decimal',
    'input'                      => 'text',
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'user_defined'               => false,
    'required'                   => false,
    'visible'                    => true,
    'source'                     => null,
    'backend'                    => null,
    'searchable'                 => false,
    'visible_in_advanced_search' => false,
    'visible_on_front'           => false,
    'is_configurable'            => false,
    'is_html_allowed_on_front'   => false,
    'sort_order'                 => '5',
    'apply_to' => 'simple',  // Apply to simple product type
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'package_depth',
array(
    'label'                      => Mage::helper('adminhtml')->__('Depth'),
    'group'                      => 'General',
    'type'                       => 'decimal',
    'input'                      => 'text',
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'user_defined'               => false,
    'required'                   => false,
    'visible'                    => true,
    'source'                     => null,
    'backend'                    => null,
    'searchable'                 => false,
    'visible_in_advanced_search' => false,
    'visible_on_front'           => false,
    'is_configurable'            => false,
    'is_html_allowed_on_front'   => false,
    'sort_order'                 => '5',
    'apply_to' => 'simple',  // Apply to simple product type
));
$installer->endSetup();
?>