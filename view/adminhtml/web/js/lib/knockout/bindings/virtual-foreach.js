define([
    'ko',
    'Magento_Ui/js/lib/knockout/template/renderer'
], function (ko, renderer) {
    'use strict';

        ko.bindingHandlers.virtualForEach = {

            /**
             * Binding init callback.
             */
            init: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
            }
        };

    renderer.addNode('virtualForEach');
});
