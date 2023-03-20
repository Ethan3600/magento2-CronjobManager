define([
    'underscore',
    'Magento_Ui/js/lib/knockout/bindings/bootstrap',
    'require',
    './virtual-foreach'
], function (_, bootstrap, require) {
    return _.extend(bootstrap, {
        virtualForEach: require('./virtual-foreach')
    });
});
