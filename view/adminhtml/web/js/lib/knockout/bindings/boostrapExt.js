define([
    'underscore',
    'Magento_Ui/js/lib/knockout/bindings/bootstrap',
    'require',
    './foreach-prop'
], function (_, bootstrap, require) {
   return _.extend(bootstrap, {
       foreachProp: require('./foreach-prop')
   }) 
});
