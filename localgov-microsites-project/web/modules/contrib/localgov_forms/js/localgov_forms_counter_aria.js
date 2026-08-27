(function (Drupal, once) {
  Drupal.behaviors.localgovFormsCounterAriaLink = {
    attach(context) {
      function linkCounters(root) {
        once(
          'localgov-forms-counter-aria',
          '.js-webform-counter[id]',
          root,
        ).forEach(function (ta) {
          const counter = ta
            .closest('.js-form-item')
            ?.querySelector('.text-count-message');
          if (!counter) {
            // Webform's own behaviour may not have created the counter
            // message element yet. Un-mark so the next mutation retries.
            once.remove('localgov-forms-counter-aria', ta);
            return;
          }

          const counterId = `${ta.id.split('--')[0]}-counter`;
          if (!counter.id) counter.id = counterId;

          const aria = ta.getAttribute('aria-describedby') || '';
          ta.setAttribute('aria-describedby', `${aria} ${counterId}`.trim());
        });
      }

      linkCounters(context);

      // One MutationObserver per page handles elements added outside Drupal's
      // behaviour attach (e.g. non-Drupal AJAX). Scans only the mutated
      // subtree, not the whole document, to avoid unnecessary work.
      if (!Drupal.behaviors.localgovFormsCounterAriaLink._observer) {
        Drupal.behaviors.localgovFormsCounterAriaLink._observer =
          new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
              linkCounters(m.target);
            });
          });
        Drupal.behaviors.localgovFormsCounterAriaLink._observer.observe(
          document.body,
          { childList: true, subtree: true },
        );
      }
    },
  };
})(Drupal, once);
