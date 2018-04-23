define([
    'ko',
    'Magento_Ui/js/lib/knockout/template/renderer'
], function (ko, renderer) {
    'use strict';

        ko.bindingHandlers.foreachProp = {
            transformObject: function (obj) {
                var properties = [];
                ko.utils.objectForEach(obj, function (key, value) {
                    properties.push({ key: key, value: value });
                });
                return properties;
            },

            /**
             * Binding init callback.
             */
            init: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
                var properties = ko.pureComputed(function () {
                    var obj = ko.utils.unwrapObservable(valueAccessor());
                    return ko.bindingHandlers.foreachProp.transformObject(obj);
                });
                ko.applyBindingsToNode(element, { foreach: properties }, bindingContext);
                return { controlsDescendantBindings: true };
            }
        };

    renderer.addAttribute('foreachProp');
});
