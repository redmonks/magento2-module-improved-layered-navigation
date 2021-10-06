<?php
namespace RedMonks\ImprovedLayeredNavigation\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use RedMonks\ImprovedLayeredNavigation\Model\AttributeOptions;

class ProductAttributeSaveAfter implements ObserverInterface
{
    /**
     * @var array
     */
    protected $_allowedInputTypes = [
        'select',
        'multiselect',
        'price'
    ];

    /**
     * @var AttributeOptions
     */
    protected $_attributeOptions;

    public function __construct(AttributeOptions $attributeOptions)
    {
        $this->_attributeOptions = $attributeOptions;
    }

    /**
     * After save attribute
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $attribute = $observer->getEvent()->getAttribute();
        $attributeOptions = $this->_attributeOptions->getDisplayOptionsByAttribute($attribute->getAttributeId());

        if (in_array($attribute->getFrontendInput(), $this->_allowedInputTypes)) {
            // insert
            if (!$attributeOptions->getId()) {
                $this->_attributeOptions->setAttributeId($attribute->getAttributeId())
                    ->setDisplayOption($attribute->getDisplayOptions())
                    ->setIsMultiselect($attribute->getIsMultiselect())
                    ->save();
            } else {
                $this->_attributeOptions->setId($attributeOptions->getId())
                    ->setDisplayOption($attribute->getDisplayOptions())
                    ->setIsMultiselect($attribute->getIsMultiselect())
                    ->save();
            }
        }
    }
}
