<?php

namespace RedMonks\ImprovedLayeredNavigation\Block\Navigation;

use Magento\Framework\View\Element\Template;

/**
 * Class FilterRenderer
 * @package RedMonks\ImprovedLayeredNavigation\Block\Navigation
 */
class FilterRenderer extends \Magento\LayeredNavigation\Block\Navigation\FilterRenderer
{
    /**
     * @var \RedMonks\ImprovedLayeredNavigation\Helper\Data
     */
    protected $_wpHelper;

    /**
     * @var \RedMonks\ImprovedLayeredNavigation\Model\AttributeOptions
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

    /**
     * FilterRenderer constructor.
     * @param \RedMonks\ImprovedLayeredNavigation\Helper\Data $wpHelper
     * @param \RedMonks\ImprovedLayeredNavigation\Model\AttributeOptions $attributeOptions
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        \RedMonks\ImprovedLayeredNavigation\Helper\Data $wpHelper,
        \RedMonks\ImprovedLayeredNavigation\Model\AttributeOptions $attributeOptions,
        Template\Context $context,
        array $data = []
    )
    {
        $this->_wpHelper = $wpHelper;
        $this->_attributeOptions = $attributeOptions;

        parent::__construct($context, $data);
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
    public function getWpAttributeOptions()
    {
        $this->_attributeOptionsObj = ($this->_attributeId > 0) ? $this->_attributeOptions->getDisplayOptionsByAttribute($this->_attributeId) : '';

        return $this->_attributeOptionsObj;
    }

    /**
     * @return mixed
     */
    public function getAttributeId()
    {
        return $this->_attributeId;
    }

    /**
     * return the 'Visible Options' attribute configuration value
     *
     * @return mixed
     */
    public function getVisibleItems()
    {
        return ($this->_attributeId > 0) ? $this->getWpAttributeOptions($this->_attributeId)->getVisibleOptions() : '';
    }

    /**
     * return the 'Visible Options Step' attribute configuration value
     *
     * @return mixed
     */
    public function getVisibleItemsStep()
    {
        return ($this->_attributeId > 0) ? $this->getWpAttributeOptions($this->_attributeId)->getVisibleOptionsStep() : '';
    }

    /**
     * return the 'Show Qty' attribute configuration value
     *
     * @return mixed
     */
    public function getShowQty()
    {
        return ($this->_attributeId > 0) ? $this->getWpAttributeOptions($this->_attributeId)->getShowQuantity() : '';
    }

    /**
     * return the 'Is Multiselect' attribute configuration value
     *
     * @return mixed
     */
    public function getIsMultiSelect()
    {
        return ($this->_attributeId > 0) ? $this->getWpAttributeOptions($this->_attributeId)->getIsMultiselect() : '';
    }
}
