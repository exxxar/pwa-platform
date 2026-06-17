export default {
    show(productItem) {
        window.dispatchEvent(new CustomEvent("product-info-event", {
            detail: {
                product: productItem,
            }
        }));
    },
}
