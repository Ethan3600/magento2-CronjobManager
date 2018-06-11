/**
 * Based on Daniel Earwicker's virtualized scolling
 * https://smellegantcode.wordpress.com/2012/12/26/virtualized-scrolling-in-knockout-js/
 */
define([
    'ko',
    'jquery',
    'Magento_Ui/js/lib/knockout/template/renderer'
], function (ko, $, renderer) {
    'use strict';

    /**
     * Simulates Ko's observable functionality for 
     * properties of the DOM
     */
    var simulatedObservable = (function() {
     
        var timer = null;
        var items = [];
     
        var check = function() {
            items = items.filter(function(item) {
                return !!item.elem.parents('html').length;
            });
            if (items.length === 0) {
                clearInterval(timer);
                timer = null;
                return;
            }
            items.forEach(function(item) {
                item.obs(item.getter());
            });
        };
     
        return function(elem, getter) {
            var obs = ko.observable(getter());
            items.push({ obs: obs, getter: getter, elem: $(elem) });
            if (timer === null) {
                timer = setInterval(check, 100);
            }
            return obs;
        };
    })();

    ko.bindingHandlers.virtualForEach = {

        /**
         * Binding init callback.
         */
        init: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
            var element = element.parentNode;
            var clone = $(element).clone();
            $(element).empty();

            var config = ko.utils.unwrapObservable(valueAccessor());
            if (config.data == null) {
                return;
            }

            // lets make our data into an observable array
            config.data = ko.observableArray(config.data);

            var $timelineCont = $('.timeline-container');
            var tcOffset = $timelineCont.offset();

            // x-offset of target element
            var offset = simulatedObservable(element, function() {
                return $(element).offset().left;
            });

            // window top relative to scrollbar
            var windowPosition = simulatedObservable(element, function() {
                return $(window).scrollTop();
            });

            // record of all materialized rows
            var created = {};

            /**
             * Responsible for materializing any cron jobs that
             * are currently visible
             */
            var refresh = function() {
                var o = offset();
                var topBoundry = windowPosition();
                var bottomBoundry = windowPosition() + $(window).height();
                var leftBoundry = tcOffset.left;   
                var rightBoundry = $timelineCont.width() + leftBoundry;

                ko.utils.arrayForEach(config.data(), function(cron) {
                    if (!created[cron.schedule_id]) {
                        var cronElement = clone.clone().children();
                        ko.applyBindingsToDescendants(
                            bindingContext.createChildContext(cron),
                            cronElement[0]
                        );
                        created[cron.schedule_id] = cronElement;
                        $(element).append(cronElement);
                    }
                });

                Object.keys(created).forEach(function(id) {
                    // can't grab element offset.. 
                    if (id < top || id >= bottom) {
                        // created[rowNum].remove();
                        // delete created[rowNum];
                    }
                });
            };

            config.data.subscribe(function() {
                Object.keys(created).forEach(function(rowNum) {
                    // created[rowNum].remove();
                    // delete created[rowNum];
                });
                refresh();
            });

            ko.computed(refresh);

            return { controlsDescendantBindings: true };
        }
    };

    renderer.addNode('virtualForEach');
    ko.virtualElements.allowedBindings.virtualForEach = true;
});
