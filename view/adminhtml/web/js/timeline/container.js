define([
    'underscore',
    'uiLayout',
    'Magento_Ui/js/lib/spinner',
    'rjsResolver',
    'moment',
    'uiCollection',
    '../lib/knockout/bindings/boostrapExt',
], function (_, layout, loader, resolver, moment, Collection) {
    'use strict';

    return Collection.extend({
        defaults: {
            timeframeFormat: 'YYYY-MM-DD HH:mm:ss',
        	dateFormat: 'HH:mm',
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
            range: {},
            scale: 15,
            width: 0,
            tracks: {
                rows: true,
                range: true,
                width: true
            }
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
            this._super();
            return this;
        },

        /**
         * Calculates the width of the timeline
         * and binds it with the trackable width property
         */
        updateTimelineWidth: function() {
            var range = this.rows[0].range;

            var first = moment.unix(range.first); 
            first = first.startOf('hour');

            var last = moment.unix(range.last);
            last = last.add(1, 'hour').startOf('hour');

            this.width = last.diff(first) / this.scale;
        },
        
        /**
         * Updates data of a range object,
         * e.g. total hours, first hour and last hour, etc.
         *
         * @returns {Object} Range instance.
         */
        updateRange: function () {
            var firstHour    = this.getFirstHour(),
                lastHour     = this.getLastHour(),
                totalHours   = lastHour.diff(firstHour, 'hours'),
                hours        = [],
                i            = -1,
                increment    = this.getFirstHour();

            while (++i <= totalHours) {
                hours.push(increment.add(1, 'hour')
                    .format(this.dateFormat));
            }

            this.range = _.extend(this.range, {
                hours:       hours,
                totalHours:  totalHours,
                firstHour:   firstHour,
                lastHour:    lastHour,
                timeframe:   this.getTimeframe(firstHour, lastHour)
            });
        },

        /**
         * Gets timeframe header from range
         *
         * @param {Moment} firstHour
         * @param {Moment} lastHour
         * @returns {String}
         */
        getTimeframe: function (firstHour, lastHour) {
            var first = firstHour.format(this.timeframeFormat),
                last  = lastHour.format(this.timeframeFormat);

            return first + " - " + last; 
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
        getFirstHour: function () {
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
        getLastHour: function () {
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
            if (Object.keys(this.rows).length < 1 
                || this.rows == undefined) {
                return;
            }
            resolver(this.hideLoader, this);
            this.updateRange();
            this.updateTimelineWidth();
        }
    });
});

