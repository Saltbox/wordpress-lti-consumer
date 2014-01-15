function lti_consumer_launch(id) {
  jQuery('form#launch-' + id).submit();
}

jQuery(document).ready(function () {
  jQuery('form[data-auto-launch="yes"]').each(function () {
    jQuery(this).submit();
  });
});
