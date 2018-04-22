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
            timeframeFormat: 'MM/DD HH:mm',
        	dateFormat: 'HH:mm',
            template: 'cronjobManager/timeline/container',
            detailsTmpl: 'cronjobManager/timeline/details',
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
            now: 0,
            tracks: {
                rows: true,
                range: true,
                width: true,
                now: true
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
         * Generates offset in pixels relative to the beggining of
         * the timeline
         *
         * @param {Object} job - cron record
         * @return {String}
         */
        getOffset: function (job) {
            var startTime = job.executed_at || job.scheduled_at,
            offset = (moment.utc(startTime).local()
                .diff(this.getFirstHour(), 'seconds')) 
                / this.scale;
            if (offset < 0) {
                offset = 0;
            }
            return offset + 'px';
        },

        getCronWidth: function (job) {
            return '3px';
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

            this.width = (last.diff(first, 'seconds') + 3600) / this.scale;
        },
        
        /**
         * Updates data of a range object,
         * e.g. total hours, first hour and last hour, etc.
         *
         * @returns {Object} Range instance.
         */
        updateRange: function () {
            var firstHour    = this.getFirstHour(),
                lastHour     = this.getLastHour().add({hours: 1}),
                totalHours   = lastHour.diff(firstHour, 'hours'),
                hours        = [],
                i            = 0,
                increment    = this.getFirstHour().subtract({hours: 1});

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
         * Returns offset relative to the time now
         * 
         * @returns {String}
         */
        setNow: function () {
            this.now = (moment().diff(this.getFirstHour(), 'seconds')) / this.scale;
        },

        /**
         * format time entry
         */
        formatTime: function (dateStr) {
            if (dateStr == null) {
                return false;
            }
            return moment.utc(dateStr).local().format('MM/DD HH:mm:ss');
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
            this.setNow();
        }
    });
});

