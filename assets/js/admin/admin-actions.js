jQuery(function ($) {
    $('#dgc_wallet_referrals_referring_visitors_limit_duration').on('change', function(){
        if('0' === $(this).val()){
            $('#dgc_wallet_referrals_referring_visitors_limit').closest('tr').hide();
        } else{
            $('#dgc_wallet_referrals_referring_visitors_limit').closest('tr').show();
        }
    }).change();
    $('#dgc_wallet_referrals_referring_signups_limit_duration').on('change', function(){
        if('0' === $(this).val()){
            $('#dgc_wallet_referrals_referring_signups_limit').closest('tr').hide();
        } else{
            $('#dgc_wallet_referrals_referring_signups_limit').closest('tr').show();
        }
    }).change();
});