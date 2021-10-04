<?php
namespace RedMonks\ImprovedLayeredNavigation\Plugin\Swatches;

use RedMonks\ImprovedLayeredNavigation\Helper\Data as LayerHelper;
use RedMonks\ImprovedLayeredNavigation\Model\Layer\Filter as FilterModel;

class RenderLayered
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
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $eavAttribute;

    /**
     * Item constructor.
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Theme\Block\Html\Pager $htmlPagerBlock
     * @param \Magento\Framework\App\RequestInterface $request
     * @param LayerHelper $_wpHelper
     * @param FilterModel $filterModel
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     */
    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        \Magento\Framework\App\RequestInterface $request,
        LayerHelper $wpHelper,
        FilterModel $filterModel,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
    ) {
        $this->_url = $url;
        $this->_htmlPagerBlock = $htmlPagerBlock;
        $this->_request = $request;
        $this->_wpHelper = $wpHelper;
        $this->_filterModel = $filterModel;
        $this->eavAttribute = $eavAttribute;
    }

    /**
     * @param \Magento\Swatches\Block\LayeredNavigation\RenderLayered $renderLayered
     * @param $proceed
     * @param string $attributeCode
     * @param int $optionId
     * @return string
     */
    public function aroundBuildUrl(
        \Magento\Swatches\Block\LayeredNavigation\RenderLayered $renderLayered,
        $proceed,
        $attributeCode,
        $optionId
    ) {
        if (!$this->_wpHelper->isEnabled() || $this->_wpHelper->isAjaxEnabled()) {
            return $proceed($attributeCode, $optionId);
        }

        $attributeId = $this->eavAttribute->getIdByCode('catalog_product', $attributeCode);
        $isMultiSelect = $this->_filterModel->isMultiselect($attributeId);

        if (!$isMultiSelect) {
            return $proceed($attributeCode, $optionId);
        }

        $filterValue = $this->_request->getParam($attributeCode);
        $requestVarValue = [];
        if (!empty($filterValue)) {
            $requestVarValue = explode(',', $filterValue);
        }

        if (in_array($optionId, $requestVarValue)) {
            $requestVarValue = array_diff($requestVarValue, [$optionId]);
        } else {
            array_push($requestVarValue, $optionId);
        }

        $requestVarValue = implode(",", $requestVarValue);

        $query = [
            $attributeCode => $requestVarValue,
            $this->_htmlPagerBlock->getPageVarName() => null
        ];

        return $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
    }
}
