(function( $ ) {

  'use strict';

  var CCBCoreAdmin = {

    syncPollId : null,
    spinner : '<span class="spinner is-active"></span>',

    initialize : function() {

      this.syncPollId = setInterval(this.pollForActiveSync, 10000);
      this.pollForActiveSync();

      $('.test-credentials-wrapper .button').on('click', window.event, this.testCredentials);

      $('.sync-wrapper .button').on('click', window.event, this.syncData);

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
        var displayField = true;
        var requiresObject = $(this).data('requires');

        if (typeof requiresObject === 'object') {
          for (var key in requiresObject) {
            if (requiresObject.hasOwnProperty(key)) {

              var requiredElement = $("[name='ccb_core_settings[" + key + "]']");
              if (requiredElement.is('input:checkbox')) {
                if (!requiredElement.is(':checked')) {
                  displayField = false;
                  break;
                }
              }
              else if (requiredElement.is('input:radio')) {
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
        'nonce': CCB_CORE_SETTINGS.nonce
      };

      $.post(ajaxurl, data, function(response) {
        if (true === response.success) {
          var $resultsWrapper = $('.ccb-core-latest-results');
          var $syncButton = $('.sync-wrapper .button');
          var className = response.data.success ? 'notice-info' : 'notice-error';
          var $content = $('<div class="notice ' + className + '"></div>');

          $content.append('<p>' + response.data.message + '</p>');
          $resultsWrapper.append($content);

          $resultsWrapper.find('.spinner').remove();
          $syncButton.removeClass('disabled');
        }
      });

    },

    pollForActiveSync : function() {

      var data = {
        'action': 'poll_sync',
        'nonce': CCB_CORE_SETTINGS.nonce
      };

      CCBCoreAdmin.disableUI();

      $.post(ajaxurl, data, function(response) {
        if (true === response.success) {
          if (false === response.data) {
            // A sync is not currently in progress.
            clearInterval(CCBCoreAdmin.syncPollId);
            CCBCoreAdmin.updateLatestSync();
          } else {
            CCBCoreAdmin.adminNotice( CCB_CORE_SETTINGS.translations.syncInProgress, 'warning' );
          }
        }
      });

    },

    syncData : function(event) {

      event.preventDefault();
      var $syncButton = $('.sync-wrapper .button');

      if ( $syncButton.hasClass('disabled') ) {
        return false;
      }

      CCBCoreAdmin.disableUI();

      var data = {
        'action': 'sync',
        'nonce': CCB_CORE_SETTINGS.nonce
      };

      $.post(ajaxurl, data, function(response) {
        CCBCoreAdmin.adminNotice( CCB_CORE_SETTINGS.translations.syncInProgress, 'warning' );
        CCBCoreAdmin.syncPollId = setInterval(CCBCoreAdmin.pollForActiveSync, 10000);
      });

    },

    testCredentials : function(event) {
      event.preventDefault();
      var $clickedButton = $(this);
      if ( $clickedButton.hasClass('disabled') ) {
        return false;
      }

      var $testCredentialsWrapper = $('.test-credentials-wrapper');

      $clickedButton.addClass('disabled');
      CCBCoreAdmin.removeNotice();

      var data = {
        'action': 'test_credentials',
        'nonce': CCB_CORE_SETTINGS.nonce
      };

      $.post(ajaxurl, data, function(response) {

        if (true === response.success) {
          CCBCoreAdmin.adminNotice( CCB_CORE_SETTINGS.translations.credentialsSuccessful );
        } else {
          CCBCoreAdmin.adminNotice( CCB_CORE_SETTINGS.translations.credentialsFailed + ': ' + response.data, 'error' );
        }

        $clickedButton.removeClass('disabled');

      });
    },

    disableUI : function() {
      var $resultsWrapper = $('.ccb-core-latest-results');
      var $syncButton = $('.sync-wrapper .button');
      CCBCoreAdmin.removeNotice();
      $syncButton.addClass('disabled');
      $resultsWrapper.empty().append(CCBCoreAdmin.spinner);
    },

    /**
     * Helper method to insert an admin notice on the page
     */
    adminNotice : function(message, type = 'info', className = '') {
      var $notice = $('<div class="notice ' + className + '"></div>');
      var $content = $('<p></p>');
      $notice.addClass('notice-' + type);
      $content.text(message);
      $notice.append($content);
      $('.ccb_core_settings-wrapper > h2 ').after( $notice );
    },

    /**
     * Helper method to remove all or some notices
     */
    removeNotice : function(className = '') {
      var noticeSelector;
      if (className.length) {
        noticeSelector = '.notice.' + className;
      } else {
        noticeSelector = '.notice';
      }

      $(noticeSelector).remove();
    }

  };

  $(function() {

    CCBCoreAdmin.initialize();

  });

})( jQuery );

