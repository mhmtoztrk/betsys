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

    const raw = el.getAttribute('data-texts'); // ham string gelir
    if (!raw) return {};

    try {
        return JSON.parse(raw);
    } catch (e) {
        console.warn('Invalid JSON in data-texts:', raw);
        return {};
    }
}

function betSlipMessage(message, type = 'warning'){
    $('.slip-message').html('<span class="slip-message-'+ type +'">'+ message +'</span>');
}

function emptySlipMessage(){
    $('.slip-message').html('');
}

function activateSlip() {
    $('.slip-overlay').css('visibility', 'hidden');
    $('.deposit-slip-button').prop("disabled",false);
}

function deactivateSlip() {
    $('.slip-overlay').css('visibility', 'unset');
    $('.deposit-slip-button').prop("disabled",true);
}

function activateSlipButton() {
    $('.deposit-slip-button').prop("disabled",false);
    $('.deposit-slip-button').removeClass('deactive-slip-button');
}

function deactivateSlipButton() {
    $('.deposit-slip-button').prop("disabled",true);
    $('.deposit-slip-button').addClass('deactive-slip-button');
}

function activateTotalStakeInput() {
    $('.stake-input').prop("disabled",false);
}

function deactivateTotalStakeInput() {
    $('.stake-input').prop("disabled",true);
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