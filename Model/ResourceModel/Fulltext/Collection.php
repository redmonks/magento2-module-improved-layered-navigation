<?php

namespace RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Fulltext;

use Magento\CatalogSearch\Model\Search\RequestGenerator;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Search\Adapter\Mysql\TemporaryStorage;
use Magento\Framework\Search\Request\EmptyRequestDataException;
use Magento\Store\Model\ScopeInterface;
use Magento\CatalogSearch\Model\ResourceModel\EngineInterface;

class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    /** @var \RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Fulltext\Collection|null Clone collection */
    public $collectionClone = null;

    /** @var string */
    private $queryText;

    /** @var string|null */
    private $order = null;

    /** @var string */
    private $searchRequestName;

    /** @var \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory */
    private $temporaryStorageFactory;

    /** @var \Magento\Search\Api\SearchInterface */
    private $search;

    /** @var \RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Search\SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var \Magento\Framework\Api\Search\SearchResultInterface */
    private $searchResult;

    /** @var \Magento\Framework\Api\FilterBuilder */
    private $filterBuilder;

    /* @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $_scopeConfig;

    /** @var \Magento\Framework\App\ProductMetadataInterface */
    protected $_productMetadata;

    /** @var */
    protected $_categoryId;

    /** @var */
    protected $_isTwoOne;

    /**
     * @var SearchResultFactory
     */
    protected $searchResultFactory;

    /**
     * Collection constructor.
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Eav\Model\EntityFactory $eavEntityFactory
     * @param \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Validator\UniversalFactory $universalFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Url $catalogUrl
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Customer\Api\GroupManagementInterface $groupManagement
     * @param \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory $temporaryStorageFactory
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param string $searchRequestName
     * @param SearchResultFactory|null $searchResultFactory
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory $temporaryStorageFactory,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        $searchRequestName = 'catalog_view_container',
        SearchResultFactory $searchResultFactory = null
    )
    {
        if ($searchResultFactory === null) {
            $this->searchResultFactory = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Api\Search\SearchResultFactory::class);
        }
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $moduleManager,
            $catalogProductFlatState,
            $scopeConfig,
            $productOptionFactory,
            $catalogUrl,
            $localeDate,
            $customerSession,
            $dateTime,
            $groupManagement,
            $connection
        );
        $this->temporaryStorageFactory = $temporaryStorageFactory;
        $this->searchRequestName = $searchRequestName;
        $this->_scopeConfig = $scopeConfig;
        $this->_productMetadata = $productMetadata;
        $this->_isMTwoOneVersion();
    }

    /**
     * LayeredNavigation Clone collection
     *
     * @return \RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Fulltext\Collection|null
     */
    public function getCollectionClone()
    {
        if ($this->collectionClone === null) {
            $this->collectionClone = clone $this;
            $this->collectionClone->setSearchCriteriaBuilder($this->searchCriteriaBuilder->cloneObject());
        }

        $searchCriterialBuilder = $this->collectionClone->getSearchCriteriaBuilder()->cloneObject();

        /** @var \RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Fulltext\Collection $collectionClone */
        $collectionClone = clone $this->collectionClone;
        $collectionClone->setSearchCriteriaBuilder($searchCriterialBuilder);

        return $collectionClone;
    }

    /**
     * Add multi-filter categories
     *
     * @param $categories
     * @return $this
     */
    public function addLayerCategoryFilter($categories)
    {
        $this->addFieldToFilter('category_ids', implode(',', $categories));

        return $this;
    }

    /**
     * Remove filter to load option item data
     *
     * @param $attributeCode
     * @return $this
     */
    public function removeAttributeSearch($attributeCode)
    {
        if (is_array($attributeCode)) {
            foreach ($attributeCode as $attCode) {
                $this->searchCriteriaBuilder->removeFilter($attCode);
            }
        } else {
            $this->searchCriteriaBuilder->removeFilter($attributeCode);
        }

        $this->_isFiltersRendered = false;

        return $this->loadWithFilter();
    }

    /**
     * Get attribute condition sql
     *
     * @param $attribute
     * @param $condition
     * @param string $joinType
     * @return string
     */
    public function getAttributeConditionSql($attribute, $condition, $joinType = 'inner')
    {
        return $this->_getAttributeConditionSql($attribute, $condition, $joinType);
    }

    /**
     * Reset Total records
     *
     * @return $this
     */
    public function resetTotalRecords($collectionCount = null)
    {
        $this->_totalRecords = $collectionCount;

        return $this;
    }

    /**
     * @return int|void
     */
    public function getSize() {
        return sizeof($this->getAllIds());
    }

    /**
     * @return \Magento\Search\Api\SearchInterface|mixed
     */
    private function getSearch()
    {
        if ($this->search === null) {
            $this->search = ObjectManager::getInstance()->get('\Magento\Search\Api\SearchInterface');
        }

        return $this->search;
    }

    /**
     * @param \Magento\Search\Api\SearchInterface $object
     */
    public function setSearch(\Magento\Search\Api\SearchInterface $object)
    {
        $this->search = $object;
    }

    /**
     * @return mixed|\RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Search\SearchCriteriaBuilder
     */
    public function getSearchCriteriaBuilder()
    {
        if ($this->searchCriteriaBuilder === null) {
            $this->searchCriteriaBuilder = ObjectManager::getInstance()
                ->get('\RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Search\SearchCriteriaBuilder');
        }

        return $this->searchCriteriaBuilder;
    }

    /**
     * @param \RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Search\SearchCriteriaBuilder $object
     */
    public function setSearchCriteriaBuilder(\RedMonks\ImprovedLayeredNavigation\Model\ResourceModel\Search\SearchCriteriaBuilder $object)
    {
        $this->searchCriteriaBuilder = $object;
    }

    /**
     * @return \Magento\Framework\Api\FilterBuilder|mixed
     */
    private function getFilterBuilder()
    {
        if ($this->filterBuilder === null) {
            $this->filterBuilder = ObjectManager::getInstance()->get('\Magento\Framework\Api\FilterBuilder');
        }

        return $this->filterBuilder;
    }

    /**
     * @param \Magento\Framework\Api\FilterBuilder $object
     */
    public function setFilterBuilder(\Magento\Framework\Api\FilterBuilder $object)
    {
        $this->filterBuilder = $object;
    }

    /**
     * Apply attribute filter to facet collection
     *
     * @param string $field
     * @param null $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($this->searchResult !== null) {
            throw new \RuntimeException('Illegal state');
        }

        $this->getSearchCriteriaBuilder();
        $this->getFilterBuilder();


        if (is_array($condition) && $this->_isElasticSearchEngine()) {
            $this->filterBuilder->setField($field);
            $this->filterBuilder->setValue($condition);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        } elseif (!is_array($condition) || !in_array(key($condition), ['from', 'to'])) {
            $this->filterBuilder->setField($field);
            $this->filterBuilder->setValue($condition);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        } else {
            if (!empty($condition['from'])) {
                $this->filterBuilder->setField("{$field}.from");
                $this->filterBuilder->setValue($condition['from']);
                $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
            }
            if (!empty($condition['to'])) {
                $this->filterBuilder->setField("{$field}.to");
                $this->filterBuilder->setValue($condition['to']);
                $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
            }
        }

        return $this;
    }

    /**
     * Add search query filter
     *
     * @param string $query
     * @return $this
     */
    public function addSearchFilter($query)
    {
        $this->queryText = trim($this->queryText . ' ' . $query);

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _renderFiltersBefore()
    {
        $this->getCollectionClone();

        $this->getSearchCriteriaBuilder();
        $this->getFilterBuilder();
        $this->getSearch();

        if ($this->queryText) {
            $this->filterBuilder->setField('search_term');
            $this->filterBuilder->setValue($this->queryText);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        }

        $priceRangeCalculation = $this->_scopeConfig->getValue(
            \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory::XML_PATH_RANGE_CALCULATION,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($priceRangeCalculation) {
            $this->filterBuilder->setField('price_dynamic_algorithm');
            $this->filterBuilder->setValue($priceRangeCalculation);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        }

        if ($this->_isTwoOne) {
            $this->filterBuilder->setField('category_ids');
            $this->filterBuilder->setValue($this->_categoryId);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        }

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchCriteria->setRequestName($this->searchRequestName);
        if ($this->_isElasticSearchEngine()) {
            if ($this->order && isset($this->order['field'])) {
                $fieldValue = explode("~", $this->order['field']);
            } else {
                $fieldValue = [];
                $fieldValue[0]=null;
            }
            if (!$fieldValue[0]) {
                $searchCriteria->setSortOrders([
                    'entity_id' => 'DESC'
                ]);
            } else {
                $searchCriteria->setSortOrders([
                    $fieldValue[0] => $this->order['dir']
                ]);
            }
        }

        try {
            $this->searchResult = $this->getSearch()->search($searchCriteria);
        } catch (EmptyRequestDataException $e) {
            /** @var \Magento\Framework\Api\Search\SearchResultInterface $searchResult */
            $this->searchResult = $this->searchResultFactory->create()->setItems([]);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Something went wrong, check and test your Search Engine configurations, reindex catalog and clear cache. Thrown error: %1', $e->getMessage()));
        }

        try {
            $temporaryStorage = $this->temporaryStorageFactory->create();
            $table = $temporaryStorage->storeDocuments($this->searchResult->getItems());

            $this->getSelect()->joinInner(
                [
                    'search_result' => $table->getName(),
                ],
                'e.entity_id = search_result.' . TemporaryStorage::FIELD_ENTITY_ID,
                []
            );

            if ($this->order && 'relevance' === $this->order['field']) {
                $this->getSelect()->order('search_result.' . TemporaryStorage::FIELD_SCORE . ' ' . $this->order['dir']);
            }
        } catch (\Exception $ex) {
            $this->_logger->error($ex->getMessage());
        }

        parent::_renderFiltersBefore();
    }

    /**
     * @return $this
     */
    protected function _renderFilters()
    {
        $this->_filters = [];

        return parent::_renderFilters();
    }

    /**
     * Set Order field
     *
     * @param string $attribute
     * @param string $dir
     * @return $this
     */
    public function setOrder($attribute, $dir = Select::SQL_DESC)
    {
        $this->order = ['field' => $attribute, 'dir' => $dir];
        if ($attribute != 'relevance') {
            parent::setOrder($attribute, $dir);
        }

        return $this;
    }

    /**
     * Stub method for compatibility with other search engines
     *
     * @return $this
     */
    public function setGeneralDefaultQuery()
    {
        return $this;
    }

    /**
     * Return field faceted data from faceted search result
     *
     * @param string $field
     * @return array
     * @throws StateException
     */
    public function getFacetedData($field)
    {
        $this->_renderFilters();
        $result = [];

        $aggregations = $this->searchResult->getAggregations();
        if (null !== $aggregations) {
            $bucket = $aggregations->getBucket($field . RequestGenerator::BUCKET_SUFFIX);
            if ($bucket) {
                foreach ($bucket->getValues() as $value) {
                    $metrics = $value->getMetrics();
                    $result[$metrics['value']] = $metrics;
                }
            } else {
                throw new StateException(__('Bucket does not exist'));
            }
        }

        return $result;
    }

    /**
     * Specify category filter for product collection
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return $this
     */
    public function addCategoryFilter(\Magento\Catalog\Model\Category $category)
    {
        $this->_categoryId = $category->getId();
        $this->addFieldToFilter('category_ids', $category->getId());

        return parent::addCategoryFilter($category);
    }

    /**
     * Set product visibility filter for enabled products
     *
     * @param array $visibility
     * @return $this
     */
    public function setVisibility($visibility)
    {
        $this->addFieldToFilter('visibility', $visibility);

        return parent::setVisibility($visibility);
    }

    /**
     * Check the engine provider is 'elasticsearch'
     *
     * @return bool
     */
    protected function _isElasticSearchEngine()
    {
        $currentEngine = $this->_scopeConfig->getValue(EngineInterface::CONFIG_ENGINE_PATH, ScopeInterface::SCOPE_STORE);
        if ($currentEngine == 'elasticsearch' || $currentEngine == 'elasticsearch5'
            || $currentEngine == 'elasticsearch6' || $currentEngine == 'elasticsearch7' ) {
            return true;
        }

        return false;
    }

    /**
     * check magento version
     * @return bool
     */
    protected function _isMTwoOneVersion()
    {
        $version = $this->_productMetadata->getVersion();
        $pos = strpos($version, '2.1');

        if ($pos === false) {
            $this->_isTwoOne = false;
        } else {
            $this->_isTwoOne =  true;
        }

        return $this->_isTwoOne;
    }

}
