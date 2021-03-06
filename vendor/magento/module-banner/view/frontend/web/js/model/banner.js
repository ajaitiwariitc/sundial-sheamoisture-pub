/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'underscore',
    'ko',
    'jquery/jquery-storageapi'
], function ($, _, ko) {
    'use strict';

    var options,
        storage = $.initNamespaceStorage('mage-banners-cache-storage').localStorage,

        /**
         * Cache invalidation
         */
        invalidateCacheBySessionTimeOut = function () {
            var date = new Date();

            if (new Date($.localStorage.get('mage-banners-cache-timeout')) < new Date()) {
                date = new Date(Date.now() + 30 * 1000);

                storage.removeAll();
                $.localStorage.set('mage-banners-cache-timeout', date);
            }
        },
        dataProvider = {

            /**
             * Request data from storage
             *
             * @param {Array} sectionNames
             * @returns {Object}
             */
            getFromStorage: function (sectionNames) {
                var result = {};

                _.each(sectionNames, function (sectionName) {
                    result[sectionName] = storage.get(sectionName);
                });

                return result;
            },

            /**
             * Request data from server
             *
             * @param {Array} sectionNames
             * @returns {Object}
             */
            getFromServer: function (sectionNames) {
                var parameters = _.isArray(sectionNames) ? {
                    sections: sectionNames.join(',')
                } : [];

                return $.getJSON(options.sectionLoadUrl, parameters).fail(function (jqXHR) {
                    throw new Error(jqXHR);
                });
            }
        },
        buffer = {
            data: {},

            /**
             * Binding parameter
             *
             * @param {String} sectionName
             */
            bind: function (sectionName) {
                this.data[sectionName] = ko.observable({});
            },

            /**
             *
             * @param {String} sectionName
             * @returns {Object}
             */
            get: function (sectionName) {
                if (!this.data[sectionName]) {
                    this.bind(sectionName);
                }

                return this.data[sectionName];
            },

            /**
             * Get keys
             *
             * @returns {Array}
             */
            keys: function () {
                return _.keys(this.data);
            },

            /**
             * Notify storage
             *
             * @param {String} sectionName
             * @param {Object} sectionData
             */
            notify: function (sectionName, sectionData) {
                if (!this.data[sectionName]) {
                    this.bind(sectionName);
                }
                this.data[sectionName](sectionData);
            },

            /**
             * Update sections
             *
             * @param {Array} sections
             */
            update: function (sections) {
                _.each(sections, function (sectionData, sectionName) {
                    storage.set(sectionName, sectionData);
                    buffer.notify(sectionName, sectionData);
                });
            }
        },
        banner = {

            /**
             * Initialization
             */
            init: function () {
                if (_.isEmpty(storage.keys())) {
                    this.reload([]);
                } else {
                    _.each(dataProvider.getFromStorage(storage.keys()), function (sectionData, sectionName) {
                        buffer.notify(sectionName, sectionData);
                    });
                }
            },

            /**
             * Get data
             *
             * @param {String} sectionName
             * @returns {*|Object}
             */
            get: function (sectionName) {
                return buffer.get(sectionName);
            },

            /**
             * Set data
             *
             * @param {String} sectionName
             * @param {Object} sectionData
             */
            set: function (sectionName, sectionData) {
                var data = {};

                data[sectionName] = sectionData;
                buffer.update(data);
            },

            /**
             * Reloading from storage or server
             *
             * @param {Array} sectionNames
             * @returns {Object}
             */
            reload: function (sectionNames) {
                return dataProvider.getFromServer(sectionNames).done(function (sections) {
                    buffer.update(sections);
                });
            },

            /**
             * Init helper
             *
             * @param {Array} settings
             */
            'Magento_Banner/js/model/banner': function (settings) {
                options = settings;
                invalidateCacheBySessionTimeOut(settings);
                banner.init();
            }
        };

    //TODO: remove global change, in this case made for initNamespaceStorage
    $.cookieStorage.setConf({
        path: '/'
    });

    return banner;
});
