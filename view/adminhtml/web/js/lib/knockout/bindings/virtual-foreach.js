/**
 * Based on Daniel Earwicker's virtualized scolling
 * https://smellegantcode.wordpress.com/2012/12/26/virtualized-scrolling-in-knockout-js/
 */
define([
    'ko',
    'jquery',
    'Magento_Ui/js/lib/knockout/template/renderer',
    'Magento_Ui/js/lib/spinner',
    'Magento_Ui/js/lib/view/utils/raf'
], function (ko, $, renderer, loader, raf) {
    'use strict';

    window.virtualRegistry = [];

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
        cronOffset.right = cronOffset.left + (cron.timelineWidth || 0);

        return cronOffset;
    };

    ko.bindingHandlers.virtualForEach = {

        /**
         * Binding init callback.
         */
        init: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
            var timelineViewModel = bindingContext.$parent;
            var totalTasks = timelineViewModel.transformedRows().length - 1;
            element = element.parentNode;
            var clone = $(element).clone();
            $(element).empty();

            var config = ko.utils.unwrapObservable(valueAccessor());
            if (config.data == null) {
                return;
            }

            // lets make our data into an observable array
            config.data = ko.observableArray(config.data);

            var index = bindingContext.$data.index;
            var $timelineCont = $('.timeline-container');
            var $timelinePanel = $('.timeline-container__panel');
            var tcOffset = $timelineCont.offset();
            // timeline panel offset
            var panelOffset = simulatedObservable($timelinePanel, function() {
                return $timelinePanel.offset().left;
            });

            // record of all materialized rows
            window.created = {};
            var fragment = document.createDocumentFragment();

            /**
             * Responsible for materializing any cron jobs that
             * are currently visible
             */
            var refresh = function() {
                var topBoundry = $(window).scrollTop();
                var bottomBoundry = topBoundry + $(window).height() + 40;
                var leftBoundry = tcOffset.left;
                var rightBoundry = $timelineCont.width() + leftBoundry;

                // flag to check if entire row is in bounds
                var isVerticallyInBounds = true;

                var crons = config.data();
                for (var i = 0; i < crons.length; i++) {
                    var cron = crons[i];
                    if (!window.created[cron.schedule_id]) {
                        var cronOffset = preCalculateOffset(timelineViewModel, cron, tcOffset, index, panelOffset);
                        if (isInBounds(cronOffset)) {
                            var cronElement = clone.clone().children();
                            ko.applyBindingsToDescendants(
                                bindingContext.createChildContext(cron),
                                cronElement[0]
                            );
                            window.created[cron.schedule_id] = {
                                el: cronElement,
                                cron: cron,
                                index: index
                            };
                            fragment.appendChild(cronElement[0]);
                        }
                        if (!isVerticallyInBounds) {
                            break;
                        }
                    }
                }

                window.virtualRegistry[index] = {
                    el: element,
                    frag: fragment
                };

                if (index === totalTasks) {
                    raf(function() {
                        materialize();
                        deMaterialize();
                        loader.get('timeline_container.timeline_panel').hide();
                    });
                }

                function materialize() {
                    for (var i = 0; i < window.virtualRegistry.length; i++) {
                        var row = window.virtualRegistry[i];
                        if (row != null) {
                            row.el.appendChild(row.frag);
                        }
                    }
                    window.virtualRegistry = [];
                }

                function deMaterialize() {
                    // Deletes all crons that are out of bounds
                    Object.keys(window.created).forEach(function(id) {
                        var cronOffset = preCalculateOffset(
                            timelineViewModel,
                            window.created[id].cron,
                            tcOffset,
                            window.created[id].index,
                            panelOffset
                        );
                        if (!isInBounds(cronOffset)) {
                            window.created[id].remove = true;
                        }
                    });
                    Object.keys(window.created).forEach(function(id) {
                        if (window.created[id].remove) {
                            var node = window.created[id].el[0];
                            node.parentNode.removeChild(node);
                            delete window.created[id];
                        }
                    });
                }

                function isInBounds(cronOffset) {
                    var cTop = cronOffset.top;
                    var cLeft = cronOffset.left;
                    var cRight = cronOffset.right;

                    if (cTop > topBoundry && cTop <= bottomBoundry) {
                        if (cLeft > leftBoundry && cLeft <= rightBoundry) {
                            return true;
                        }
                        if (cRight > leftBoundry && cRight <= rightBoundry) {
                            return true;
                        }
                        if (cLeft < leftBoundry && cRight >= rightBoundry) {
                            return true;
                        }
                        return false;
                    }

                    isVerticallyInBounds = false;
                    return false;
                }
            };

            var debounceRefresh = function() {
                if (debounceRefresh.timer) {
                    clearTimeout(debounceRefresh.timer);
                }

                debounceRefresh.timer = setTimeout(function() {
                    raf(refresh);
                }, 150);
            };

            $(window).on('scroll', debounceRefresh);
            $timelinePanel.on('scroll', debounceRefresh);
            $timelineCont.on('scroll', debounceRefresh);

            $timelineCont.on('timeline.ready', function() {
                $(window).trigger('scroll');
                loader.get('timeline_container.timeline_panel').hide();
            });

            if (index === totalTasks) {
                // trigger's materialization after
                // the last virtualForEach has run
                $timelineCont.trigger('timeline.ready');
            }
            return { controlsDescendantBindings: true };
        }
    };

    renderer.addNode('virtualForEach');
    ko.virtualElements.allowedBindings.virtualForEach = true;
});
