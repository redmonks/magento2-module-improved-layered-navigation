<?php
namespace RedMonks\ImprovedLayeredNavigation\Model\Layer\Filter;

use Magento\CatalogSearch\Model\Layer\Filter\Price as AbstractFilter;
use RedMonks\ImprovedLayeredNavigation\Helper\Data as LayerHelper;

class Price extends AbstractFilter
{
	/** @var \RedMonks\ImprovedLayeredNavigation\Helper\Data */
	protected $_moduleHelper;

	/** @var array|null Filter value */
	protected $_filterVal = null;

	/** @var \Magento\Tax\Helper\Data */
	protected $_taxHelper;

	/** @var \Magento\Catalog\Model\Layer\Filter\DataProvider\Price */
	private $dataProvider;

	/** @var \Magento\Framework\Pricing\PriceCurrencyInterface */
	private $priceCurrency;

	/**
	 * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param \Magento\Catalog\Model\Layer $layer
	 * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
	 * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource
	 * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Magento\Framework\Search\Dynamic\Algorithm $priceAlgorithm
	 * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
	 * @param \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory
	 * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory
	 * @param \Magento\Tax\Helper\Data $taxHelper
	 * @param \RedMonks\ImprovedLayeredNavigation\Helper\Data $moduleHelper
	 * @param array $data
	 */
	public function __construct(
		\Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Catalog\Model\Layer $layer,
		\Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
		\Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Framework\Search\Dynamic\Algorithm $priceAlgorithm,
		\Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
		\Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory,
		\Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory,
		\Magento\Tax\Helper\Data $taxHelper,
		LayerHelper $moduleHelper,
		array $data = []
	)
	{
		parent::__construct(
			$filterItemFactory,
			$storeManager,
			$layer,
			$itemDataBuilder,
			$resource,
			$customerSession,
			$priceAlgorithm,
			$priceCurrency,
			$algorithmFactory,
			$dataProviderFactory,
			$data
		);

		$this->priceCurrency = $priceCurrency;
		$this->dataProvider  = $dataProviderFactory->create(['layer' => $this->getLayer()]);
		$this->_moduleHelper = $moduleHelper;
		$this->_taxHelper    = $taxHelper;
	}

	/**
	 * @inheritdoc
	 */
	public function apply(\Magento\Framework\App\RequestInterface $request)
	{
		if (!$this->_moduleHelper->isEnabled()) {
			return parent::apply($request);
		}
		/**
		 * Filter must be string: $fromPrice-$toPrice
		 */
		$filter = $request->getParam($this->getRequestVar());
		if (!$filter || is_array($filter)) {
			return $this;
		}
		$filterParams = explode(',', $filter);
		$filter       = $this->dataProvider->validateFilter($filterParams[0]);
		if (!$filter) {
			return $this;
		}

		$this->dataProvider->setInterval($filter);
		$priorFilters = $this->dataProvider->getPriorFilters($filterParams);
		if ($priorFilters) {
			$this->dataProvider->setPriorIntervals($priorFilters);
		}

		list($from, $to) = $this->_filterVal = $filter;

		$this->getLayer()->getProductCollection()->addFieldToFilter(
			'price',
			['from' => $from, 'to' => $to]
		);

		$this->getLayer()->getState()->addFilter(
			$this->_createItem($this->_renderRangeLabel(empty($from) ? 0 : $from, $to), $filter)
		);

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	protected function _renderRangeLabel($fromPrice, $toPrice, $isLast = false)
	{
		if (!$this->_moduleHelper->isEnabled()) {
			return parent::_renderRangeLabel($fromPrice, $toPrice, $isLast = false);
		}
		$formattedFromPrice = $this->priceCurrency->format($fromPrice);
        if ($toPrice === '' || $isLast) {
			return __('%1 and above', $formattedFromPrice);
		} elseif ($fromPrice == $toPrice && $this->dataProvider->getOnePriceIntervalValue()) {
			return $formattedFromPrice;
		} else {
			return __('%1 - %2', $formattedFromPrice, $this->priceCurrency->format($toPrice));
		}
	}

	/**
	 * Price Slider Configuration
	 *
	 * @return array
	 */
	public function getSliderConfig()
	{
		/** @var \RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Fulltext\Collection $productCollection */
		$productCollection = $this->getLayer()->getProductCollection();

		if ($this->_filterVal) {
			/** @type \RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Fulltext\Collection $productCollectionClone */
			$productCollection = $productCollection->getCollectionClone()
				->removeAttributeSearch(['price.from', 'price.to']);
		}

		$min = $productCollection->getMinPrice();
		$max = $productCollection->getMaxPrice();

		list($from, $to) = $this->_filterVal ?: [$min, $max];
		$from = ($from < $min) ? $min : $from;
		$to = ($to > $max) ? $max : $to;

		$item = $this->getItems()[0];

		return [
			"selectedFrom" => $from,
			"selectedTo"   => $to,
			"minValue"     => $min,
			"maxValue"     => $max,
			"priceFormat"  => $this->_taxHelper->getPriceFormat(),
			"ajaxUrl"      => $item->getUrl()
		];
	}

	/**
	 * Get data array for building attribute filter items
	 *
	 * @return array
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	protected function _getItemsData()
	{
		if (!$this->_moduleHelper->isEnabled()) {
			return parent::_getItemsData();
		}

		$attribute         = $this->getAttributeModel();
		$this->_requestVar = $attribute->getAttributeCode();

		/** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
		$productCollection = $this->getLayer()->getProductCollection();

		if ($this->_filterVal) {
			/** @type \RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Fulltext\Collection $productCollectionClone */
			$productCollection = $productCollection->getCollectionClone()
				->removeAttributeSearch(['price.from', 'price.to']);
		}

		$facets = $productCollection->getFacetedData($attribute->getAttributeCode());

		$data = [];
		if (count($facets) > 1) {
            $lastFacet = array_key_last($facets);
			foreach ($facets as $key => $aggregation) {
				$count = $aggregation['count'];
				if (strpos($key, '_') === false) {
					continue;
				}
                $isLast = $lastFacet === $key;
				$data[] = $this->prepareData($key, $count, $isLast);
			}
		}

		return $data;
	}

	/**
	 * @param string $key
	 * @param int $count
     * @param boolean $isLast
	 * @return array
	 */
	private function prepareData($key, $count, $isLast = false)
	{
		list($from, $to) = explode('_', $key);
		if ($from == '*') {
			$from = $this->getFrom($to);
		}
		if ($to == '*') {
			$to = $this->getTo($to);
		}
		$label = $this->_renderRangeLabel(
			empty($from) ? 0 : $from * $this->getCurrencyRate(),
			empty($to) ? $to : $to * $this->getCurrencyRate(),
            $isLast
		);
		$value = $from . '-' . $to . $this->dataProvider->getAdditionalRequestData();

		$data = [
			'label' => $label,
			'value' => $value,
			'count' => $count,
			'from'  => $from,
			'to'    => $to,
		];

		return $data;
	}
}
