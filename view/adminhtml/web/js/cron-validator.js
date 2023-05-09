require(
    [
        'Magento_Ui/js/lib/validation/validator',
        'jquery',
        'mage/translate'
    ],
    function (validator, $) {

        const CRON_REGEXES = [
            /^(\*|[0-5]?\d(-[0-5]?\d)?(,[0-5]?\d(-[0-5]?\d)?)*)(\/\d+)?\s+(\*|([01]?\d|2[0-3])(-([01]?\d|2[0-3]))?(,([01]?\d|2[0-3])(-([01]?\d|2[0-3]))?)*)(\/\d+)?\s+(\*|([0-2]?\d|3[01])(-([0-2]?\d|3[01]))?(,([0-2]?\d|3[01])(-([0-2]?\d|3[01]))?)*)(\/\d+)?\s+(\*|(0?\d|1[0-2])(-(0?\d|1[0-2]))?(,(0?\d|1[0-2])(-(0?\d|1[0-2]))?)*)(\/\d+)?\s+(\*|0?[0-7](-0?[0-7])?(,0?[0-7](-0?[0-7])?)*)(\/\d+)?$/i, //Regular regex terms - m h dom mon dow
            /^@(annually|yearly|monthly|weekly|daily|hourly|reboot)$/i, //Predefined cron macros (non-standard)
            /^@every (\d+(ns|us|µs|ms|s|m|h))+$/i, //Other cron macros
        ];

        validator.addRule(
            'cron-validation',
            function (value) {
                if (value === '') {
                    return true;
                }
                for (let regex in CRON_REGEXES) {
                    if (CRON_REGEXES[regex].test(value)) return true;
                }
                return false;
            }, $.mage.__('Invalid cron expression. Please try again.')
        );
    });
