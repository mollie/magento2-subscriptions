/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/model/messageList',
    'mage/translate'
], function (
    messageList,
    $t
) {
    return function (originalComponent) {
        if (window.checkoutConfig.mollie &&
            window.checkoutConfig.mollie.subscriptions &&
            window.checkoutConfig.mollie.subscriptions.has_subscription_products_in_cart
        ) {
            messageList.addSuccessMessage({
                message: $t('Not all payments methods are available when ordering subscription products.')
            });
        }

        return originalComponent;
    };
});
