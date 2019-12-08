(function( $ ) {

  'use strict';

  var CCBCoreNotices = {

    initialize : function() {
      $('body').on('click', '.ccb-dismissible-notice .notice-dismiss', window.event, this.dismissNotice);
    },

    dismissNotice : function(event) {

      event.preventDefault();
      var data = {
        'action': 'dismiss_notice',
        'noticeId': $(this).parents('.ccb-dismissible-notice').data('noticeId'),
        'nonce': CCB_CORE_NOTICES.nonce
      };

      $.post(ajaxurl, data);

    },

  };

  $(function() {
    CCBCoreNotices.initialize();
  });

})( jQuery );
