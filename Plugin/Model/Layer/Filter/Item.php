<?php
namespace RedMonks\ImprovedLayeredNavigation\Plugin\Model\Layer\Filter;

use RedMonks\ImprovedLayeredNavigation\Helper\Data as LayerHelper;
use RedMonks\ImprovedLayeredNavigation\Model\Layer\Filter as FilterModel;

class Item
{
    /** @var \Magento\Framework\UrlInterface */
    protected $_url;

    /** @var \Magento\Theme\Block\Html\Pager */
    protected $_htmlPagerBlock;

    /** @var \Magento\Framework\App\RequestInterface */
    protected $_request;

    /** @var \RedMonks\ImprovedLayeredNavigation\Helper\Data */
    protected $_wpHelper;
    /**
     * @var FilterModel
     */
    protected $_filterModel;

    /**
     * Item constructor.
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Theme\Block\Html\Pager $htmlPagerBlock
     * @param \Magento\Framework\App\RequestInterface $request
     * @param LayerHelper $_wpHelper
     * @param FilterModel $filterModel
     */
    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        \Magento\Framework\App\RequestInterface $request,
        LayerHelper $wpHelper,
        FilterModel $filterModel
    )
    {
        $this->_url = $url;
        $this->_htmlPagerBlock = $htmlPagerBlock;
        $this->_request = $request;
        $this->_wpHelper = $wpHelper;
        $this->_filterModel = $filterModel;
    }

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\Item $item
     * @param $proceed
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __aroundGetUrl(\Magento\Catalog\Model\Layer\Filter\Item $item, $proceed)
    {
        if (!$this->_wpHelper->isEnabled()) {
            return $proceed();
        }

        $value = [];
        $filter = $item->getFilter();
        if ($this->_filterModel->isMultiple($filter)) {
            $requestVar = $filter->getRequestVar();
            if ($requestValue = $this->_request->getParam($requestVar)) {
                $value = explode(',', $requestValue);
            }
            if (!in_array($item->getValue(), $value)) {
                $value[] = $item->getValue();
            }
        }

        if (sizeof($value)) {
            $query = [
                $filter->getRequestVar() => implode(',', $value),
                $this->_htmlPagerBlock->getPageVarName() => null,
            ];

            return $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
        }

        return $proceed();
    }

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\Item $item
     * @param $proceed
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundGetRemoveUrl(\Magento\Catalog\Model\Layer\Filter\Item $item, $proceed)
    {
        if (!$this->_wpHelper->isEnabled()) {
            return $proceed();
        }

        $value = [];
        $filter = $item->getFilter();

        $value = $this->_filterModel->getFilterValue($filter);
        $itemValue = $item->getValue();
        if (is_array($item->getValue())) {
            $itemValue = implode('-', $item->getValue());
        }
        if (in_array($itemValue, $value)) {
            $value = array_diff($value, [$itemValue]);
        }

        $params['_query'] = [$filter->getRequestVar() => count($value) ? implode(',', $value) : $filter->getResetValue()];
        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_escape'] = true;

        return $this->_url->getUrl('*/*/*', $params);
    }

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\Item $item
     * @param $proceed
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundGetUrl(\Magento\Catalog\Model\Layer\Filter\Item $item, $proceed)
    {
        if (!$this->_wpHelper->isEnabled() || $this->_wpHelper->isAjaxEnabled()) {
            return $proceed();
        }

        $filter = $item->getFilter();
        if ($filter instanceof \RedMonks\ImprovedLayeredNavigation\Model\Layer\Filter\Category) {
            $isMultiSelect = false;
        } else {
            $isMultiSelect = $this->_filterModel->isMultiselect($filter->getAttributeModel()->getAttributeId());
        }

        if (!$isMultiSelect) {
            return $proceed();
        }

        $requestVarValue = $this->_filterModel->getFilterValue($filter);

        if (in_array($item->getValue(), $requestVarValue)) {
            $requestVarValue = array_diff($requestVarValue, [$item->getValue()]);
        } else {
            array_push($requestVarValue, $item->getValue());
        }

        $requestVarValue = implode(",", $requestVarValue);

        $query = [
            $filter->getRequestVar() => $requestVarValue,
            $this->_htmlPagerBlock->getPageVarName() => null,
        ];
        return $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
    }
}
