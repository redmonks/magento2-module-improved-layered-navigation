<?php
namespace RedMonks\ImprovedLayeredNavigation\Observer\Edit\Tab\Front;

use Magento\Config\Model\Config\Source;
use Magento\Framework\Event\Observer;
use Magento\Framework\Module\Manager;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\Http;
use RedMonks\ImprovedLayeredNavigation\Model\AttributeOptions;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class ProductAttributeFormBuildFrontTab implements ObserverInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $optionList;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var Http
     */
    protected $_request;

    /**
     * @var AttributeOptions
     */
    protected $_wpModel;

    protected $_wpAttributeObj = false;

    /**
     * @var Attribute
     */
    protected $_attributeModel;

    public function __construct(
        Manager $moduleManager,
        Source\Yesno $optionList,
        Http $request,
        AttributeOptions $wpModel,
        Attribute $attributeModel
    )
    {
        $this->optionList = $optionList;
        $this->moduleManager = $moduleManager;
        $this->_request = $request;
        $this->_wpModel = $wpModel;
        $this->_attributeModel = $attributeModel;

        $this->_getWpAttributeOptionValues();
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->moduleManager->isOutputEnabled('RedMonks_ImprovedLayeredNavigation')) {
            return;
        }
        $isSwatchAttr = $this->_isSwatchAttr();
        $swatchComment = $isSwatchAttr ?  '<br/>This setting can not be used with swatch attribute types.' : '';

        /** @var \Magento\Framework\Data\Form\AbstractForm $form */
        $form = $observer->getForm();

        $fieldset = $form->addFieldset(
            'iln_front_fieldset',
            ['legend' => __('Improved Layered Navigation Properties'), 'collapsable' => false]
        );

        $fieldset->addField(
            'display_options',
            'select',
            [
                'name' => 'display_options',
                'label' => __("Filter Display Options"),
                'title' => __('Can be used only with catalog input type Dropdown, Multiple Select and Price'),
                'note' => __('Can be used only with catalog input type Dropdown, Multiple Select and Price. %1', $swatchComment),
                'values' => [
                    ['value' => 0, 'label' => __('Closed')],
                    ['value' => 1, 'label' => __('Fully Opened')],
                    ['value' => 2, 'label' => __('Expandable')],
                ],
                'value' => $this->_getDisplayOption(),
            ]
        );

        $fieldset->addField(
            'is_multiselect',
            'select',
            [
                'name' => 'is_multiselect',
                'label' => __("Enable Multiselect"),
                'value' => $this->_getIsMultiselect(),
                'values' => [
                    ['value' => 0, 'label' => __('No')],
                    ['value' => 1, 'label' => __('Yes')],
                ],
                'title' => __('Allow to filter multiple options from the same attribute.'),
                'note' => __('Allow to filter multiple options from the same attribute.')

            ]
        )->setAfterElementHtml(
            "<script>
                   //<![CDATA[
                       require(['jquery', 'jquery/ui'], function($){
                            var acceptedTypeArray = ['select','multiselect','price','swatch_visual','swatch_text'],
                                displayOptionEl = $('#display_options'),
                                isMultiselectEl = $('#is_multiselect'),
                                mageFrontendInpEl = $('#frontend_input'),

                                displayOpt = '" . $this->_getDisplayOption() . "',
                                isMultiselect = '" . $this->_getIsMultiselect() . "',
                                isSwatch = '".$isSwatchAttr."';
                                displayOptionEl.val(displayOpt);
                                isMultiselectEl.val(isMultiselect);
                                setIlnVisibility();
                            mageFrontendInpEl.change(function(){
                                var selVal = $(this).val();
                                if($.inArray(selVal, acceptedTypeArray) !== -1) {
                                    enableElement(displayOptionEl);
                                    enableElement(isMultiselectEl);
                                } else {
                                    disableElement(displayOptionEl);
                                    disableElement(isMultiselectEl);
                                }
                            });

                            /**
                            * set wp fields property(disabled)
                            */
                            function setIlnVisibility(){
                                var elVal = mageFrontendInpEl.val();
                                if($.inArray(elVal, acceptedTypeArray) !== -1) {
                                    enableElement(displayOptionEl);
                                    enableElement(isMultiselectEl);
                                } else {
                                    disableElement(displayOptionEl);
                                }
                                if(isSwatch) {
                                    disableElement(displayOptionEl);
                                }
                            }

                            /**
                            * set element as disabled
                            * @param element
                            */
                            function disableElement(element) {
                                element.prop('disabled', true);
                            }

                            /**
                            * remove 'disabled' attribute from element
                            * @param element
                            */
                            function enableElement(element) {
                                element.removeAttr('disabled');
                            }
                        });
                   //]]>
             </script>"
        );

    }

    /**
     * @return $this|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getWpAttributeOptionValues()
    {
        $attributeId = $this->_request->getParam('attribute_id');

        if ($attributeId) {
            $this->_wpAttributeObj = $this->_wpModel->getDisplayOptionsByAttribute($attributeId);
        }

        return $this;
    }

    /**
     * Get attribute 'display_option' value
     * @return int
     */
    protected function _getDisplayOption()
    {
        $val = AttributeOptions::DISPLAY_OPTION_DEF_VAL;
        if($this->_wpAttributeObj) {
            if(!empty($this->_wpAttributeObj->getData())) {
                $val = $this->_wpAttributeObj->getDisplayOption();
            }
        }

        return $val;
    }

    /**
     * Get attribute 'is_multiselect' value
     * @return int
     */
    protected function _getIsMultiselect()
    {
        $val = 0;
        if($this->_wpAttributeObj) {
            if(!empty($this->_wpAttributeObj->getData())) {
                $val = $this->_wpAttributeObj->getIsMultiselect();
            }
        }

        return $val;
    }

    protected function _isSwatchAttr() {
        $attributeId = $this->_request->getParam('attribute_id');

        if ($attributeId) {
            $attr = $this->_attributeModel->load($attributeId);
            if(!$attr->getAdditionalData()) {
                return false;
            }
            $addititonalData = $attr->getAdditionalData();
            $attrData = json_decode($addititonalData, true);
            if(isset($attrData['swatch_input_type'])) {
                return true;
            }
            return true;
        }

        return false;
    }

}
