require(
[
	'Magento_Ui/js/lib/validation/validator',
	'jquery',
	'mage/translate'
],
function(validator, $) {
    validator.addRule(
        'cron-validation',
        function(value) {
        	var regex = /^$|(\*|[0-5]?[0-9]|\*\/[0-9]+)\s+(\*|1?[0-9]|2[0-3]|\*\/[0-9]+)\s+(\*|[1-2]?[0-9]|3[0-1]|\*\/[0-9]+)\s+(\*|[0-9]|1[0-2]|\*\/[0-9]+)\s+(\*\/[0-9]+|\*|[0-7])\s*(\*\/[0-9]+|\*|[0-9]+)?/i;
        	return regex.test(value);
        }, $.mage.__('Invalid cron expression. Please try again.')
    );
});
