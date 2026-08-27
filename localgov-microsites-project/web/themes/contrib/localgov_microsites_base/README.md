# LocalGov Microsites Base

A  sub-theme of LocalGov Base. Please read the README there also.

## Supported releases

We now have a 3.x and a 2.x branch.

- All new feature development work will be against the 3.x branch
- The 2.x branch will continue to have bug fixes until the end of December 2025.

Everyone is encouraged to upgrade to the 3.x branch and localgov_base 2.x, but please see release notes regarding changes to the grid system layouts, moving from flex to css grids.

https://github.com/localgovdrupal/localgov_base/releases/tag/2.0.0

## Notes on upgrading from 2.x to 3.x

The 3.x branch of localgov_microsites_base requires localgov_base 2.x

In the 2.x branch of localgov_base, we have re-written our grid system to use CSS Grid instead of Flexbox.

This makes the CSS for the grid simpler. It should also fix lots of minor spacing issues (negative left/right margin on grid containers).

Important: We recommend testing your layouts before deploying.

In some cases, if you have made customisations to the layout in a child theme, removing these changes might be advisable.

In some cases, the changes needed in child themes are to change the width specification on grid child items from
"width: calc(...) " to "grid-column: span 3" for example.

## Customising the appearance through the UI

A _Microsite Admin_ has the permission to set a number of css variables through the UI. These override the default variables set in /css/variables.css

## Creating a sub-theme of LocalGov Microsites Base

We recommend creating a sub-theme of LocalGov Microsites Base in order to create a custom theme for one or more of your microsites. A _Microsite controller_ is able to install and set a new theme for a microsite.


## Quick overview

1. Create a new sub-theme of LocalGov Microsite Base (see below)
4. Copy the variables you need from localgov_microsites_base/css/variables.css and set the default branding colours, fonts etc
5. Enable this theme, using the "Enable" button only, <strong>not</strong> the "Enable and set as default" button. After this, you can then hit the "Set as default" button. This 2-step process is needed to ensure all the blocks from the default theme get transition to the same block regions in your new theme.



## Sub-theme
To create a sub-theme, you simply need to run the sub-theme creation script that is in the `/scripts` directory, like so:

```bash
  cd web/themes/contrib/localgov_microsites_base/
  bash scripts/create_subtheme.sh
```

You need to enter two items when creating a sub-theme:
1. The name of the theme, this can be anything and can include spaces, e.g. Super Council
2. The machine name for the theme, this must start with a letter and use only lowercase letters and underscores, e.g. super_council

## Custom Styles
Hopefully most of the custom styles you will need are set via CSS custom properties in the `/css/variables.css` file in your sub-theme.

This is where you set your colours, fonts, spacing, etc. Then you "apply" these variables where needed, like so:

```css
  :root {
    /* Set/Override Variables */
    --color-accent: red;
    --spacing-largest: 5rem;

    /* Apply Variables */
    --color-link: var(--color-accent);
    --breadcrumbs-background-color: var(--color-accent);
    --section-spacing-vertical-header: var(--spacing-largest);
  }
```

If you need to add any CSS overides, you can create custom CSS files for these and then a library/libraries to attach them to your components.


## Maintainers

This project is currently maintained by:

 - Mark Conroy https://github.com/markconroy
 - Maria Young https://github.com/msayoung
