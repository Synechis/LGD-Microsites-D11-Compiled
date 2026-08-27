/**
 * @file
 *  Webform form errrors.
 */
(function formErrors($, Drupal, once) {
  /**
   * Click handler for error links.
   *
   * @param  {event} event
   *   Jquery click event.
   */
  const linkClick = function linkClick(event) {
    // Get the target from jQuery event data.
    const { target } = event.data;

    // Set focus on click to the first input.
    const closetInput = target.find('input, textarea').first();
    closetInput.focus();
  };

  // Attach after an ajax refresh
  Drupal.behaviors.localgov_forms_errors = {
    attach(context) {
      once(
        'localgov-forms-errors',
        '.localgov-forms-alert-content ul > li > a',
        context,
      ).forEach(function bindErrorLink(el) {
        const $el = $(el);
        // Get fragment link
        const fragment = $el.attr('href');

        // If is a fragment (to avoid regular links in the banner).
        if (fragment.indexOf('#') === 0) {
          // If there is a wrapper target, apply the click handler.
          const target = $(`${fragment}--wrapper`);
          if (target.length > 0) {
            $el.on('click', { target }, linkClick);
          }
        }
      });
    },
  };
})(jQuery, Drupal, once);
