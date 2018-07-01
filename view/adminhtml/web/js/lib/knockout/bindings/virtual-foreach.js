/**
 * Based on Daniel Earwicker's virtualized scolling
 * https://smellegantcode.wordpress.com/2012/12/26/virtualized-scrolling-in-knockout-js/
 */
define([
    'ko',
    'jquery',
    'Magento_Ui/js/lib/knockout/template/renderer',
    'Magento_Ui/js/lib/view/utils/raf'
], function (ko, $, renderer, raf) {
    'use strict';

    window.cancelAnimationFrame = window.cancelAnimationFrame
        || window.mozCancelAnimationFrame
        || function(requestID) {
            clearTimeout(requestID);
        };

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

    /**
     * Get's the offset relative to the window for a cron
     *
     * @param {Object} viewModel
     * @param {Object} cron
     * @param {Object} tcOffset - timeline container offset results
     * @param {int} i - iterations in the virtualForEach
     * @param {Object} - panelOffset
     * @return {Object} cronOffset - top and left 
     */
    var preCalculateOffset = function(viewModel, cron, tcOffset, i, panelOffset) {
        var cronOffset = {};

        /////////////////Vertial Offset/////////////////
        var rowHoursOffset = 31;
        var rowHeight = 40;
        var rowHeightOffset = i * rowHeight;
        cronOffset.top = tcOffset.top + rowHeightOffset + rowHoursOffset; 
        ///////////////Horizontal Offset////////////////
        var timeOffset = viewModel.getOffset(cron, true);
        cronOffset.left = timeOffset + panelOffset();

        return cronOffset;
    }

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
            var $timelinePanel = $('.timeline-container__panel');
            var tcOffset = $timelineCont.offset();

            // timeline panel offset
            var panelOffset = simulatedObservable($timelinePanel, function() {
                return $timelinePanel.offset().left;
            });

            // record of all materialized rows
            var created = {};

            /**
             * Responsible for materializing any cron jobs that
             * are currently visible
             */
            var refresh = function() {
                var index = bindingContext.$data.index;
                var topBoundry = $(window).scrollTop();
                var bottomBoundry = topBoundry + $(window).height() + 40;
                var leftBoundry = tcOffset.left;   
                var rightBoundry = $timelineCont.width() + leftBoundry;

                // flag to check if entire row is in bounds
                var isVerticallyInBounds = true;

                var crons = config.data();
                for (var i = 0; i < crons.length; i++) {
                    var cron = crons[i];
                    if (!created[cron.schedule_id]) {
                        var cronOffset = preCalculateOffset(timelineViewModel, cron, tcOffset, index, panelOffset);
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
                        if (!isVerticallyInBounds) {
                            break;
                        }
                    }
                };

                // Deletes all crons that are out of bounds
                Object.keys(created).forEach(function(id) {
                    var cronOffset = preCalculateOffset(timelineViewModel, created[id].cron, tcOffset, index, panelOffset);
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
                        return false;
                    }
                    isVerticallyInBounds = false;
                    return false;
                }
            };

            config.data.subscribe(function() {
                Object.keys(created).forEach(function(id) {
                    created[id].el.remove();
                    delete created[id];
                });
                raf(refresh);
            });

            var windowTimer = null;
            $(window).on('scroll', function() {
                if (windowTimer !== null) {
                    clearTimeout(windowTimer);
                }
                windowTimer = setTimeout(function() {
                    raf(refresh); 
                }, 1000);
            });

            var panelTimer = null;
            $('.timeline-container__panel').on('scroll mouseup', function() {
                if (panelTimer !== null) {
                    clearTimeout(panelTimer);
                }
                panelTimer = setTimeout(function() {
                    raf(refresh); 
                }, 1000);
            });

            // raf(refresh);
            return { controlsDescendantBindings: true };
        }
    };

    renderer.addNode('virtualForEach');
    ko.virtualElements.allowedBindings.virtualForEach = true;
});
