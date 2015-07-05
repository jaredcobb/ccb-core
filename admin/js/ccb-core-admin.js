(function( $ ) {

  'use strict';

  var ccbCoreAdmin = {

    syncPollId : '',

    initialize : function() {

      this.syncPollId = setInterval(this.pollForActiveSync, 10000);
      this.pollForActiveSync();

      $('.test-login-wrapper .button').on('click', event, this.testCredentials);

      $('.sync-wrapper .button').on('click', event, this.syncData);

      var switches = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
      switches.forEach(function(html) {
        var switchery = new Switchery(html);
      });

      var sliders = Array.prototype.slice.call(document.querySelectorAll('.js-range'));
      sliders.forEach(function(html) {
        var min = $(html).data('min');
        var max = $(html).data('max');
        var start = $(html).val();
        var powerange = new Powerange(html, {"min":min, "max":max, "hideRange":true, "start":start});
      });

      $('.js-switch, .date-range-type').on('change', this.refreshEnabledFields).change();

      $('.js-range').on('change', function() {
        var siblingSelector = '.' + $(this).data('sibling');
        $(siblingSelector).val($(this).val());
      }).change();

      $('.datepicker').pickadate({
        format: 'mmm d, yyyy',
        formatSubmit: 'yyyy-mm-dd',
        hiddenName: true
      });

      $('.ccb-core-tooltip').tipr({
        "mode":"top"
      });

    },

    refreshEnabledFields : function() {

      $('[data-requires]').each(function() {
        //var requiredElements = $(this).data('requires').split(' ');
        var displayField = true;
        var requiresObject = $(this).data('requires');

        if (typeof requiresObject === 'object') {
          for (var key in requiresObject) {
            if (requiresObject.hasOwnProperty(key)) {

              var requiredElement = $("[name='ccb_core_settings[" + key + "]']");
              if (requiredElement.is(':checkbox')) {
                if (!requiredElement.is(':checked')) {
                  displayField = false;
                  break;
                }
              }
              else if (requiredElement.is(':radio')) {
                requiredElement = $("[name='ccb_core_settings[" + key + "]']:checked");
                if (requiredElement.val() !== requiresObject[key])
                  displayField = false;
                  break;
              }

            }
          }
        }

        if (displayField === true) {
          $(this).parents('tr').show();
        }
        else {
          $(this).parents('tr').hide();
        }

      });
    },

    updateLatestSync : function() {

      var data = {
        'action': 'get_latest_sync',
        'nextNonce': CCB_CORE_SETTINGS.nextNonce
      };

      $.post(ajaxurl, data, function(response) {

        var $latestSyncMessageWrapper = $('.ccb-core-latest-results');
        var labelContent = '<b>Latest Sync Results</b><br>' + response.description;

        $latestSyncMessageWrapper.removeClass('error notice updated').addClass(response.style);
        $latestSyncMessageWrapper.empty().append(labelContent);

      });

    },

    pollForActiveSync : function() {

      var data = {
        'action': 'poll_sync',
        'nextNonce': CCB_CORE_SETTINGS.nextNonce
      };

      $.post(ajaxurl, data, function(response) {
        if ( response.syncInProgress == false ) {
          clearInterval(ccbCoreAdmin.syncPollId);
          ccbCoreAdmin.updateLatestSync();

          var $spinner = $('.sync-wrapper .spinner');
          var $syncButtons = $('.sync-wrapper .button');
          var $syncMessages = $('div.in-progress-message');

          $spinner.removeClass('is-active');
          $syncButtons.removeClass('disabled');
          $syncMessages.remove();

        }
      });

    },

    testCredentials : function(event) {
      event.preventDefault();
      var $clickedButton = $(this);
      if ( $clickedButton.hasClass('disabled') ) {
        return false;
      }

      var $spinner = $('.test-login-wrapper .spinner');
      var $testLoginWrapper = $('.test-login-wrapper');

      $clickedButton.addClass('disabled');
      $spinner.addClass('is-active');
      $testLoginWrapper.find('.ajax-message').remove();

      var data = {
        'action': 'test_credentials',
        'nextNonce': CCB_CORE_SETTINGS.nextNonce
      };

      $.post(ajaxurl, data, function(response) {

        $clickedButton.removeClass('disabled');
        $spinner.removeClass('is-active');

        if (response.success === false) {
          $testLoginWrapper.append('<div class="ajax-message error">' + response.message + '</div>');
        }
        else if (typeof response.services !== 'undefined' && response.services.length > 0) {

          $.each(response.services, function( index, value ){

            var divClass = (value.success === true ? 'updated' : 'error');
            $testLoginWrapper.append('<div class="ajax-message ' + divClass + '"><strong>' + value.label + '</strong>: ' + value.message + '</div>');

          });

        }

      });
    },

    syncData : function(event) {

      event.preventDefault();
      var $clickedButton = $(this);

      if ( $clickedButton.hasClass('disabled') ) {
        return false;
      }

      var $syncWrapper = $clickedButton.parents('.sync-wrapper');
      var $spinner = $syncWrapper.find('.spinner');

      $clickedButton.addClass('disabled');
      $spinner.addClass('is-active');

      var data = {
        'action': 'sync',
        'nextNonce': CCB_CORE_SETTINGS.nextNonce
      };

      $.post(ajaxurl, data, function(response) {
        $syncWrapper.append('<div class="in-progress-message ajax-message updated">Syncronization in progress... You can safely navigate away from this page while we work hard in the background. (It should be just a moment).</div>');
        ccbCoreAdmin.syncPollId = setInterval(ccbCoreAdmin.pollForActiveSync, 10000);
      });

    }

  };

  $(function() {

    ccbCoreAdmin.initialize();

  });

})( jQuery );

