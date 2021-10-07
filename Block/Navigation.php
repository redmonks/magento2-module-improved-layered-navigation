<?php

namespace RedMonks\ImprovedLayeredNavigation\Block;

use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;
use Magento\Catalog\Helper\Product\ProductList;

/**
 * Class Navigation
 * @package RedMonks\ImprovedLayeredNavigation\Block
 */
class Navigation extends \Magento\LayeredNavigation\Block\Navigation
{
    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_catalogLayer;

    /**
     * @var \Magento\Catalog\Model\Layer\FilterList
     */
    protected $filterList;

    /**
     * @var \Magento\Catalog\Model\Layer\AvailabilityFlagInterface
     */
    protected $visibilityFlag;

    /**
     * @var ProductList
     */
    protected $_productListHelper;

    /**
     * Default Order field
     *
     * @var string
     */
    protected $_orderField = null;

    /**
     * Default direction
     *
     * @var string
     */
    protected $_direction = ProductList::DEFAULT_SORT_DIRECTION;

    /**
     * @var \RedMonks\ImprovedLayeredNavigation\Helper\Data
     */
    protected $_wpHelper;

    /**
     * @var \RedMonks\ImprovedLayeredNavigation\Model\AttributeOptions
     */
    protected $_attributeOptions;

    /**
     * Navigation constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Catalog\Model\Layer\FilterList $filterList
     * @param \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag
     * @param ProductList $productListHelper
     * @param \RedMonks\ImprovedLayeredNavigation\Helper\Data $wpHelper
     * @param \RedMonks\ImprovedLayeredNavigation\Model\AttributeOptions $attributeOptions
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Model\Layer\FilterList $filterList,
        \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag,
        ProductList $productListHelper,
        \RedMonks\ImprovedLayeredNavigation\Helper\Data $wpHelper,
        \RedMonks\ImprovedLayeredNavigation\Model\AttributeOptions $attributeOptions,
        array $data = []
    )
    {
        $this->_catalogLayer = $layerResolver->get();
        $this->filterList = $filterList;
        $this->visibilityFlag = $visibilityFlag;
        $this->_productListHelper = $productListHelper;
        $this->_wpHelper = $wpHelper;
        $this->_attributeOptions = $attributeOptions;
        parent::__construct($context, $layerResolver, $filterList, $visibilityFlag, $data);
    }

    /**
     * @return mixed
     */
    public function isAjaxMode()
    {
        return $this->_wpHelper->isAjaxEnabled();
    }

    /**
     * check if current filter is a category filter
     *
     * @param $filter
     * @return bool
     */
    public function isCategoryFilter($filter)
    {
        return ($filter->getRequestVar() == 'cat') ? true : false;
    }

    /**
     * Return wp attribute options
     *
     * @param $attributeId
     * @return mixed
     */
    public function getWpAttributeOptions($attributeId)
    {
        return $this->_attributeOptions->getDisplayOptionsByAttribute($attributeId);
    }

    /**
     * set filter tab status
     *
     * @return string
     */
    public function getActiveFilters() {
        $filters = $this->getFilters();
        $activeFilters = [];
        $ctr = 0;
        foreach($filters as $k => $filter) {
            if($filter->getRequestVar() == 'cat') {
                if($filter->getItemsCount()) $ctr++;
                continue;
            } else {
                if($filter->getItemsCount()) {
                    $attributeId = $filter->getAttributeModel()->getAttributeId();
                    if($attributeId) {
                        $wpOptions = $this->getWpAttributeOptions($attributeId);
                        if($wpOptions->getData()) {
                            if($wpOptions->getDisplayOption() == '1' || $wpOptions->getDisplayOption() == '2') {
                                $activeFilters[] = $ctr;
                            }
                        }
                    }
                    $ctr++;
                }
            }
        }

        $activeFiltersStr = implode(' ', $activeFilters);

        return $activeFiltersStr;
    }

    /**
     * Return current URL with rewrites and additional parameters
     *
     * @param array $params Query parameters
     * @return string
     */
    public function getPagerUrl($params = [])
    {
        $urlParams = [];
        $urlParams['_current'] = true;
        $urlParams['_escape'] = false;
        $urlParams['_use_rewrite'] = true;
        $urlParams['_query'] = $params;
        return $this->getUrl('*/*/*', $urlParams);
    }

    /**
     * Retrieve widget options in json format
     *
     * @param array $customOptions Optional parameter for passing custom selectors from template
     * @return string
     */
    public function getWidgetOptionsJson(array $customOptions = [])
    {
        $defaultMode = $this->_productListHelper->getDefaultViewMode($this->getModes());
        $options = [
            'mode' => ToolbarModel::MODE_PARAM_NAME,
            'direction' => ToolbarModel::DIRECTION_PARAM_NAME,
            'order' => ToolbarModel::ORDER_PARAM_NAME,
            'limit' => ToolbarModel::LIMIT_PARAM_NAME,
            'modeDefault' => $defaultMode,
            'directionDefault' => $this->_direction ?: ProductList::DEFAULT_SORT_DIRECTION,
            'orderDefault' => $this->getOrderField(),
            'limitDefault' => $this->_productListHelper->getDefaultLimitPerPageValue($defaultMode),
            'url' => $this->getPagerUrl(),
        ];
        $options = array_replace_recursive($options, $customOptions);
        return json_encode(['productListToolbarForm' => $options]);
    }

    /**
     * Get order field
     *
     * @return null|string
     */
    protected function getOrderField()
    {
        if ($this->_orderField === null) {
            $this->_orderField = $this->_productListHelper->getDefaultSortField();
        }
        return $this->_orderField;
    }
}
