/**
 * @file
 * Address select behaviour for the LocalGov Forms International Address Lookup element.
 *
 * Mirrors localgov_forms/address_select for the
 * localgov_forms_international_address_lookup element type, which renders with
 * a different wrapper class and so falls outside the LGD selector.
 */
(function localgovFormsInternationalAddressLookup($, Drupal, once) {
  const WRAPPER =
    '.js-webform-type-localgov-forms-international-address-lookup';
  const COUNTRY =
    'select.js-localgov-forms-webform-international-address--country';

  /**
   * Sets the country select from a geocoded address.
   *
   * Webform's country_names options are keyed by country name, so the country
   * name is tried against option values first, then the ISO code, then the
   * visible option text. Geocoders returning a sub-national country such as
   * "England" match nothing; in that case the existing selection is left alone
   * rather than blanked.
   *
   * @param {jQuery} wrapper
   *   The composite element wrapper.
   * @param {object} addr
   *   The selected address from drupalSettings.centralHub.
   */
  function setCountry(wrapper, addr) {
    const select = wrapper.find(COUNTRY);
    if (!select.length) {
      return;
    }
    const options = select.find('option');
    const candidates = [addr.country, addr.country_code].filter(Boolean);
    for (let i = 0; i < candidates.length; i += 1) {
      const candidate = String(candidates[i]).trim().toLowerCase();
      const match = options.filter(function matchCountryOption() {
        return (
          this.value !== '' &&
          (this.value.trim().toLowerCase() === candidate ||
            this.textContent.trim().toLowerCase() === candidate)
        );
      });
      if (match.length) {
        select.val(match.first().prop('value')).trigger('change');
        return;
      }
    }
  }

  function showManualAddress(wrapper) {
    wrapper.find('.js-address-entry-container').removeClass('hidden');
    wrapper.find('.js-manual-address').hide();
    wrapper.find('.js-address-select-container .js-address-error').remove();
  }

  // Drop 'settings' parameter; visibility is read from the data attribute set
  // by AddressLookupElement, mirroring address_select.js:79.
  function hideManualAddress(wrapper, mode) {
    wrapper.find('.js-address-entry-container').addClass('hidden');
    if (mode === 'hard') {
      wrapper.find('.js-address-entry-container input').val('');
      wrapper
        .find('.js-address-entry-container input')
        .first()
        .trigger('change');
      wrapper.find(COUNTRY).val('').trigger('change');
    }
    const isAlwaysVisible = wrapper
      .find('.js-centralhub-address-lookup')
      .data('manual-entry-always-visible');
    if (!isAlwaysVisible) {
      wrapper.find('.js-manual-address').hide();
    }
  }

  // The country select is checked alongside the text inputs so a submission
  // reloaded with only a country value keeps the address entry container open.
  function isManualAddressEntered(wrapper) {
    const container = wrapper.find('.js-address-entry-container');
    if (container.is(':visible')) {
      let hasValue = false;
      container.find('input[type="text"], select').each(function checkValue() {
        if (this.value !== '') {
          hasValue = true;
        }
      });
      return hasValue;
    }
    return false;
  }

  function addManualEntryButton(wrapper) {
    const manualButton = $('<button>', {
      text: "Can't find the address?",
      type: 'button',
      class: 'link-button manual-address js-manual-address',
      href: '#',
    });
    manualButton.click(function manualButtonClick() {
      showManualAddress(wrapper);
    });
    wrapper.find('.js-centralhub-address-lookup').append(manualButton);
  }

  const selectChangeHandler = function localgovFormsIntlAddressSelectChange() {
    if (typeof drupalSettings.centralHub === 'undefined') {
      return;
    }
    const wrapper = $(this).closest(WRAPPER);
    if (!wrapper.length) {
      return;
    }
    const addressEntry = wrapper.find('.js-address-entry-container');

    if (drupalSettings.centralHub.selectedAddress) {
      const addr = drupalSettings.centralHub.selectedAddress;
      addressEntry
        .find('input.js-localgov-forms-webform-uk-address--address-1')
        .val(addr.line1);
      addressEntry
        .find('input.js-localgov-forms-webform-uk-address--address-2')
        .val(addr.line2);
      addressEntry
        .find('input.js-localgov-forms-webform-uk-address--town-city')
        .val(addr.town);
      addressEntry
        .find('input.js-localgov-forms-webform-uk-address--postcode')
        .val(addr.postcode);

      ['lat', 'lng', 'uprn', 'ward'].forEach(function fillExtraField(field) {
        wrapper
          .find(`input.js-localgov-forms-webform-uk-address--${field}`)
          .val(addr[field]);
      });

      setCountry(wrapper, addr);

      showManualAddress(wrapper);
    } else if (this.value === 0) {
      hideManualAddress(wrapper, 'hard');
    }
  };

  const manualAddressChangeHandler =
    function localgovFormsIntlManualAddressChange() {
      const wrapper = $(this).closest(WRAPPER);
      if (!wrapper.length) {
        return;
      }
      ['lat', 'lng', 'uprn', 'ward'].forEach(function clearExtraField(field) {
        wrapper
          .find(`input.js-localgov-forms-webform-uk-address--${field}`)
          .val('');
      });
    };

  Drupal.behaviors.localgovFormsInternationalAddressLookup = {
    attach(context, settings) {
      $(once('localgov-forms-international-address', WRAPPER, context)).each(
        function initWrapper() {
          const wrapper = $(this);
          addManualEntryButton(wrapper);

          if (!isManualAddressEntered(wrapper)) {
            hideManualAddress(wrapper, 'soft');
          } else {
            wrapper.find('.js-reset-address').show();
            wrapper.find('.js-manual-address').hide();
          }

          wrapper.find('.js-reset-address').click(function handleResetClick() {
            hideManualAddress(wrapper, 'hard');
          });
        },
      );

      $(`${WRAPPER} .js-address-entry-container input`, context).on(
        'change',
        manualAddressChangeHandler,
      );
      $(`${WRAPPER} .js-address-select`, context).on(
        'change',
        selectChangeHandler,
      );
    },
    detach() {
      $(`${WRAPPER} .js-address-entry-container input`).off(
        'change',
        manualAddressChangeHandler,
      );
      $(`${WRAPPER} .js-address-select`).off('change', selectChangeHandler);
    },
  };
})(jQuery, Drupal, once);
