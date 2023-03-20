define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            elementTmpl: 'EthanYehuda_CronjobManager/form/element/inputWithDatalist',
            options: []
        }
    });
});
