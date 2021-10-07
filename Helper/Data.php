<?php
namespace RedMonks\ImprovedLayeredNavigation\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\Page\Config;
use Magento\Store\Model\ScopeInterface;
use \Magento\CatalogSearch\Model\ResourceModel\EngineInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    const FILTER_TYPE_SLIDER = 'slider';
    const FILTER_TYPE_LIST = 'list';
    const SLIDE_IN_STYLE = 1;
    const CONFIG_IS_ENABLE = 'redmonks_improvedlayerednavigation/general/enable';
    const CONFIG_IS_ENABLE_AJAX = 'redmonks_improvedlayerednavigation/general/ajax';

    protected $_currentEngine = '';

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var int
     */
    protected $_storeId;

    /**
     * @var Config
     */
    private $pageConfig;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $pageConfig
    ) {
        $this->_storeManager = $storeManager;
        $this->pageConfig = $pageConfig;
        parent::__construct($context);
        $this->_storeId = $this->getCurrentStoreId();
    }

    /**
     * Return current store_id
     *
     * @return int
     */
    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    public function isEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue(self::CONFIG_IS_ENABLE,ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    public function isAjaxEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue(self::CONFIG_IS_ENABLE_AJAX, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Check the engine provider is 'elasticsearch'
     *
     * @return bool
     */
    public function isElasticSearchEngine()
    {
        if (!$this->_currentEngine) {
            $this->_currentEngine = $this->scopeConfig->getValue(EngineInterface::CONFIG_ENGINE_PATH, ScopeInterface::SCOPE_STORE);
        }
        if($this->_currentEngine == 'elasticsearch' || $this->_currentEngine == 'elasticsearch5'
            || $this->_currentEngine == 'elasticsearch6'  || $this->_currentEngine == 'elasticsearch7' ) {
            return true;
        }
        return false;
    }

}
