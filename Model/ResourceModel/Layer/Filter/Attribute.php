<?php
namespace RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Layer\Filter;

use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Framework\DB\Select;

class Attribute extends \Magento\Catalog\Model\ResourceModel\Layer\Filter\Attribute
{

    /**
     * Retrieve array with products counts per attribute option
     *
     * @param FilterInterface $filter
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return array
     */
    public function getCount(FilterInterface $filter)
    {
        // clone select from collection with filters
        $layer = $filter->getLayer();
        if($layer instanceof \Magento\Catalog\Model\Layer\Search) {
            $collectionSelect = $layer->getProductCollection()->getSelect();
        } else {
            $collectionSelect = $layer->getCurrentCategory()->getProductCollection()->getSelect();
        }

        $select = clone $collectionSelect;
        // reset columns, order and limitation conditions
        $select->reset(Select::COLUMNS);
        $select->reset(Select::ORDER);
        $select->reset(Select::LIMIT_COUNT);
        $select->reset(Select::LIMIT_OFFSET);

        $connection = $this->getConnection();
        $attribute = $filter->getAttributeModel();
        $tableAlias = sprintf('%s_idx', $attribute->getAttributeCode());
        $conditions = [
            "{$tableAlias}.entity_id = e.entity_id",
            $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("{$tableAlias}.store_id = ?", $filter->getStoreId()),
        ];

        $select->join(
            [$tableAlias => $this->getMainTable()],
            join(' AND ', $conditions),
            ['value', 'count' => new \Zend_Db_Expr("COUNT({$tableAlias}.entity_id)")]
        )->group(
            "{$tableAlias}.value"
        );

        return $connection->fetchPairs($select);
    }
}
