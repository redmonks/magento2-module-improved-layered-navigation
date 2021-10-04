<?php

namespace RedMonks\ImprovedLayeredNavigation\Block\Navigation;

/**
 * Layered navigation state
 *
 * @api
 * @since 100.0.2
 */
class State extends \Magento\LayeredNavigation\Block\Navigation\State
{
    /**
     * @var string
     */
    protected $_template = 'RedMonks_ImprovedLayeredNavigation::layer/state.phtml';

    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_catalogLayer;

    /**
     * @var \RedMonks\ImprovedLayeredNavigation\Helper\Data
     */
    protected $_rmHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \RedMonks\ImprovedLayeredNavigation\Helper\Data $rmHelper,
        array $data = []
    ) {
        $this->_catalogLayer = $layerResolver->get();
        $this->_rmHelper = $rmHelper;
        parent::__construct($context,$layerResolver, $data);
    }

}
