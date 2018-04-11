define([
    'underscore',
    'uiLayout',
    'Magento_Ui/js/lib/spinner',
    'rjsResolver',
    'moment',
    'uiCollection'
], function (_, layout, loader, resolver, moment, Collection) {
    'use strict';

    return Collection.extend({
        defaults: {
        	dateFormat: 'YYYY-MM-DD HH:mm:ss',
            ignoreTmpls: {
                templates: false,
                childDefaults: true
            },
            template: 'cronjobManager/timeline/container',
            imports: {
                rows: '${$.parentName}_data_source:data'
            },
            listens: {
                '${ $.provider }:reload': 'onBeforeReload',
                '${ $.provider }:reloaded': 'onDataReloaded'
            },
            range: {}
        },

        /**
         * Initializes Listing component.
         *
         * @returns {Listing} Chainable.
         */
        initialize: function () {
            this._super()
            return this;
        },

        /**
         * Initializes observable properties.
         *
         * @returns {Listing} Chainable.
         */
        initObservable: function () {
            this._super()
                .track({
                    rows: [],
                    range: {}
                });

            return this;
        },
        
        /**
         * Updates data of a range object,
         * e.g. total hours, first hour and last hour, etc.
         *
         * @returns {Object} Range instance.
         */
        updateRange: function () {
            var firstHour    = this._getFirstHour(),
                lastHour     = this._getLastHour(),
                totalHours   = lastHour.diff(firstHour, 'hours'),
                hours        = [],
                i            = -1;

            while (++i <= totalHours) {
                hours.push(firstHour.add(1, 'hour')
                    .format(this.dateFormat));
            }

            return _.extend(this.range, {
                hours:       hours,
                totalHours:  totalHours,
                firstHour:   firstHour,
                lastHour:    moment(_.last(hours)),
            });
        },

        /**
         * Converts unix timestamp to moment object
         *        
         * @param {String} dateStr
         * @returns {Moment}
         */
        createDate: function (dateStr) {
            return moment(moment.unix(dateStr));
        },

        /**
         * Returns date which is closest to the current hour
         *
         * @private
         * @returns {Moment}
         */
        _getFirstHour: function () {
            var firstHour = this.rows[0].range.first;
            var first = this.createDate(firstHour); 
            return first.startOf('hour');
        },

        /**
         * Returns the most distant date
         * specified in available records.
         *
         * @private
         * @returns {Moment}
         */
        _getLastHour: function () {
            var lastHour = this.rows[0].range.last;
            var last = this.createDate(lastHour); 
            return last.add(1, 'hour').startOf('hour');
        },

        /**
         * Hides loader.
         */
        hideLoader: function () {
            loader.get(this.name).hide();
        },

        /**
         * Shows loader.
         */
        showLoader: function () {
            loader.get(this.name).show();
        },

        /**
         * Handler of the data providers' 'reload' event.
         */
        onBeforeReload: function () {
            this.showLoader();
        },

        /**
         * Handler of the data providers' 'reloaded' event.
         */
        onDataReloaded: function () {
            resolver(this.hideLoader, this);
            this.updateRange();
        }
    });
});

