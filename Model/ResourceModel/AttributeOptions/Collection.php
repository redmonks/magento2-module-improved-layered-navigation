<?php

namespace RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\AttributeOptions;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'redmonks_ln_atribute_options_collection';
    protected $_eventObject = 'attribute_options_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('RedMonks\ImprovedLayeredNavigation\Model\AttributeOptions', 'RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\AttributeOptions');
    }

}
