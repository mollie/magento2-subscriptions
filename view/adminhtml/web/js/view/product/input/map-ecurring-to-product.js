/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/ui-select',
    'ko',
    'underscore'
], function (
    Element,
    ko,
    _
) {
    return Element.extend({
        defaults: {
            rows: ko.observableArray([]),
        },

        initialize: function () {
            this._super();

            this.parseInitialValue();
            this.rows.subscribe(function () { this.updateValue() }.bind(this));

            return this;
        },

        parseInitialValue: function () {
            if (typeof this.value() != 'string') {
                this.addRow('', '');
                return;
            }

            var json = JSON.parse(this.value());

            _.each(json, function (row) {
                this.addRow(row.productId, row.name);
            }.bind(this));

            if (!this.rows().length) {
                this.addRow('', '');
            }
        },

        updateValue: function () {
            var rows = this.rows().map( function (row) {
                return {
                    productId: row.productId(),
                    name: row.name(),
                }
            });

            // Remove empty & non complete rows
            rows = rows.filter( function (row) {
                return row.productId && row.name;
            });

            this.value(JSON.stringify(rows));
        },

        add: function () {
            this.addRow('', '');
        },

        remove: function (index) {
            this.rows.splice(index, 1);
        },

        addRow: function (productId, name) {
            var productIdObservable = ko.observable(productId);
            var nameObservable = ko.observable(name);

            productIdObservable.subscribe(function () { this.updateValue() }.bind(this));
            nameObservable.subscribe(function () { this.updateValue() }.bind(this));

            this.rows.push({
                productId: productIdObservable,
                name: nameObservable,
            });
        }
    });
});

