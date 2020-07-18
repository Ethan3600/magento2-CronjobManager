var config = {
    config: {
        mixins: {
            'Magento_Ui/js/grid/filters/filters': {
                'EthanYehuda_CronjobManager/js/grid/filters-mixin': true
            }
        }
    },
    shim: {
        'EthanYehuda_CronjobManager/js/timeline/timeline': {
            'deps': ['EthanYehuda_CronjobManager/js/lib/knockout/bindings/virtual-foreach']
        }
    }
};
