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

    ko.bindingHandlers.virtualForEach = {

        /**
         * Binding init callback.
         */
        init: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
            var clone = $(element).clone();
            $(element).empty();

            var config = ko.utils.unwrapObservable(valueAccessor());
            if (config.data == null) {
                return;
            }
            var rowHeight = 40;

            ko.computed(function() {
                $(element).css({
                    height: config.data.length * rowHeight
                });
            });

            var offset = simulatedObservable(element, function() {
                return $(element).offset().top;
            });

            var windowHeight = simulatedObservable(element, function() {
                return window.innerHeight;
            });

            var created = {};

            var refresh = function() {
                var o = offset();
                var data = config.data;
                var top = Math.max(0, Math.floor(-o / rowHeight) - 10);
                var bottom = Math.min(data.length, Math.ceil((-o + windowHeight()) / rowHeight));

                for (var row = top; row < bottom; row++) {
                    if (!created[row]) {
                        var rowDiv = $('<div></div>');
                        rowDiv.css({
                            position: 'absolute',
                            height: config.rowHeight,
                            left: 0,
                            right: 0,
                            top: row * config.rowHeight
                        });
                        rowDiv.append(clone.clone().children());
                        ko.applyBindingsToDescendants(context.createChildContext(data[row]), rowDiv[0]);
                        created[row] = rowDiv;
                        $(element).append(rowDiv);
                    }
                }

                Object.keys(created).forEach(function(rowNum) {
                    if (rowNum < top || rowNum >= bottom) {
                        created[rowNum].remove();
                        delete created[rowNum];
                    }
                });
            };

            config.rows.subscribe(function() {
                Object.keys(created).forEach(function(rowNum) {
                    created[rowNum].remove();
                    delete created[rowNum];
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
