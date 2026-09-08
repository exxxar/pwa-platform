export default {
    show(productItem, options) {
        window.dispatchEvent(new CustomEvent("product-info-event", {
            detail: {
                product: productItem,
                scrollToOptions: options?.scrollToOptions || false
            }
        }));
    },
}
