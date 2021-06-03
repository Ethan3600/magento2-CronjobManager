require(
    [
        'Magento_Ui/js/lib/validation/validator',
        'jquery',
        'mage/translate'
    ],
    function (validator, $) {

        const CRON_REGEXES = [
            /((((\d+,)+\d+|(\d+(\/|-)\d+)|\d+|\*) ?){5,7})/i, //Regular regex terms
            /@(annually|yearly|monthly|weekly|daily|hourly|reboot)/i, //Predefined cron macros (non-standard)
            /@every (\d+(ns|us|µs|ms|s|m|h))+/i, //Other cron macros
        ];

        validator.addRule(
            'cron-validation',
            function (value) {
                for (let regex in CRON_REGEXES) {
                    if (CRON_REGEXES[regex].test(value)) return true;
                }
                return false;
            }, $.mage.__('Invalid cron expression. Please try again.')
        );
    });
