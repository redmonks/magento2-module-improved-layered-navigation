<?php

namespace RedMonks\ImprovedLayeredNavigation\Model\Layer;

use Magento\Framework\App\RequestInterface;
use RedMonks\ImprovedLayeredNavigation\Model\AttributeOptions;

class Filter
{
	/** @var \Magento\Framework\App\RequestInterface */
	protected $request;

    /**
     * @var AttributeOptions
     */
    protected $_wpAttributeOptions;

    /**
     * @var array
     */
    protected $_ids = [];

    /**
     * Filter constructor.
     * @param RequestInterface $request
     * @param AttributeOptions $attributeOptions
     */
	public function __construct(
	    RequestInterface $request,
        AttributeOptions $attributeOptions
    )
	{
		$this->request = $request;
        $this->_wpAttributeOptions = $attributeOptions;
	}

	/**
	 * Get option url. If it has been filtered, return removed url. Else return filter url
	 *
	 * @param \Magento\Catalog\Model\Layer\Filter\Item $item
	 * @return mixed
	 */
	public function getItemUrl($item)
	{
		if ($this->isSelected($item)) {
			return $item->getRemoveUrl();
		}

		return $item->getUrl();
	}

	/**
	 * @param \Magento\Catalog\Model\Layer\Filter\AbstractFilter $filter
	 * @param bool|true $explode
	 * @return array|mixed
	 */
	public function getFilterValue($filter, $explode = true)
	{
		$filterValue = $this->request->getParam($filter->getRequestVar());
		if (empty($filterValue)) {
			return [];
		}

		return $explode ? explode(',', $filterValue) : $filterValue;
	}


	/**
	 * Checks whether the option reduces the number of results
	 *
	 * @param \Magento\Catalog\Model\Layer\Filter\AbstractFilter $filter
	 * @param int $optionCount Count of search results with this option
	 * @param int $totalSize Current search results count
	 * @return bool
	 */
	public function isOptionReducesResults($filter, $optionCount, $totalSize)
	{
		$result = $optionCount <= $totalSize;

		if ($this->isShowZero($filter)) {
			return $result;
		}

		return $optionCount && $result;
	}

	/**
	 * @param \Magento\Catalog\Model\Layer\Filter\AbstractFilter $filter
	 * @return bool
	 */
	public function isShowZero($filter)
	{
		return false;
	}

    /**
     * @param $filter
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
	public function isMainFilter($filter) {
        if(empty($this->_ids)){
            $this->_ids = $this->getStateAttributesIds($filter);
        }
        $isMulti = false;
        if(count($this->_ids) <= 1) {
            $isMulti = true;
            if(!empty($this->_ids)) {
                $isMulti = $this->isMultiselect($this->_ids[0]);
            }
        }

        return $isMulti;
    }

    /**
     * @param $attrId
     */
    public function isMultiselect($attrId) {
        $wpLnAttributeOptions = ($attrId) ? $this->_wpAttributeOptions->getDisplayOptionsByAttribute($attrId) : false;
        $isMultiselect = ($wpLnAttributeOptions) ? $wpLnAttributeOptions->getIsMultiselect() : false;

        return $isMultiselect;
    }


    /**
     * @param Layer $layer
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getStateAttributesIds($filter)
    {
        $layer = $filter->getLayer();
        foreach ($layer->getState()->getFilters() as $filter) {
            if ($model = $filter->getFilter()->getData('attribute_model')) {
                $this->_ids[] = $model->getId();
            }
        }
        return array_unique($this->_ids);
    }
}
