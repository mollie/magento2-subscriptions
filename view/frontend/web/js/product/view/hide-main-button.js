define([], function () {
    return function (config, element) {
        element.style.display = 'none';
        element.setAttribute('data-hidden-by', 'this element is hidden by `Mollie_Subscriptions/js/product/view/hide-main-button`');

        // Make sure the element stays hidden, even if external javascript tries to show it.
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === "attributes") {
                    mutation.target.style.display = 'none';
                }
            });
        });

        observer.observe(element, {
            attributes: true
        });
    }
});