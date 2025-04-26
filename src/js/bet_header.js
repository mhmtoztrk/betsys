const SLIP_PAGE_SELECTOR = '.bet-slip-page';
const SLIP_CONTAINER = '#bet-slip-container';
const SLIP_SELECTOR = '.bet-slip';

function slipData(){
    if (!document.querySelector(SLIP_PAGE_SELECTOR)) return false;

    var page = $(SLIP_PAGE_SELECTOR);
    var data = {
        'lang' : page.data('lang'),
        'list_type' : page.data('list_type')
    };
    if (data.list_type === 'detail') data.match_id = page.data('match_id');

    var slipDiv = $(SLIP_SELECTOR);
    data.version = slipDiv.data('version') || 0;
    return data;
}

function bet_action(action, data, callback) {
    var slip_data = slipData();
    data.lang = slip_data.lang;
    data.version = slip_data.version;
    
    $.ajax({
        url: BASE_PATH + '/bet-action/' + action,
        type: 'POST',
        data: data,
        dataType: 'json',
        cache: false,
        success: function(result) {
            if (typeof callback === 'function') {
                callback(result);
            }
        }
    });
}

function applyBetResponse(response) {
    if (response.status) {

        const actions = response.actions;

        if ('slip_html' in actions) {
            $(SLIP_CONTAINER).html(actions.slip_html);
        }

    }
}

function getBetSlipTexts() {
    const el = document.querySelector('.bet-slip');
    if (!el) return {};

    const raw = el.getAttribute('data-texts');
    if (!raw) return {};

    try {
        return JSON.parse(raw);
    } catch (e) {
        console.warn('Invalid JSON in data-texts:', raw);
        return {};
    }
}

function slipAlert(message, type = 'warning') {
    var $alert = $('<div class="slip-alert slip-alert-' + type + '">' + message + '</div>');

    $('body').append($alert);

    $alert.hide().fadeIn(200);

    setTimeout(function() {
        $alert.fadeOut(300, function() {
            $(this).remove();
        });
    }, 2000);
}

function betSlipMessage(message, type = 'warning'){
    $('.slip-message').html('<span class="slip-message-'+ type +'">'+ message +'</span>');
}

function emptySlipMessage(){
    $('.slip-message').html('');
}

function deactivateBetSlip() {
    var slip = $('.bet-slip');

    // Add a disabled class to the slip for visual indication
    slip.addClass('bet-slip-disabled');

    // Disable all form elements inside the slip
    slip.find('input, button, select, textarea').prop('disabled', true);

    // Disable the reset slip link
    slip.find('.reset-slip-button').addClass('disabled').css('pointer-events', 'none');

    // Disable all remove bet buttons
    slip.find('.bet-remove').addClass('disabled').css('pointer-events', 'none');

    // Disable all odds selection elements across the page
    $('.bet-create').addClass('disabled').css('pointer-events', 'none');
}

function activateBetSlip() {
    var slip = $('.bet-slip');

    // Remove the disabled class from the slip
    slip.removeClass('bet-slip-disabled');

    // Enable all form elements inside the slip
    slip.find('input, button, select, textarea').prop('disabled', false);

    // Enable the reset slip link
    slip.find('.reset-slip-button').removeClass('disabled').css('pointer-events', '');

    // Enable all remove bet buttons
    slip.find('.bet-remove').removeClass('disabled').css('pointer-events', '');

    // Enable all odds selection elements across the page
    $('.bet-create').removeClass('disabled').css('pointer-events', '');
}

function activateTotalStakeInput() {
    $('.stake-input').prop("disabled",false);
}

function deactivateTotalStakeInput() {
    $('.stake-input').prop("disabled",true);
}

function isDisabledClick($el, e) {
    if ($el.hasClass('disabled')) {
        e.preventDefault();
        let texts = getBetSlipTexts();
        slipAlert(texts.slip_disabled_error, 'error');
        return true;
    }
    return false;
}

function filter_leagues() {
    var countryFilter = $('#country_filter').val().toLowerCase();
    var leagueFilter = $('#league_filter').val().toLowerCase();

    // Iterate over each country group
    $('.country-group').each(function() {
        var countryName = $(this).find('.country-name').text().toLowerCase();
        var matchCountry = countryName.includes(countryFilter);

        var leagueMatchFound = false;

        // Iterate over each league within the group
        $(this).find('.league-item').each(function() {
            var leagueName = $(this).find('.league-name').text().toLowerCase();

            if (leagueName.includes(leagueFilter)) {
                $(this).show();
                leagueMatchFound = true;
            } else {
                $(this).hide();
            }
        });

        // Check if country matches or any league in the group matches
        if (matchCountry && leagueMatchFound) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}