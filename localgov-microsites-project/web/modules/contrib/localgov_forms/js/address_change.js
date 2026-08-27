/**
 * @file
 *  Address change
 */
(function localgovFormsAddressChange($, Drupal) {
  /**
   * Reset Address Lookup Form.
   *
   * @param {jQuery} addressLookupElement
   *   Address lookup element.
   * @param {jQuery} resetButton
   *   Reset button element.
   * @param {String} mode
   *   'soft' = Do not clear the search string.
   *   'hard' = Clear the search string.
   */
  function resetAddressLookUpForm(addressLookupElement, resetButton, mode) {
    addressLookupElement
      .find('.js-address-select-container')
      .addClass('hidden');
    addressLookupElement.find('.js-address-select').val('0');
    if (mode === 'hard') {
      addressLookupElement.find('.js-address-searchstring').val('').focus();
    }
    resetButton.addClass('hidden');
  }

  /**
   * Add reset button
   * @param {jQuery} addressLookupElement
   *   Address lookup jquery element.
   */
  function addResetButton(addressLookupElement) {
    // Add the reset button.
    const resetButton = $('<button>', {
      text: 'Reset',
      type: 'button',
      class: 'button btn btn-orange js-reset-address hidden',
      href: '#',
    });

    // Reset the form.
    resetButton.click(function resetButtonClickHandler() {
      resetAddressLookUpForm(addressLookupElement, $(this), 'hard');

      // Show manual entry button if configured to always display.
      const isAlwaysVisible = addressLookupElement.data(
        'manual-entry-always-visible',
      );
      if (isAlwaysVisible !== undefined && isAlwaysVisible) {
        const manualButton = addressLookupElement
          .parent()
          .find('.js-manual-address');
        manualButton.show();
      }
    });

    // Append the reset button.
    addressLookupElement.find('.js-address-actions').append(resetButton);
  }

  /**
   * Populate Central Hub Selected Address
   *
   * Adds the selected address to drupalSettings.centralhub.selectedAddress.
   *
   * @param  {jQuery} selectList
   *   Address selectlist element.
   */
  function populateCentrahubSelectedAddress(selectList) {
    // As the select gets replaced by Drupal, this lets us know its ready.
    selectList.addClass('js-populated');
    selectList.change(function handleSelectListChange() {
      const addressSelectId = $(this).data('address-id');
      if (drupalSettings.centralHub[addressSelectId].addressList) {
        const { addressList } = drupalSettings.centralHub[addressSelectId];
        const addressSelectedName = this.value;
        const addressSelected = addressList.find(
          function addressSelected(element) {
            return element.name === addressSelectedName;
          },
        );
        drupalSettings.centralHub.selectedAddress = addressSelected;
      }
    });
  }

  // Attach after an ajax refresh
  Drupal.behaviors.localgov_forms = {
    attach(context, settings) {
      // Behaviors to only attach once.
      $(
        once(
          'central-hub-behaviours',
          '.js-centralhub-address-lookup',
          context,
        ),
      ).each(function centralHubEachHandler() {
        // Get form elements.
        const addressLookupElement = $(this);
        const searchElement = addressLookupElement.find(
          '.js-address-searchstring',
        );
        const searchButton = addressLookupElement.find(
          '.js-address-searchbutton',
        );
        const selectListContainer = addressLookupElement.find(
          '.js-address-select-container',
        );
        const selectList = selectListContainer.find('.js-address-select');
        const error = selectListContainer.find('.js-address-error, .error');

        // Change the search button to normal button.
        searchButton.attr('type', 'button');
        searchButton.click(function searchButtonClick(event) {
          event.preventDefault();
          searchButton.addClass('js-searching');
        });

        // Add the reset button.
        if (addressLookupElement.find('.js-reset-address').length === 0) {
          addResetButton(addressLookupElement);
        }
        const resetButton = addressLookupElement.find('.js-reset-address');

        searchElement.unbind('change');
        // Mark search element when changed.
        searchElement
          .change(function handleSearchElementChange() {
            $(this).addClass('js-changed');
          })
          .keyup(function handleSearchElementKeyup(event) {
            // Detect keypress is backspace or actual text key.
            // See : https://www.cambiaresearch.com/articles/15/javascript-char-codes-key-codes.
            const { keyCode } = event;
            if (
              keyCode === 8 ||
              keyCode === 32 ||
              (keyCode >= 48 && keyCode <= 90) ||
              (keyCode >= 96 && keyCode <= 111) ||
              (keyCode >= 186 && keyCode <= 222)
            ) {
              resetAddressLookUpForm(addressLookupElement, resetButton, 'soft');
            }
            if (keyCode === 13) {
              searchElement.blur();
              event.preventDefault();
              searchButton.trigger('click');
            }
            // Stop the main form submitting on enter.
          })
          .focus(function handleSearchElementFocus() {
            searchButton.attr('type', 'submit');
          })
          .blur(function handleSearchElementBlur() {
            searchButton.attr('type', 'button');
          });

        if (error.length > 0) {
          searchButton.attr('type', 'submit');
        }

        // Default hide the select box and reset button.
        // This is for when address lookup is on the first page.
        if (
          (selectList.find('option').length === 0 &&
            searchElement.val().length === 0) ||
          error.length === 0
        ) {
          resetButton.addClass('hidden');
          selectListContainer.addClass('hidden');
        }

        // Make sure that selected address is blank.
        // See DRUP-1294.
        // @todo seperate each selected address based on element ID.
        if (typeof drupalSettings.centralHub !== 'undefined') {
          drupalSettings.centralHub.selectedAddress = undefined;
        }
      });

      // Behaviours to add each time.
      // Requires the address select box to be populated.
      $('.js-centralhub-address-lookup', context).each(
        function centralHubAddressLookupEachHandler() {
          // Get form elements.
          const addressLookupElement = $(this);
          const searchElement = addressLookupElement.find(
            '.js-address-searchstring',
          );
          const searchButton = addressLookupElement.find(
            '.js-address-searchbutton',
          );
          const selectListContainer = addressLookupElement.find(
            '.js-address-select-container',
          );
          const selectList = selectListContainer.find('.js-address-select');
          const error = selectListContainer.find('.js-address-error, .error');
          const resetButton = addressLookupElement.find('.js-reset-address');
          const manualButton = addressLookupElement.find('.js-manual-address');
          const manualAddressContainer = addressLookupElement.find(
            '+ .js-address-entry-container',
          );
          const ajaxProgressElement =
            addressLookupElement.find('.ajax-progress');

          // This ajac was either not initiated by the address lookup,
          // or the search is still in progress.
          if (
            !searchButton.hasClass('js-searching') ||
            ajaxProgressElement.length > 0
          ) {
            return;
          }

          // Unhide the manual entry button only when manual address entry fields
          // are hidden.
          const isAlwaysVisible = addressLookupElement.data(
            'manual-entry-always-visible',
          );
          if (
            isAlwaysVisible !== undefined &&
            !isAlwaysVisible &&
            manualAddressContainer.is(':hidden')
          ) {
            manualButton.show();
          }

          // If there has been a search.
          if (
            (selectList.find('option').length > 0 &&
              searchElement.val().length > 0) ||
            error.length > 0
          ) {
            searchButton.removeClass('js-searching');

            // unhide resetbutton.
            resetButton.removeClass('hidden');

            // After search, show the address lookup
            selectListContainer.removeClass('hidden');

            // Add select behaviour if present
            if (selectList.find('option').length > 1) {
              populateCentrahubSelectedAddress(selectList);
              if (searchElement.hasClass('js-changed')) {
                selectList.focus();
                searchElement.removeClass('js-changed');
              } else if (drupalSettings.centralHub.newSearch) {
                // If new search latch set, refocus on search element.
                searchElement.focus();
              }
            } else {
              searchElement.focus();
            }

            // Set newSearch latch to false.
            drupalSettings.centralHub.newSearch = false;

            // Else make sure the select box is hidden.
          } else {
            resetButton.addClass('hidden');
            selectListContainer.addClass('hidden');
          }
        },
      );
    },
  };
})(jQuery, Drupal);
