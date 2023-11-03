define([
    'underscore',
    'jquery',
    'ko',
    'uiLayout',
    'Magento_Ui/js/lib/spinner',
    'rjsResolver',
    'uiRegistry',
    'moment',
    'uiCollection',
    '../lib/knockout/bindings/bootstrapExt',
], function (_, $, ko, layout, loader, resolver, registry, moment, Collection) {
    'use strict';

    return Collection.extend({
        defaults: {
            timeframeFormat: 'MM/DD HH:mm',
            dateFormat: 'MM/DD HH:mm',
            template: 'EthanYehuda_CronjobManager/timeline/timeline',
            detailsTmpl: 'EthanYehuda_CronjobManager/timeline/details',
            imports: {
                rows: '${$.parentName}_data_source:data'
            },
            listens: {
                '${ $.provider }:reload': 'onBeforeReload',
                '${ $.provider }:reloaded': 'onDataReloaded'
            },
            range: {},
            scale: 8,
            minScale: 3,
            maxScale: 15,
            step: 1,
            width: 0,
            now: 0,
            total: 0,
            transformedRows: [],
            tracks: {
                rows: true,
                range: true,
                width: true,
                now: true,
                total: true,
                scale: true
            }
        },

        /**
         * Initializes Listing component.
         *
         * @returns {Listing} Chainable.
         */
        initialize: function () {
            this._super();
            this.initTrackable();
            return this;
        },

        /**
         * Initializes observable properties.
         *
         * @returns {Listing} Chainable.
         */
        initObservable: function () {
            this._super()
                // fastForEach only takes observables
                // we must NOT use ES5 get/set accessor descriptors
                .observe('transformedRows');
            return this;
        },

        initTrackable: function () {
            var self = this;
            this.on('scale', function() {
                self.updateTimelineWidth();
                self.setNow();
            });
        },

        /**
         * Generates offset in pixels relative to the beggining of
         * the timeline
         *
         * @param {Object} job - cron record
         * @param {boolean} asInt
         * @return {String}
         */
        getOffset: function (job, asInt) {
            var startTime = job.executed_at || job.scheduled_at;
            var firstHour = this.getFirstHour(false);
            var offset = this.diff(startTime, firstHour) / this.scale;
            if (offset < 0) {
                offset = 0;
            }

            if (asInt == true) {
                return offset;
            }
            return offset + 'px';
        },

        getCronWidth: function (job) {
            var minWidth = 3,
                timezoneOffset = new Date().getTimezoneOffset() * 60,
                startTime = job.executed_at || job.scheduled_at || job.created_at,
                start = new Date(startTime).getTime() / 1000,
                end = new Date(job.finished_at).getTime() / 1000,
                now = (new Date().getTime() / 1000) + timezoneOffset,
                duration = 0;

            if (job.finished_at == null && job.status == 'running' && start != 0) {
                duration = (now - start) / this.scale;
            }

            if (moment(end).isValid() && job.finished_at != null) {
                duration = (end - start) / this.scale;
            }
            duration = Math.round(duration);
            duration = duration > minWidth ? duration : minWidth;
            job.timelineWidth = duration;
            return duration;
        },

        /**
         * Calculates the width of the timeline
         * and binds it with the trackable width property
         */
        updateTimelineWidth: function () {
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

            return first + ' - ' + last;
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
         * @param {boolean} useMoment
         * @returns {Moment}
         */
        getFirstHour: function (useMoment) {
            var firstHour = this.rows[0].range.first;
            if (useMoment == null || useMoment) {
                var first = this.createDate(firstHour);
                return first.startOf('hour');
            }
            return new Date(new Date(new Date(firstHour * 1000).setMinutes(0)).setSeconds(0));
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

        diff: function(startTime, endTime) {
            var timezoneOffset = new Date().getTimezoneOffset() * 60;
            startTime = (new Date(startTime).getTime() / 1000) - timezoneOffset;
            endTime = (new Date(endTime).getTime() / 1000);
            return (startTime - endTime);
        },

        /**
         * format time entry
         *
         * @returns {String}
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
            loader.get('timeline_container.timeline_panel').show();
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
                loader.get('timeline_container.timeline_panel').hide();
                return;
            }
            this.total = this.rows[0].total;
            this.updateRange();
            this.updateTimelineWidth();
            this.setNow();
            this.transformObject(this.rows);

            $('.timeline-container').animate({
                scrollLeft: (this.now - ($('.timeline-container').width() / 3))
            }, 500);
        },

        reloader: function () {
            // Unregister all virtual foreach events
            // so we don't overlap materializations
            $(window).off();
            $('.timeline-container__panel').off();
            resolver(this.reloadHandler, this);
        },

        reloadHandler: function () {
            registry.get(this.provider).reload({
                refresh: true
            });
        },

        transformObject: function (obj) {
            var properties = [];
            var index = 0;
            ko.utils.objectForEach(obj, function (key, value) {
                if (key == 'showTotalRecords' && typeof value == 'boolean') {
                    return;
                }
                properties.push({ index: index, key: key, value: value });
                index++;
            });
            // we don't need the range key, which is stored
            // in the first element
            properties.shift();
            this.transformedRows(properties);
        },

        /**
         * Handles dragging functionality on the timeline window
         */
        afterTimelineRender: function () {
            resolver(this.hideLoader, this);
            var clicked = false,
                scrollVertical = true,
                scrollHorizontal = true,
                clickY,
                clickX;

            function updateScrollPos(e, el) {
                $('html').css('cursor', 'move');
                var $el = $(el);
                scrollVertical && $(window).scrollTop(($(window).scrollTop() + (clickY - e.pageY)));
                scrollHorizontal && $el.scrollLeft(($el.scrollLeft() + (clickX - e.pageX)));
            }

            $('.timeline-container').on({
                'mousemove': function(e) {
                    clicked && updateScrollPos(e, this);
                },

                'mousedown': function(e) {
                    clicked = true;
                    clickY = e.pageY;
                    clickX = e.pageX;
                },

                'mouseup': function() {
                    clicked = false;
                    $('html').css('cursor', 'auto');
                }
            });
        }
    });
});
