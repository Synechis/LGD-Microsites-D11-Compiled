# LocalGovDrupal Forms

Provides additional configuration, styling and components for the Drupal Webform module.

## Additional components

* LocalGov Forms Date - A date input field based on the [GDS Date Input pattern](https://design-system.service.gov.uk/components/date-input/)
* LocalGov address lookup - Webform element with a configurable address lookup backend.  Geocoder plugins act as backends.
* Two-decimal number field — Constrains a webform `number` element to two decimal places. Enforces input via `step=0.01`/`inputmode=decimal` attributes and a JS behaviour that truncates on input and normalises (e.g. `1` → `1.00`) on blur and submit.

### Date validation messages

The **LGD Date** and **LGD Date of Birth** elements take their validation
wording from the element settings, under **Form validation**:

* **Required message** — shown when the field is left empty. Used verbatim.
* **Invalid date message** — shown when the date is not a real date, is missing
  the day, month or year, or has letters in one of the boxes.

Leave either blank for the default wording (`Date of birth is required.`,
`Date of birth must include a month and a year.`, `Date of birth must be a real
date.`). Messages are plain text — no field name is italicised, and each field
produces one message.

Note: the **Date minimum** / **Date maximum** messages come from the webform
module itself and cannot be overridden here.

## Plugins
- Personally Identifiable Information (PII) redactor from Webform submissions: At the moment, a plugin manager `plugin.manager.pii_redactor` and a sample plugin are provided.

## Installation

By default, on initial install, localgov_forms will update the default `webform.settings` config:
- Enables Ajax by default for forms (individual forms can override this).
- Enables submit-once (prevents double button presses / slow connection retries etc.)
- Disables the browser back button during submission
- Warns about unsaved changes
- Enables the submission log.
- Sets plain-English defaults for the confirmation message
- Removes chevrons from wizard/preview button labels.
- Excludes a range of webform elements that are less likely to be used so the UI is easier for editors to navigate. 

**If you have already installed Webform and wish to preserve your existing
settings**, add the following to `settings.php` before installing this
module (it can be removed afterwards):

```php
$settings['localgov_forms_skip_webform_config'] = TRUE;
```

However, there is currently a **known bug** where the address lookup element throws an error if the LocalGov Forms webform settings are not included at install time. See more at https://git.drupalcode.org/project/localgov_forms/-/work_items/3584172

## Dependencies
The geocoder-php/nominatim-provider package is necessary to run automated tests:
```
$ composer require --dev geocoder-php/nominatim-provider
```

The localgovdrupal/localgov_geo and localgovdrupal/localgov_os_places_geocoder_provider packages are needed to use the Ordnance Survey Places API-based address lookup plugin.  Once these packages are installed, the *Localgov OS Places* plugin will become available for selection from the Localgov address lookup element's configuration form.

## Managing changes to webforms

Webforms in Drupal are config entities, therefore are by default exported with the website configuration.
It is often desirable that webforms are built and maintained by non-developers.
To avoid the configuration being removed by deployments, install the [Config ignore](https://www.drupal.org/project/config_ignore) module and under `/admin/config/development/configuration/ignore` add the following:
```
webform.webform.*
webform.webform_options.*
```
