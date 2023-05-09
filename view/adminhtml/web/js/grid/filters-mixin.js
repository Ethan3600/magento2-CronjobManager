define(function () {
    'use strict';
    return function (target) {
        return target.extend({
            defaults: {
                templates: {
                    filters: {
                        textWithDatalist: {
                            component: 'EthanYehuda_CronjobManager/js/form/element/inputWithDatalist',
                            options: '${ JSON.stringify($.$data.column.options) }',
                            template: 'ui/grid/filters/field'
                        }
                    }
                }
            }
        });
    };
});
