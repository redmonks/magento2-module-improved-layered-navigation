<?php
namespace RedMonks\ImprovedLayeredNavigation\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class SortBy implements ArrayInterface
{
    /**
     * @var array
     */
    protected $_styles = array(
        'position' => 'Position',
        'name' => 'Name'
    );

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        foreach ($this->_styles as $id => $style) :
            $options[] = array(
                'value' => $id,
                'label' => $style
            );
        endforeach;
        return $options;
    }
}
