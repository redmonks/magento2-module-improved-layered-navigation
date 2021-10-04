<?php

namespace RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Search;

use Magento\Framework\Api\ObjectFactory;
use Magento\Framework\Api\Search\SearchCriteriaBuilder as SourceSearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;

class SearchCriteriaBuilder extends SourceSearchCriteriaBuilder
{
	/**
	 * @param ObjectFactory $objectFactory
	 * @param FilterGroupBuilder $filterGroupBuilder
	 * @param SortOrderBuilder $sortOrderBuilder
	 */
	public function __construct(
		ObjectFactory $objectFactory,
		FilterGroupBuilder $filterGroupBuilder,
		SortOrderBuilder $sortOrderBuilder
	)
	{
		parent::__construct($objectFactory, $filterGroupBuilder, $sortOrderBuilder);
	}

	/**
	 * @param $attributeCode
	 *
	 * @return $this
	 */
	public function removeFilter($attributeCode)
	{
		$this->filterGroupBuilder->removeFilter($attributeCode);

		return $this;
	}

    /**
     * @return SearchCriteriaBuilder
     */
	public function cloneObject()
	{
		$cloneObject = clone $this;
		$cloneObject->setFilterGroupBuilder($this->filterGroupBuilder->cloneObject());

		return $cloneObject;
	}

    /**
     * @param $filterGroupBuilder
     */
	public function setFilterGroupBuilder($filterGroupBuilder)
	{
		$this->filterGroupBuilder = $filterGroupBuilder;
	}

    /**
     * @return string
     */
	protected function _getDataObjectType()
	{
		return 'Magento\Framework\Api\Search\SearchCriteria';
	}
}
