var config = {
	paths: {
        'cronjobManager/template': 'EthanYehuda_CronjobManager/templates'
    },
    shim: {
        'EthanYehuda_CronjobManager/js/timeline/timeline': {
            'deps': ['EthanYehuda_CronjobManager/js/lib/knockout/bindings/virtual-foreach']
        }
    }
};
