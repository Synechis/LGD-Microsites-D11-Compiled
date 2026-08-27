/**
 * @file
 *  Address select
 */
(function localgovFormsAddressSelect($, Drupal) {
  /**
   * Show the manual address form elements.
   *
   * @param  {jQuery} centralHubElement
   *   Centralhub address element.
   */
  function showManualAddress(centralHubElement) {
    const manualAddressContainer = centralHubElement.find(
      '.js-address-entry-container',
    );
    const manualButton = centralHubElement.find('.js-manual-address');
    const addressSelectContainer = centralHubElement.find(
      '.js-address-select-container',
    );
    const addressError = addressSelectContainer.find('.js-address-error');
    manualAddressContainer.removeClass('hidden');
    manualButton.hide();
    // Remove the error element when making a manual address.
    addressError.remove();
  }

  /**
   * Adds manual entry button.
   *
   * @param {jQuery} centralHubElement
   *   Centralhub address element.
   */
  function addManualEntryButton(centralHubElement) {
    // Add the manual lookup button.
    const manualButton = $('<button>', {
      text: "Can't find the address?",
      type: 'button',
      class: 'link-button manual-address js-manual-address',
      href: '#',
    });

    // Reset the form.
    manualButton.click(function manualButtonClick() {
      showManualAddress(centralHubElement);
    });

    // Append the manual lookup button.
    centralHubElement
      .find('.js-centralhub-address-lookup')
      .append(manualButton);
  }

  /**
   * Hide manual address form.
   *
   * @param {jQuery} centralHubElement
   *   Central hub address lookup element.
   * @param {String} type
   *   'soft' = Do not clear the address values.
   *            (used when an address is selected)
   *   'hard' = Clear the address values.
   */
  function hideManualAddress(centralHubElement, type) {
    const manualAddressContainer = centralHubElement.find(
      '.js-address-entry-container',
    );
    const manualButton = centralHubElement.find('.js-manual-address');
    manualAddressContainer.addClass('hidden');

    if (type === 'hard') {
      // Clear all values.
      manualAddressContainer.find('input').val('');

      // Trigger change events so UPRN and extra fields are cleared.
      manualAddressContainer.find('input').first().trigger('change');
    }

    // Hide manual entry button if asked for.
    const isAlwaysVisible = centralHubElement
      .find('.js-centralhub-address-lookup')
      .data('manual-entry-always-visible');
    if (isAlwaysVisible !== undefined && !isAlwaysVisible) {
      manualButton.hide();
    }
  }

  /**
   * Check if a manual address has been entered.
   *
   * @param {jQuery} centralHubElement
   *   Centralhub address element.
   * @return {Boolean}
   *   True if the a manual address is present and the search box is empty.
   */
  function isManualAddressEntered(centralHubElement) {
    const manualAddressContainer = centralHubElement.find(
      '.js-address-entry-container',
    );
    // Test manual address if the element is visible.
    if (manualAddressContainer.is(':visible')) {
      let hasManualValue = false;
      manualAddressContainer
        .find('input[type="text"]')
        .each(function checkManualValue() {
          if (this.value !== '') {
            hasManualValue = true;
          }
        });
      return hasManualValue;
    }
    return false;
  }

  /**
   * Hide errors on an element
   *
   * @param {jQuery} indvElement
   *   The individual form input element to hide errors.
   */
  function hideErrorsOnElement(indvElement) {
    const indvElementWrapper = indvElement.closest('.js-form-item');
    indvElement.not('.js-address-searchstring').removeClass('error');
    indvElementWrapper.removeClass('has-error');
    indvElementWrapper.find('.invalid-feedback').remove();
  }

  /**
   * Hide address search errors.
   *
   * On webform, when a form fails validation, errors can cascade to the child
   * elements, including the search box.
   * This will remove the errors in javascript, leaving the error message on
   * the parent element only.
   * @see https://www.drupal.org/project/drupal/issues/2848319
   * @param  {jQuery} centralHubElement
   *   Centralhub address element.
   */
  function hideAddressSearchErrors(centralHubElement) {
    const searchElementContainer = centralHubElement.find(
      '.js-address-search-container',
    );
    const searchElement = searchElementContainer.find(
      '.js-address-searchstring',
    );
    const manualAddressContainer = centralHubElement.find(
      '.js-address-entry-container',
    );
    hideErrorsOnElement(searchElement);
    if (manualAddressContainer.is(':hidden')) {
      manualAddressContainer.find('input').each(function hideErrorsEachInput() {
        hideErrorsOnElement($(this));
      });
    }
  }

  /**
   * Central hub select change handler.
   * Populates the address fields when selecting an address.
   * @function
   */
  const localgovFormsWebformChangeHandler =
    function localgovFormsWebformChangeHandler() {
      // Guard check, don't run if centralhub not yet defined.
      if (typeof drupalSettings.centralHub === 'undefined') {
        return;
      }
      const centralHubElement = $(this).closest(
        '.js-webform-type-localgov-webform-uk-address',
      );
      const centralHubWebformAddressContainer = $(this).closest(
        '.js-webform-type-localgov-webform-uk-address',
      );
      const centralHubWebformAddressEntry =
        centralHubWebformAddressContainer.find('.js-address-entry-container');

      if (drupalSettings.centralHub.selectedAddress) {
        const addressSelected = drupalSettings.centralHub.selectedAddress;
        centralHubWebformAddressEntry
          .find('input.js-localgov-forms-webform-uk-address--address-1')
          .val(addressSelected.line1);
        centralHubWebformAddressEntry
          .find('input.js-localgov-forms-webform-uk-address--address-2')
          .val(addressSelected.line2);
        centralHubWebformAddressEntry
          .find('input.js-localgov-forms-webform-uk-address--town-city')
          .val(addressSelected.town);
        centralHubWebformAddressEntry
          .find('input.js-localgov-forms-webform-uk-address--postcode')
          .val(addressSelected.postcode);

        // Add any extra fields from centrahub for Twig access.
        // @See DRUP-1287.
        const extraElements = ['lat', 'lng', 'uprn', 'ward'];
        $.each(extraElements, function extraElementsIterator(index, value) {
          centralHubWebformAddressContainer
            .find(`input.js-localgov-forms-webform-uk-address--${value}`)
            .val(addressSelected[value]);
        });

        showManualAddress(centralHubElement);
      } else if (this.value === 0) {
        // If choosing the empty option, clear out the address fields.
        hideManualAddress(centralHubElement, 'hard');
      }
    };

  /**
   * Central hub manual address change handler.
   * Clears any central hub values such as UPRN from the address handler.
   * @function
   */
  const localgovFormsWebformManualAddressChangeHandler =
    function localgovFormsWebformManualAddressChangeHandler() {
      const centralHubWebformAddressContainer = $(this).closest(
        '.js-webform-type-localgov-webform-uk-address',
      );

      // Clear any extra fields from centrahub for Twig access.
      // @See DRUP-1287.
      const extraElements = ['lat', 'lng', 'uprn', 'ward'];
      $.each(
        extraElements,
        function localgovFormsWebformManualAddressChangeHandlerEach(
          index,
          value,
        ) {
          centralHubWebformAddressContainer
            .find(`input.js-localgov-forms-webform-uk-address--${value}`)
            .val('');
        },
      );
    };

  // Attach after an ajax refresh
  Drupal.behaviors.localgov_forms_webform = {
    attach(context, settings) {
      $(
        once(
          'localgov-address-webform',
          '.js-webform-type-localgov-webform-uk-address',
          context,
        ),
      ).each(function localgovFormsAddressEach() {
        const centralHubElement = $(this);
        addManualEntryButton(centralHubElement);

        // Hide the manual address element, if it has no values.
        if (!isManualAddressEntered(centralHubElement)) {
          hideManualAddress(centralHubElement, 'soft');
        }

        centralHubElement
          .find('.js-reset-address')
          .click(function handleResetAddressClick() {
            hideManualAddress(centralHubElement, 'hard');
          });
        hideAddressSearchErrors(centralHubElement);
      });

      // Manual address change handler first.
      $('.js-address-entry-container input').on(
        'change',
        localgovFormsWebformManualAddressChangeHandler,
      );

      // Select box change handler.
      $('.js-address-select').on('change', localgovFormsWebformChangeHandler);
    },
    detach() {
      $('.js-address-entry-container input').off(
        'change',
        localgovFormsWebformManualAddressChangeHandler,
      );
      $('.js-address-select').off('change', localgovFormsWebformChangeHandler);
    },
  };
})(jQuery, Drupal);
