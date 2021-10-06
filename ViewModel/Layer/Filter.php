<?php
namespace RedMonks\ImprovedLayeredNavigation\ViewModel\Layer;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use RedMonks\ImprovedLayeredNavigation\Model\AttributeOptions;

class Filter implements ArgumentInterface
{
    /**
     * @var AttributeOptions
     */
    protected $_attributeOptions;
    /**
     * @var
     */
    protected $_attributeId;
    /**
     * @var
     */
    protected $_attributeOptionsObj;

    public function __construct(AttributeOptions $attributeOptions)
    {
        $this->_attributeOptions = $attributeOptions;
    }

    /**
     * @param $filter
     */
    public function setAttributeId($filter)
    {
        $this->_attributeId = ($filter->getRequestVar() != 'cat') ? $filter->getAttributeModel()->getAttributeId() : 0;
    }

    /**
     * Return wp attribute options
     *
     * @param $attributeId
     * @return mixed
     */
    public function getAttributeOptions()
    {
        return ($this->_attributeId > 0) ? $this->_attributeOptions->getDisplayOptionsByAttribute($this->_attributeId) : '';
    }

    /**
     * @return mixed
     */
    public function getAttributeId()
    {
        return $this->_attributeId;
    }

    /**
     * return the 'Is Multiselect' attribute configuration value
     *
     * @return mixed
     */
    public function getIsMultiSelect()
    {
        return ($this->_attributeId > 0) ? $this->getAttributeOptions($this->_attributeId)->getIsMultiselect() : '';
    }
}
