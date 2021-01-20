jQuery(function ($) {
    $('#dgc_payment_referrals_referring_visitors_limit_duration').on('change', function(){
        if('0' === $(this).val()){
            $('#dgc_payment_referrals_referring_visitors_limit').closest('tr').hide();
        } else{
            $('#dgc_payment_referrals_referring_visitors_limit').closest('tr').show();
        }
    }).change();
    $('#dgc_payment_referrals_referring_signups_limit_duration').on('change', function(){
        if('0' === $(this).val()){
            $('#dgc_payment_referrals_referring_signups_limit').closest('tr').hide();
        } else{
            $('#dgc_payment_referrals_referring_signups_limit').closest('tr').show();
        }
    }).change();
});