$(document).ready(function() {

    var slip_data = slipData();

    if(slip_data) {

        var pagePars = {
            'list_type' : slip_data.list_type
        }

        if(slip_data.list_type == 'detail') pagePars.match_id = slip_data.match_id;
        
        // setInterval(function () {

        //     bet_action('update_all', pagePars, function(response) {
        //         applyBetResponse(response);
        //     });

        // }, 2000);
        
    }

    $(document).on('click', '.deposit-slip-button', function(e) {

        emptySlipMessage();

        const value = $('.stake-input').val();

        if (value > 0) {
            if (isDisabledClick($(this), e)) return;

            deactivateBetSlip();
            
            bet_action('submit_slip', {}, function(response) {
                applyBetResponse(response);
            });
            
        }else{

            var texts = getBetSlipTexts();
            betSlipMessage(texts.stake_zero_warning, 'error');

        }

    });

    $(document).on('click', '.reset-slip-button', function(e) {
        if (isDisabledClick($(this), e)) return;
        
        deactivateBetSlip();

        bet_action('reset_slip', {}, function(response) {
            applyBetResponse(response);
        });

    });

    $(document).on('click', '.bet-create', function(e) {
        if (isDisabledClick($(this), e)) return;
        
        deactivateBetSlip();

        const data = {
            'match_id' : $(this).data('match_id'),
            'bet_type_id' : $(this).data('bet_type_id'),
            'bet_value' : $(this).data('bet_value'),
            'odd_value' : $(this).data('odd_value'),
        }

        bet_action('create_bet', data, function(response) {
            applyBetResponse(response);
        });

    });

    $(document).on('click', '.bet-remove', function(e) {
        if (isDisabledClick($(this), e)) return;
        
        deactivateBetSlip();

        const betRow = $(this).closest('.slip-bet');
        
        const data = {
            'match_id' : betRow.data('match_id'),
            'bet_type_id' : betRow.data('bet_type_id'),
            'bet_value' : betRow.data('bet_value'),
        }

        bet_action('remove_bet', data, function(response) {
            applyBetResponse(response);
        });

    });

    let stakeTimer;
    $(document).on('input', '.stake-input', function (e) {
        if (isDisabledClick($(this), e)) return;

        $('.bet-slip-payout').addClass('slip-payout-waiting');
        
        deactivateBetSlip();

        const value = $(this).val();
    
        clearTimeout(stakeTimer);
    
        stakeTimer = setTimeout(function () {
            
            bet_action('update_stake', { stake: value }, function(response) {
                applyBetResponse(response);
            });

        }, 700);

    });

    $(document).on('click', '.load-updated-slip', function() {
        
        deactivateBetSlip();
            
        bet_action('load_updated_slip', {}, function(response) {
            applyBetResponse(response);
        });

    });

    $(document).on('click', '.bet-slip-header', function() {

        $('body').toggleClass('slip-opened');

    });

    $(document).on('click', '.slip-opened', function(e) {

        if ($(e.target).closest('.bet-slip').length > 0) return;

        $('body').toggleClass('slip-opened');

    });
    

    // Trigger the filter function on input change
    $('.league-input').on('input', function() {
        filter_leagues();
    });

    $(document).on('click', '.tab-button', function () {
        var tab = $(this).data('tab');
    
        // Sekme butonlarını güncelle
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
    
        // İçerikleri güncelle
        $('.tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });
    
});
