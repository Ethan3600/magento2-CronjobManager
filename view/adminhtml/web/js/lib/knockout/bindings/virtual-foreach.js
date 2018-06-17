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
     * Get's the offset relative to the window for a cron
     *
     * @param {Object} viewModel
     * @param {Object} cron
     * @param {Object} tcOffset - timeline container offset results
     * @param {Observable} i - iterations in the virtualForEach
     * @return {Object} cronOffset - top and left 
     */
    var preCalculateOffset = function(viewModel, cron, tcOffset, i) {
        var cronOffset = {};

        /////////////////Vertial Offset/////////////////
        var rowHoursOffset = 31;
        var rowHeight = 40;
        var rowHeightOffset = i * rowHeight;
        cronOffset.top = tcOffset.top + rowHeightOffset + rowHoursOffset; 
        ///////////////Horizontal Offset////////////////
        var timeOffset = viewModel.getOffset(cron, true);
        cronOffset.left = timeOffset + $('.timeline-container__panel').offset().left;

        return cronOffset;
    }

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
                timer = setInterval(check, 250);
            }
            return obs;
        };
    })();

    ko.bindingHandlers.virtualForEach = {

        /**
         * Binding init callback.
         */
        init: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
            var timelineViewModel = bindingContext.$parent;
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
                return $('.timeline-container__panel').offset().left;
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
                var index = bindingContext.$data.index;
                // allows us to track horizontal scrolling
                var o = offset();
                var topBoundry = windowPosition();
                var bottomBoundry = windowPosition() + $(window).height() + 40;
                var leftBoundry = tcOffset.left;   
                var rightBoundry = $timelineCont.width() + leftBoundry;

                ko.utils.arrayForEach(config.data(), function(cron) {
                    if (!created[cron.schedule_id]) {
                        var cronOffset = preCalculateOffset(timelineViewModel, cron, tcOffset, index);
                        if (isInBounds(cronOffset)) {
                            var cronElement = clone.clone().children();
                            ko.applyBindingsToDescendants(
                                bindingContext.createChildContext(cron),
                                cronElement[0]
                            );
                            created[cron.schedule_id] = {
                                el: cronElement,
                                cron: cron
                            };
                            $(element).append(cronElement);
                        }
                    }
                });

                Object.keys(created).forEach(function(id) {
                    var cronOffset = preCalculateOffset(timelineViewModel, created[id].cron, tcOffset, index);
                    if (!isInBounds(cronOffset)) {
                        created[id].el.remove();
                        delete created[id];
                    }
                });

                function isInBounds(cronOffset) {
                    var cTop = cronOffset.top;
                    var cLeft = cronOffset.left;

                    if (cTop > topBoundry && cTop <= bottomBoundry) {
                        if (cLeft > leftBoundry && cLeft <= rightBoundry) {
                            return true;
                        }
                    }
                    return false;
                }
            };

            config.data.subscribe(function() {
                Object.keys(created).forEach(function(id) {
                    created[id].el.remove();
                    delete created[id];
                });
                refresh();
            });

            ko.computed(refresh).extend({ rateLimit: 500 });
            return { controlsDescendantBindings: true };
        }
    };

    renderer.addNode('virtualForEach');
    ko.virtualElements.allowedBindings.virtualForEach = true;
});
