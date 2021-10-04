define([
    "jquery",
    "loader",
    "jquery-ui-modules/widget",
    "jquery-ui-modules/effect-slide",
    "Magento_Catalog/js/product/list/toolbar",
], function ($) {
    /**
     * ProductListToolbarForm Widget - this widget is setting cookie and submitting form according to toolbar controls
     */
    $.widget('mage.productListToolbarForm', $.mage.productListToolbarForm, {

        options:
            {
                modeControl: '[data-role="mode-switcher"]',
                directionControl: '[data-role="direction-switcher"]',
                orderControl: '[data-role="sorter"]',
                limitControl: '[data-role="limiter"]',
                pagerControl: '[data-role="pager"], .pages-items a',
                mode: 'product_list_mode',
                direction: 'product_list_dir',
                order: 'product_list_order',
                limit: 'product_list_limit',
                pager: 'p',
                modeDefault: 'grid',
                directionDefault: 'asc',
                orderDefault: 'position',
                limitDefault: '9',
                pagerDefault: '1',
                productsToolbarControl: '.toolbar.toolbar-products',
                productsListBlock: '#layer-product-list',
                layeredNavigationFilterBlock: '.block.filter',
                filterItemControl: '.block.filter .item a, .block.filter .filter-clear,.block.filter .swatch-option-link-layered, .wp-price-slider-a',
                url: ''
            },

        _create: function () {
            this._super();
            this._bind($(this.options.pagerControl), this.options.pager, this.options.pagerDefault);
            $(this.options.filterItemControl)
                .off('click.' + this.namespace + 'productListToolbarForm')
                .on('click.' + this.namespace + 'productListToolbarForm', {}, $.proxy(this.applyFilterToProductsList, this))
            ;

            $('.item a.wp-filter-disabled').off('click');
            if (window.wp_ajax_useCustomPlaceholder == '1') {
                $('.page-wrapper').loader({
                    icon: window.loadingImage,
                    template: '<div class="loading-mask" data-role="loader">\n' +
                        '    <div class="loader">\n' +
                        '         <img alt="<%- data.texts.imgAlt %>" style="max-width: ' + window.wp_ajax_placeholderCustomWidth + '" src="<%- data.icon %>">\n' +
                        '        <p><%- data.texts.loaderText %></p>\n' +
                        '    </div>\n' +
                        '</div>'
                });
            }

        },
        /**
         * @param {jQuery.Event} event
         * @private
         */
        _processPagination: function (event) {
            event.preventDefault();
            var paginationUrl = event.currentTarget.href;
            var urlParams = this.getUrlParams(paginationUrl);
            this.changeUrl(
                event.data.paramName,
                urlParams[event.data.paramName],
                event.data.default
            );
        },
        _bind: function (element, paramName, defaultValue) {
            /**
             * Prevent double binding of these events because this component is being applied twice in the UIX
             */
            if (paramName == this.options.pager) {
                element
                    .off('click.' + this.namespace + 'productListToolbarForm')
                    .on('click.' + this.namespace + 'productListToolbarForm', {
                        paramName: paramName,
                        default: defaultValue
                    }, $.proxy(this._processPagination, this));
            } else if (element.is("select")) {
                element
                    .off('change.' + this.namespace + 'productListToolbarForm')
                    .on('change.' + this.namespace + 'productListToolbarForm', {
                        paramName: paramName,
                        default: defaultValue
                    }, $.proxy(this._processSelect, this));
            } else {
                element
                    .off('click.' + this.namespace + 'productListToolbarForm')
                    .on('click.' + this.namespace + 'productListToolbarForm', {
                        paramName: paramName,
                        default: defaultValue
                    }, $.proxy(this._processLink, this));
            }

        },
        applyFilterToProductsList: function (evt) {
            var link = $(evt.currentTarget),
                linkA = link.attr('href'),
                urlParts = (typeof linkA !== 'undefined') ? linkA.split('?') : '',
                currentUrl = window.location.href,
                isMulti = (link.attr('data-is-multi')) ? link.data('is-multi') : 0,
                parentElem = link.parent(),
                clickOpt = (parentElem.attr('data-path-opt')) ? parentElem.data('opt-path') : link.data('opt-path'),
                c = currentUrl.split('?');
            window.isSorting = false;
            var reqeustParams = (typeof urlParts[1] === 'undefined') ? '' : urlParts[1];

            var mergedPath = reqeustParams;

            if (reqeustParams.length > 0 && typeof c[1] !== 'undefined') {
                mergedPath = this.compareMergeParams(c[1], reqeustParams, clickOpt, isMulti);
            }

            self.elem = link;

            this.makeAjaxCall(urlParts[0], mergedPath);
            evt.preventDefault();

        },
        compareMergeParams: function (currentParamsStr, newParamsStr, clickOpt, isMulti) {
            var a = currentParamsStr.split('&');
            var b = newParamsStr.split('&');
            var c = (typeof clickOpt !== 'undefined') ? clickOpt.split('=') : '';
            var res = '';
            a.sort();
            b.sort();
            for (var i = 0; i < b.length; i++) {
                var paramStr = b[i].split('=')[0],
                    paramVal = decodeURIComponent(b[i].split('=')[1]),
                    paramArr = paramVal.split(',');
                for (var j = 0; j < a.length; j++) {

                    if (typeof a[j] === 'undefined') {
                        continue;
                    }

                    var existParamStr = a[j].split('=')[0],
                        existParamVal = decodeURIComponent(a[j].split('=')[1]),
                        existParamArr = existParamVal.split(','),
                        matchParams = '';

                    if(paramStr == 'p' || paramStr == 'q' || paramStr == 'ajax') {
                        continue;
                    }

                    if (paramStr !== existParamStr) {
                        //res += paramStr + '=' + paramVal + '&';
                        continue;
                    }
                    for (var z = 0; z < existParamArr.length; z++) {
                        if (paramArr.indexOf(existParamArr[z]) !== -1) {
                            matchParams = (matchParams.length === 0) ? existParamArr[z] : matchParams + ',' + existParamArr[z];
                        }
                    }

                    if (paramStr === existParamStr && paramVal !== existParamVal && matchParams.length === 0 && isMulti != 0) {
                        paramVal = existParamVal + ',' + paramVal;
                    }
                    else if( c[0] == paramStr && c[1] == matchParams ){
                        var filteredArray = existParamArr.filter(function(e) { return e !== matchParams })
                        paramVal = (filteredArray.length > 0) ? filteredArray.join(',') : '';
                    }
                    else  {
                        //paramVal = matchParams;
                    }
                }

                if(paramVal) {
                    res += paramStr + '=' + paramVal + '&';
                }

            }
            res = res.slice(0, -1);

            return res;
        },
        updateUrl: function (url, paramData) {
            if (!url) {
                return;
            }
            if (paramData && paramData.length > 0) {
                url += '?' + paramData;
            }
            url = this.removeQueryStringParameter('ajax', url);
            url = this.removeQueryStringParameter('_', url);
            if (typeof history.replaceState === 'function') {
                history.replaceState({}, null, url);
            }
        },

        getParams: function (urlParams, paramName, paramValue, defaultValue) {
            var decode = window.decodeURIComponent,
                paramData = {},
                parameters, i;

            for (i = 0; i < urlParams.length; i++) {
                parameters = urlParams[i].split('=');
                paramData[decode(parameters[0])] = parameters[1] !== undefined ?
                    decode(parameters[1].replace(/\+/g, '%20')) :
                    '';
            }

            /** get the real attr name from param */
            var paramValueArr = paramValue.split('~'),
                paramValueNew = paramValueArr[0];

            paramData[paramName] = paramValueNew;

            /** get the given direction from param */
            var directionName = this.options.direction;
            if (paramValueArr.length == 2 && paramName != directionName) {
                paramData[directionName] = paramValueArr[1];
            }

            return $.param(paramData);
        },
        _updateContent: function (content) {
            window.shouldOpenMinicart = false;
            $(this.options.productsToolbarControl).remove();
            if (content.products_list) {
                $(this.options.productsListBlock).html(content.products_list);
                $(this.options.productsListBlock).trigger('contentUpdated');
            }

            if (content.filters) {
                var isSlideIn = $('body').hasClass('slider-layer');
                var isSlideDown = $('body').hasClass('slider-down-layer');
                var isAutoClose = $('#layered-filter-block').hasClass('auto-close');
                var isViewL = $('body').hasClass('wp-device-l');
                var isViewXl = $('body').hasClass('wp-device-xl');
                $(this.options.layeredNavigationFilterBlock).replaceWith(content.filters);
                $(this.options.layeredNavigationFilterBlock).trigger('contentUpdated');

                if(!isAutoClose && isSlideIn  && !window.isSorting && (isViewL || isViewXl)) {
                    $('body').addClass('wp-ln-open').css({'height': '100%', 'overflow': 'hidden'});
                    $('.block-search, a.logo').css({'z-index': '1'});
                    $('.wp-ln-overlay').fadeIn(100, 'swing', function () {
                        $('#layered-filter-block').delay(150).show("slide", {direction: "left"});
                    });
                }

                if(!isAutoClose && isSlideDown && !window.isSorting) {
                    var productWrapperMarginTop = $('body').attr('data-pwmt');
                    $('.products.wrapper').animate({marginTop: productWrapperMarginTop},
                        {
                            duration:500,
                            complete: function() {
                                $('.wp-filters span.wp-slide-down-add').addClass('active');
                            }

                        });
                    $('.wp-slide-down-add:not(active)').off('click');
                    $('.slide-down-filter').slideDown(500);
                    $('body.slider-down-layer #layered-filter-block').show();
                }

            }

            if (content.dataLayer) {
                var dlObjects = JSON.parse(content.dataLayer);
                window.dataLayer = window.dataLayer || [];
                for (var i in dlObjects) {
                    window.dataLayer.push(dlObjects[i]);
                }
            }
            if (content.dataLayerGA4) {
                var dl4Objects = JSON.parse(content.dataLayerGA4);
                window.dataLayer = window.dataLayer || [];
                for (var i in dl4Objects) {
                    window.dataLayer.push(dl4Objects[i]);
                }
            }
            $(document).trigger("wpproductlabels:init");
            $('li.product-item').trigger('contentUpdated');
            if (window.isSlCustomPopupUsed && parseInt(window.isSlCustomPopupUsed)) {
                $('li.product-item').find('.towishlist').each(function() {
                    $(this).removeAttr('data-post');
                })
            }
            $('body').trigger('contentUpdated');

        },
        lnSlideDown: function() {
            var productWrapperMarginTop = $('body').attr('data-pwmt');

            $('.products.wrapper').animate({marginTop: productWrapperMarginTop},
                {
                    duration:500,
                    complete: function() {
                        $('.wp-filters span.wp-slide-down-add').addClass('active');
                    }

                });
            $('.wp-slide-down-add:not(active)').off('click');
            $('.slide-down-filter').slideDown(500);
            $('.wp-filters').on('click', this.lnSlideUp);
        },
        lnSlideUp: function() {
            $('.wp-filters').off('click');
            $('.slide-down-filter').slideUp(
                {
                    duration: 500,
                    start: function(){
                        $('.products.wrapper').animate(
                            {
                                marginTop: '0px'
                            },
                            {
                                duration:500,
                                complete: function() {
                                    $('.wp-filters span.wp-slide-down-add').removeClass('active');
                                    $('.wp-filters').on('click', this.lnSlideDown);
                                },
                                queue: false
                            }
                        );

                    },
                    queue: false
                });
        },
        reinitializeIas: function() {
            if(require.defined('ias') && window.ajaxCatalog == 'infiniteScroll') {
                jQuery.ias().destroy();
                jQuery(function($) {
                    var config = {
                        container:       '.products.wrapper .product-items',
                        item:            '.product-item',
                        pagination:      '.toolbar .pages, .toolbar .limiter',
                        next:            '.pages .action.next',
                        negativeMargin:  window.negativeMargin
                    };
                    /** added to prevent jquery to add extra "_" parameter to link */
                    $.ajaxSetup({ cache: true });

                    /** add infinite-scroll class */
                    $(config.container).closest('.column.main').addClass('infinite-scroll');
                    /** load ias */
                    var ias = $.ias(config);

                    ias.getNextUrl = function(container) {
                        if (!container) {
                            container = ias.$container;
                        }
                        /** always take the last matching item + fix to be protocol relative */
                        var nexturl = $(ias.nextSelector, container).last().attr('href');
                        if(typeof nexturl !== "undefined") {
                            if (window.location.protocol == 'https:') {
                                nexturl = nexturl.replace('http:', window.location.protocol);
                            } else {
                                nexturl = nexturl.replace('https:', window.location.protocol);
                            }
                            nexturl = window.ajaxInfiniteScroll.removeQueryStringParameter('_', nexturl);
                            nexturl = window.ajaxInfiniteScroll.removeQueryStringParameter('ajax', nexturl);
                        }

                        return nexturl;
                    };

                    /** adds extra functionality to Infinite AJAX Scroll */
                    ias.extension(new IASPagingExtension());
                    ias.on('pageChange', function(pageNum, scrollOffset, url) {
                        window.page = pageNum;
                    });

                    /** added to prevent jquery to add extra "_" parameter to link */
                    ias.on('load', function(event) {
                        var url = event.url;
                        event.ajaxOptions.cache = true;
                        event.url = window.ajaxInfiniteScroll.removeQueryStringParameter('_', event.url);
                    });

                    ias.on('loaded', function(data, items) {
                        /** fix lazy load images */
                        window.ajaxInfiniteScroll.reloadImages(items);
                        window.ajaxInfiniteScroll.dataLayerUpdate(data);
                    });
                    /** fix ajax add to cart */
                    ias.on('rendered', function(items) {
                        window.ajaxInfiniteScroll.fixAddToCart();
                        /** re-init Pearl related elements */
                        window.ajaxInfiniteScroll.reloadQuickView();
                        window.ajaxInfiniteScroll.reloadCategoryPage();
                        /** update next/prev head links */
                        if (window.showCanonical == 1) {
                            window.ajaxInfiniteScroll.reloadCanonicalPrevNext();
                        }
                        $('.product-item-info a').each(function() {
                            if( typeof $(this).attr('data-item-page') === 'undefined') {
                                $(this).attr('data-item-page', window.page);
                            }
                        });
                        $(document).trigger("wpproductlabels:init");
                        $('li.product-item').trigger('contentUpdated');
                        if (window.isSlCustomPopupUsed && parseInt(window.isSlCustomPopupUsed)) {
                            $('li.product-item').find('.towishlist').each(function() {
                                $(this).removeAttr('data-post');
                            })
                        }
                        $.mage.formKey();
                    });


                    /** adds a text when there are no more pages to load */
                    ias.extension(new IASNoneLeftExtension({
                        html: '<span class="ias-no-more">' + window.textNoMore + '</span>'

                    }));
                    /** displays a customizable loader image when loading a new page */
                    var loadingHtml  = '<div class="ias-spinner">';
                    loadingHtml += '<img src="{src}"';
                    if (window.wp_ajax_useCustomPlaceholder == '1') {
                        loadingHtml += "style='max-width:" + window.wp_ajax_placeholderCustomWidth +"'";
                    }
                    loadingHtml += '/>';
                    loadingHtml += '<span>' + window.textLoadingMore + '</span>';
                    loadingHtml += '</div>';
                    ias.extension(new IASSpinnerExtension({
                        src: window.loadingImage,
                        html: loadingHtml
                    }));

                    /** adds "Load More" and "Load Previous" button */
                    if (window.LoadMore > 0) {
                        ias.extension(new IASTriggerExtension({
                            text: window.textNext,
                            html: '<button class="button action ias-load-more" type="button"><span>{text}</span></button>',
                            textPrev: 'Load previous items',
                            htmlPrev: '<button class="button action ias-load-prev" type="button"><span>{text}</span></button>',
                            offset: window.LoadMore
                        }));
                    } else {
                        ias.extension(new IASTriggerExtension({
                            textPrev: 'Load previous items',
                            htmlPrev: '<button class="button action ias-load-prev" type="button"><span>{text}</span></button>',
                            offset: 1000
                        }));
                    }
                    /** adds history support */
                    ias.extension(new IASHistoryExtension({prev: '.previous'}));

                });
            }
        },

        updateContent: function (content) {
            this._updateContent(content)
        },


        changeUrl: function (paramName, paramValue, defaultValue) {
            var urlPaths = this.options.url.split('?'),
                baseUrl = urlPaths[0],
                urlParams = urlPaths[1] ? urlPaths[1].split('&') : [],
                paramData = this.getParams(urlParams, paramName, paramValue, defaultValue);
            window.isSorting = (paramName == 'product_list_order' || paramName == 'product_list_limit' || paramName == 'p') ? true : false;
            if( paramName == 'product_list_mode') {// paramName == 'product_list_order' - to remove adavancedsorting by ajax
                location.href = baseUrl + (paramData.length ? '?' + paramData : '');
            } else {
                this.makeAjaxCall(baseUrl, paramData);
            }
        },

        backToTop: function()
        {
            var stickyHeader = $('.sticky-header, .sticky-header-mobile'),
                stickyHeaderHeight = 0;
            if (stickyHeader.length) {
                stickyHeaderHeight = parseInt(stickyHeader.outerHeight());
            }

            $('html, body').animate({
                scrollTop: ($('.column.main').offset().top - stickyHeaderHeight)
            }, 'slow');
        },

        makeAjaxCall: function (baseUrl, paramData) {
            var self = this;
            var isSlideIn = $('body').hasClass('slider-layer');
            if (!isSlideIn) {
                self.backToTop();
            }
            var showLoader = true;
            if (window.wp_ajax_useCustomPlaceholder == '1' && window.isSorting) {
                $('.page-wrapper').loader("show");
                showLoader = false;
            }
            var jqxhr = $.ajax({
                url: baseUrl,
                data: (paramData && paramData.length > 0) ? paramData + '&ajax=1' : 'ajax=1',
                type: 'get',
                dataType: 'json',
                cache: true,
                showLoader: showLoader,
                beforeSend: function (xhr){
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    if(typeof window.page === 'undefined') {
                        window.page = $('.product-item-info a').last().attr('data-item-page');
                    }
                }
            }).done(function (response) {
                if (response.success) {
                    $('.swatch-option-tooltip').hide();
                    self.updateUrl(baseUrl, paramData);
                    self.updateContent(response.html);
                    self.slidersUpdate();
                }
            });

            jqxhr.always(function() {
                if (window.wp_ajax_useCustomPlaceholder == '1') {
                    $('.page-wrapper').loader("hide");
                }
                /** fix lazy load image width */
                $("img.lazy").each(function() {
                    $(this).css({'max-width':'100%'});
                });

                self.reinitializeIas();
                $('.product.photo.product-item-photo').on('click', function(e) {
                    e.preventDefault();
                    var page = $(this).attr('data-item-page');
                    var url = window.location.href;
                    self.resetIasPagination(page, url);
                    var href = $(this).attr('href');
                    window.location.href = href;
                });
                $('.product-item-info a').each(function() {
                    if( typeof $(this).attr('data-item-page') === 'undefined') {
                        $(this).attr('data-item-page', window.page);
                    }
                });
                if(require.defined('ias') && window.ajaxCatalog == 'nextPage') {
                    window.ajaxInfiniteScroll.addPageSelector('.pages li.item a.page');
                }

                if ($('body').hasClass('slider-layer')) self.resetPage();
                if ($('body').hasClass('slider-down-layer')) self.slideUpReset();

                self.reloadQuickView();
                window.shouldOpenMinicart = '1';
                $.mage.formKey();
            });
        },
        resetIasPagination: function(page, url) {
            if(require.defined('ias') && window.ajaxCatalog == 'infiniteScroll') {
                jQuery.ias().destroy();
                var newUrl = url.replace(/(p=).*?(&|$)/, '$1' + page + '$2');
                window.history.replaceState("", "", newUrl);


            }
        },
        reloadQuickView: function() {
            var quickView = $('.redmonks-quickview');
            if (quickView.length) {
                $('.redmonks-quickview').bind('click', function() {
                    var prodUrl = $(this).attr('data-quickview-url');
                    if (prodUrl.length) {
                        window.quickview.displayContent(prodUrl);
                    }
                });
                if (window.wpQwListMode == 'list') {
                    quickView.each(function (key, item) {
                        if (!$(item).hasClass('wp-qw-adjusted')) {
                            var imageWrapper = $(item).closest('.product-item').find('.product-item-info').get(0);
                            var imagePhotoLink = $(item).closest('.product-item-info').find('.product-item-photo').get(0);

                            $(imageWrapper).prepend('<div class="product photo product-item-photo product-image-list"></div>');
                            var imageCustomDiv = $(item).closest('.product-item-info').find('.product-image-list').get(0);

                            $(imagePhotoLink).appendTo(imageCustomDiv)
                            var imagePhoto = $(item).closest('.product-item-info').find('.product-image-list').get(0);
                            $(item).show().appendTo(imagePhoto);
                            $(item).addClass('wp-qw-adjusted');
                            $(item).css('display', '');
                        }
                    });
                }
            }
        },
        slidersUpdate: function () {
            $('.wp-slide-in').not(':first').remove();
            $('.wp-slide-out').not(':first').remove();
            $('.wp-filters').not(':first').remove();
            $('.wp-ln-overlay').not(':first').remove();
            $('.wp-ln-slider-js').not(':first').remove();
            $('.wp-ln-selected-js').not(':first').remove();
        },
        resetPage: function () {
            var slideInBlock = $('#layered-filter-block');
            if(slideInBlock.hasClass('auto-close')) {
                //$('.slide-in-filter').hide('slide', {direction: "left"}, 500, function () {
                $('body').removeClass('wp-ln-open').css({'height': 'auto', 'overflow': 'auto'});
                $('.wp-ln-overlay').hide();
                $('.block-search, a.logo').css({'z-index': '10'});
                //});
            }

        },

        slideUpReset: function () {
            var slideDownBlock = $('#layered-filter-block');
            if(slideDownBlock.hasClass('auto-close')) {
                //$('.wp-slide-down-add.active').off('click');
                //$('.wp-slide-down-add:not(active)').on('click', self.lnSlideDown());
                $('.wp-filters span.wp-slide-down-add').removeClass('active');
                $('.products.wrapper').animate({marginTop: '0px'}, 1000);
                $('.slide-down-filter').slideUp(1000);
                $('.wp-filters').off('click');
            } else {
                $('.wp-filters span.wp-slide-down-add').removeClass('active');
                $('.wp-filters').off('click');
            }
        },

        markSelected: function () {
            var elem = self.elem.parent();
            if(elem.hasClass('wp-ln-selected')) {
                elem.removeClass('wp-ln-selected');

            } else {
                elem.addClass('wp-ln-selected');
            }

        },
        removeQueryStringParameter: function (key, url)
        {
            if (!url) url = window.location.href;
            var hashParts = url.split('#'),
                regex = new RegExp("([?&])" + key + "=.*?(&|#|$)", "i");

            if (hashParts[0].match(regex)) {
                url = hashParts[0].replace(regex, '$1');
                url = url.replace(/([?&])$/, '');
                if (typeof hashParts[1] !== 'undefined' && hashParts[1] !== null)
                    url += '#' + hashParts[1];
            }

            return url;
        },
        getUrlParams: function (url) {
            var params = {
                p: "1"
            };
            var parser = document.createElement('a');
            parser.href = url;
            var query = parser.search.substring(1);
            var vars = query.split('&');
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split('=');
                params[pair[0]] = decodeURIComponent(pair[1]);
            }
            return params;
        }

    });

    return $.mage.productListToolbarForm;
});
