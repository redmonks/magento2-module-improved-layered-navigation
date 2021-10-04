<?php
namespace RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Search;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\ObjectFactory;
use Magento\Framework\Api\Search\FilterGroup as SourceFilterGroup;
use Magento\Framework\Api\Search\FilterGroupBuilder as SourceFilterGroupBuilder;
use Magento\Framework\App\RequestInterface;


class FilterGroupBuilder extends SourceFilterGroupBuilder
{
	/** @var \Magento\Framework\App\RequestInterface */
	protected $_request;

	/**
	 * FilterGroupBuilder constructor.
	 * @param \Magento\Framework\Api\ObjectFactory $objectFactory
	 * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
	 * @param \Magento\Framework\App\RequestInterface $request
	 */
	public function __construct(
		ObjectFactory $objectFactory,
		FilterBuilder $filterBuilder,
		RequestInterface $request
	)
	{
		parent::__construct($objectFactory, $filterBuilder);

		$this->_request = $request;
	}

	/**
	 * @return FilterGroupBuilder
	 */
	public function cloneObject()
	{
		$cloneObject = clone $this;
		$cloneObject->setFilterBuilder(clone $this->_filterBuilder);

		return $cloneObject;
	}

	/**
	 * @param $filterBuilder
	 */
	public function setFilterBuilder($filterBuilder)
	{
		$this->_filterBuilder = $filterBuilder;
	}

	/**
	 * @param $attributeCode
	 *
	 * @return $this
	 */
	public function removeFilter($attributeCode)
	{
		if (isset($this->data[SourceFilterGroup::FILTERS])) {
			foreach ($this->data[SourceFilterGroup::FILTERS] as $key => $filter) {
				if ($filter->getField() == $attributeCode) {
					if (($attributeCode == 'category_ids') && ($filter->getValue() == $this->_request->getParam('id'))) {
						continue;
					}
					unset($this->data[SourceFilterGroup::FILTERS][$key]);
				}
			}
		}

		return $this;
	}

	/**
	 * Return the Data type class name
	 *
	 * @return string
	 */
	protected function _getDataObjectType()
	{
		return 'Magento\Framework\Api\Search\FilterGroup';
	}
}
