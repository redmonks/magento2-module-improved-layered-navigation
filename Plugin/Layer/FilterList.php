<?php
namespace RedMonks\ImprovedLayeredNavigation\Plugin\Layer;

use  RedMonks\ImprovedLayeredNavigation\Helper\Data;

class FilterList
{
    /**
     * @var \RedMonks\ImprovedLayeredNavigation\Helper\Data
     */
    protected $_wpHelper;

    /**
     * FilterList constructor.
     * @param Data $wpHelper
     */
    public function __construct(
        Data $wpHelper
    )
    {
        $this->_wpHelper = $wpHelper;
    }

    /**
     * Remove category filter if disabled in configuration
     *
     * @param \Magento\Catalog\Model\Layer\FilterList $subject
     * @param $result
     * @return array
     */
    public function afterGetFilters(\Magento\Catalog\Model\Layer\FilterList $subject, $result)
    {
        if(!$this->_wpHelper->isEnabled()){
            return $result;
        }
        $filteredResult = [];
        if(!$this->_wpHelper->showCategoriesBlock()) {
            foreach($result as $r) {
                if($r->getRequestVar() != 'cat') {
                    $filteredResult[] = $r;
                }
            }
            return $filteredResult;

        } else {
            return $result;
        }


    }
}
