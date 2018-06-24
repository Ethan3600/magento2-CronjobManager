var config = {
	paths: {
        'cronjobManager/template': 'EthanYehuda_CronjobManager/templates'
    },
    shim: {
        'EthanYehuda/js/timeline/timeline': {
            'deps': ['EthanYehuda/js/lib/knockout/bindings/virtual-foreach']
        }
    }
};
