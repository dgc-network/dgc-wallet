/* global dgc_wallet_admin_product_param */

jQuery(document).ready(function ($) {
    if (dgc_wallet_admin_product_param.is_hidden) {
        $('tr.post-' + dgc_wallet_admin_product_param.product_id + '.type-product').remove();
    }
});