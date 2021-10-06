<?php

namespace RedMonks\ImprovedLayeredNavigation\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class AttributeOptions extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'redmonks_attribute_options';
    const DISPLAY_OPTION_DEF_VAL = 0;
    const DISPLAY_OPTION_OPEN_VAL = 1;
    const VISIBLE_OPTIONS_DEF_VAL = 99;
    const VISIBLE_OPTIONS_STEP_DEF_VAL = 99;

    /**
     * @var string
     */
    protected $_cacheTag = 'redmonks_attribute_options';
    /**
     * @var string
     */
    protected $_eventPrefix = 'redmonks_attribute_options';

    protected function _construct()
    {
        $this->_init('RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\AttributeOptions');
    }

    /**
     * @return array|string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @param $attributeId
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDisplayOptionsByAttribute($attributeId) {
        $this->_getResource()->loadDisplayOptionsByAttribute($this, $attributeId);
        return $this;
    }
}
