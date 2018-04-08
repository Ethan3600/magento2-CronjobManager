define([
    'uiLayout',
    'Magento_Ui/js/lib/spinner',
    'rjsResolver',
    'uiCollection'
], function (layout, loader, resolver, Collection) {
    'use strict';

    return Collection.extend({
        defaults: {
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
            this._super()
                .track({
                    rows: [],
                });

            return this;
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
        }
    });
});

