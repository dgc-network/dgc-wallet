/* global payment_param */

jQuery(function ($) {
    $('#wc-payment-transaction-details').DataTable(
            {
                responsive: true,
                searching: false,
                order: [[0, 'desc']],
                language: {
                    emptyTable: payment_param.i18n.emptyTable,
                    lengthMenu: payment_param.i18n.lengthMenu,
                    info: payment_param.i18n.info,
                    infoEmpty : payment_param.i18n.infoEmpty,
                    paginate: payment_param.i18n.paginate
                }
            }
    );
    $('.dgc-payment-select2').selectWoo({
        language: {
            inputTooShort: function () {
                if (payment_param.search_by_user_email) {
                    return payment_param.i18n.non_valid_email_text;
                }
                return payment_param.i18n.inputTooShort;
            },
            noResults: function () {
                if (payment_param.search_by_user_email) {
                    return payment_param.i18n.non_valid_email_text;
                } 
                return payment_param.i18n.no_resualt;
            },
            searching: function (){
                return payment_param.i18n.searching;
            }
        },
        minimumInputLength: 3,
        ajax: {
            url: payment_param.ajax_url,
            dataType: 'json',
            type: 'POST',
            quietMillis: 50,
            data: function (term) {
                return {
                    action: 'dgc-payment-user-search',
                    autocomplete_field: 'ID',
                    term: term.term
                };
            },
            processResults: function (data) {
                // Tranforms the top-level key of the response object from 'items' to 'results'
                return {
                    results: $.map(data, function (item) {
                        return {
                            id: item.value,
                            text: item.label
                        };
                    })
                };
            }
        }
    });
});