define([
    "jquery"
], function ($) {
    "use strict";

    window.wpFilterSelect = {
        markSelected: function() {
            $.each($('#wp_ln_shopby_items li'), function() {
                var id = $(this).data('attr-id');
                if(id) {
                    var filterElem = $('#wp_ln_attr_' + id);
                    var filterSwatchElem = $('#wp_ln_swatch_attr_' + id);

                    if(filterElem.length) {
                        filterElem.addClass('wp-ln-selected');
                    }

                    if(filterSwatchElem.length) {
                        filterSwatchElem.addClass('wp-ln-selected');
                    }
                }
            });
        }
    }
});