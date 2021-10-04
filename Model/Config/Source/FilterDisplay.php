<?php

namespace RedMonks\ImprovedLayeredNavigation\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class FilterDisplay implements ArrayInterface
{
    /**
     * @var array
     */
    protected $_styles = array(
        0 => 'Close',
        1 => 'Open',
        2 => 'Expand',
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
