<?php
namespace RedMonks\ImprovedLayeredNavigation\Model\Layer\Filter;

use Magento\CatalogSearch\Model\Layer\Filter\Category as AbstractFilter;
use RedMonks\ImprovedLayeredNavigation\Helper\Data as LayerHelper;
use RedMonks\ImprovedLayeredNavigation\Model\Layer\Filter as LayerFilter;

class Category extends AbstractFilter
{
	/** @var \RedMonks\ImprovedLayeredNavigation\Helper\Data */
	protected $_moduleHelper;

	/** @var bool Is Filterable Flag */
	protected $_isFilter = false;

	/** @var \Magento\Framework\Escaper */
	private $escaper;

	/** @var  \Magento\Catalog\Model\Layer\Filter\DataProvider\Category */
	private $dataProvider;

    /**
     * Category constructor.
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $categoryDataProviderFactory
     * @param LayerHelper $moduleHelper
     * @param LayerFilter $layerFilter
     * @param array $data
     */
	public function __construct(
		\Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Catalog\Model\Layer $layer,
		\Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
		\Magento\Framework\Escaper $escaper,
		\Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $categoryDataProviderFactory,
		LayerHelper $moduleHelper,
        LayerFilter $layerFilter,
		array $data = []
	)
	{
		parent::__construct(
			$filterItemFactory,
			$storeManager,
			$layer,
			$itemDataBuilder,
			$escaper,
			$categoryDataProviderFactory,
			$data
		);

		$this->escaper       = $escaper;
		$this->_moduleHelper = $moduleHelper;
        $this->_layerFilter  = $layerFilter;
		$this->dataProvider  = $categoryDataProviderFactory->create(['layer' => $this->getLayer()]);
	}

	/**
	 * @inheritdoc
	 */
	public function apply(\Magento\Framework\App\RequestInterface $request)
	{
		if (!$this->_moduleHelper->isEnabled()) {
			return parent::apply($request);
		}

		$categoryId = $request->getParam($this->_requestVar);
		if (empty($categoryId)) {
			return $this;
		}

		$categoryIds = [];
		foreach (explode(',', $categoryId) as $key => $id) {
			$this->dataProvider->setCategoryId($id);
			if ($this->dataProvider->isValid()) {
				$category = $this->dataProvider->getCategory();
				if ($request->getParam('id') != $id) {
					$categoryIds[] = $id;
					$this->getLayer()->getState()->addFilter($this->_createItem($category->getName(), $id));
				}
			}
		}

		if (sizeof($categoryIds)) {
			$this->_isFilter = true;
			$this->getLayer()->getProductCollection()->addLayerCategoryFilter($categoryIds);
		}

		if ($parentCategoryId = $request->getParam('id')) {
			$this->dataProvider->setCategoryId($parentCategoryId);
		}

		return $this;
	}

    /**
     * @return array
     * @throws \Magento\Framework\Exception\StateException
     */
	protected function _getItemsData()
	{
		if (!$this->_moduleHelper->isEnabled()) {
			return parent::_getItemsData();
		}

		/** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
		$productCollection = $this->getLayer()->getProductCollection();

		if ($this->_isFilter) {
			$productCollection = $productCollection->getCollectionClone()
				->removeAttributeSearch('category_ids');
		}

		$optionsFacetedData = $productCollection->getFacetedData('category');
		$category           = $this->dataProvider->getCategory();
		$categories         = $category->getChildrenCategories();

		$collectionSize = $productCollection->getSize();

		if ($category->getIsActive()) {
			foreach ($categories as $category) {
				$count = isset($optionsFacetedData[$category->getId()]) ? $optionsFacetedData[$category->getId()]['count'] : 0;
				if ($category->getIsActive()
					&& $this->_layerFilter->isOptionReducesResults($this, $count, $collectionSize)
				) {
					$this->itemDataBuilder->addItemData(
						$this->escaper->escapeHtml($category->getName()),
						$category->getId(),
						$count
					);
				}
			}
		}

		return $this->itemDataBuilder->build();
	}
}
