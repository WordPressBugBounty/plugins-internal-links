var __webpack_exports__ = {};
/*!*************************************************!*\
  !*** ./src/admin/js/ilj_rating_notification.js ***!
  \*************************************************/
jQuery(document).ready(function () {
  jQuery('a.ilj-rating-notification-add').each(function () {
    jQuery(this).on('click', function (e) {
      e.preventDefault();
      var days = jQuery(this).data('add');
      var data = {
        'action': 'ilj_rating_notification_add',
        'days': days,
        'nonce': ilj_ajax_object.nonce
      };
      jQuery(this).closest('.notice').slideUp();
      jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: data,
        success: function (data, textStatus, xhr) {
          return;
        }
      });
    });
  });
});
