<?php
namespace RedMonks\ImprovedLayeredNavigation\Plugin\Block;

use Magento\LayeredNavigation\Block\Navigation\State;

class LayerNavState
{
    public function afterGetTemplate(State $subject, $result)
    {
        return 'RedMonks_ImprovedLayeredNavigation::layer/state.phtml';
    }
}
