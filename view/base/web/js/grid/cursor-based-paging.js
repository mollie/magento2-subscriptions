/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'ko',
    'underscore',
    'mageUtils',
    'uiLayout',
    'Magento_Ui/js/grid/paging/paging'
], function (ko, _, utils, layout, Element) {
    'use strict';

    return Element.extend({
        defaults: {
            template: 'Mollie_Subscriptions/grid/cursor-based-paging',

            imports: {
                nextID: '${ $.provider }:data.nextID',
                previousID: '${ $.provider }:data.previousID',
            },

            exports: {
                current: false,
                offsetID: '${ $.provider }:params.offsetID'
            },
        },

        initObservable: function () {
            this._super()
                .observe([
                    'offsetID',
                ])
                .track([
                    'nextID',
                    'previousID',
                ]);

            return this;
        },

        hasPrevious: function () {
            return this.previousID !== null;
        },

        hasNext: function () {
            return this.nextID !== null;
        },

        next: function () {
            console.log('next', this.nextID);
            this.offsetID(this.nextID);

            return this;
        },

        prev: function () {
            console.log('previous', this.previousID);
            this.offsetID(this.previousID);

            return this;
        }
    });
});
