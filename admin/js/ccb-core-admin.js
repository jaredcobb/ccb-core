(function( $ ) {

  'use strict';

  var ccbCoreAdmin = {

    syncPollId : '',

    initialize : function() {

      this.syncPollId = setInterval(this.pollForActiveSync, 10000);

      $('.test-login-wrapper .button').on('click', event, this.testCredentials);

      $('.sync-wrapper .button').on('click', event, this.syncData);

    },

    updateLatestSync : function() {

      var data = {
        'action': 'get_latest_sync',
        'nextNonce': CCB_CORE_OPTIONS.nextNonce
      };

      $.post(ajaxurl, data, function(response) {

        var $latestSyncMessageWrapper = $('.ccb-core-latest-results');
        var labelStyle = 'redux-' + response.style;
        var labelContent = '<p class="redux-info-desc"><b>Latest Sync Results</b><br>' + response.description + '</p>';

        $latestSyncMessageWrapper.removeClass('redux-critical redux-warning redux-success').addClass(labelStyle);
        $latestSyncMessageWrapper.empty().append(labelContent);

      });

    },

    pollForActiveSync : function() {

      var data = {
        'action': 'poll_sync',
        'nextNonce': CCB_CORE_OPTIONS.nextNonce
      };

      $.post(ajaxurl, data, function(response) {
        if ( response.syncInProgress == false ) {
          clearInterval(ccbCoreAdmin.syncPollId);
          ccbCoreAdmin.updateLatestSync();

          var $spinner = $('.sync-wrapper .spinner');
          var $syncButtons = $('.sync-wrapper .button');
          var $syncMessages = $('.sync-wrapper div.in-progress-message');

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
        'nextNonce': CCB_CORE_OPTIONS.nextNonce
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
        'nextNonce': CCB_CORE_OPTIONS.nextNonce
      };

      $.post(ajaxurl, data, function(response) {
        $syncWrapper.append('<div class="in-progress-message redux-notice-field redux-field-info redux-info">Syncronization in progress... You can safely navigate away from this page while we work hard in the background. (It should be just a moment).</div>');
        ccbCoreAdmin.syncPollId = setInterval(ccbCoreAdmin.pollForActiveSync, 10000);
      });

    }

  };

  $(function() {

    ccbCoreAdmin.initialize();

  });

})( jQuery );

