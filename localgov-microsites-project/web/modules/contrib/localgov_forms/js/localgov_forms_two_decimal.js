(function (Drupal) {
  Drupal.behaviors.localgovFormsTwoDecimalNumber = {
    attach(context) {
      const fields = once(
        'localgov-forms-two-decimal',
        'input[data-two-decimal="true"]',
        context,
      );

      fields.forEach((field) => {
        field.addEventListener('input', function () {
          if (!/^\d*\.?\d{0,2}$/.test(this.value)) {
            this.value = this.value.slice(0, -1);
          }
        });

        field.addEventListener('blur', function () {
          if (this.value && !Number.isNaN(Number(this.value))) {
            this.value = Number(this.value).toFixed(2);
          }
        });

        // One listener per field (not per form) so every two-decimal field on
        // the same form is normalised on submit, not just the first one found.
        field.closest('form')?.addEventListener('submit', function () {
          if (field.value && !Number.isNaN(Number(field.value))) {
            field.value = Number(field.value).toFixed(2);
          }
        });
      });
    },
  };
})(Drupal);
