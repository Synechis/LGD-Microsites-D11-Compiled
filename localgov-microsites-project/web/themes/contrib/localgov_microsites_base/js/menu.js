(function menuScript(Drupal) {
  Drupal.behaviors.menu = {
    attach: function (context, settings) {
      context = context || document;
      const subMenuToggles = Array.from(context.querySelectorAll('.menu__sub-menu-toggle'));

      function handleHideNestedSubMenus(toggle) {
        toggle.setAttribute('aria-expanded', 'false');
        toggle.nextElementSibling.style.display = 'none';
      }

      function handleSubMenuToggle(subMenuToggle) {
        subMenuToggle.removeAttribute('hidden');
        const subMenu = subMenuToggle.nextElementSibling;

        subMenuToggle.addEventListener('click', function() {
          const topLevelMenuItem = subMenuToggle.closest('.menu-item--level-0');
          const topLevelIndex = topLevelMenuItem.dataset.index;
          const parentMenu = topLevelMenuItem.closest('.menu');
          const siblings = Array.from(parentMenu.querySelectorAll('li:not(li li)'));
          siblings.forEach(sibling => {
            if (sibling.dataset.index != topLevelIndex) {
              const toggles = sibling.querySelectorAll('.menu__sub-menu-toggle');
              toggles.forEach(toggle => {
                toggle.setAttribute('aria-expanded', 'false');
                toggle.nextElementSibling.style.display = 'none';
              });
            }
          });

          const expanded = subMenuToggle.getAttribute('aria-expanded');

          if (expanded === 'false') {
            subMenu.style.display = 'block';
            subMenuToggle.setAttribute('aria-expanded', 'true');
          } else {
            subMenuToggle.setAttribute('aria-expanded', 'false');
            subMenuToggle.nextElementSibling.style.display = 'none';
          }

          const nestedMenuToggles = subMenu.querySelectorAll('.menu__sub-menu-toggle');
          nestedMenuToggles.forEach(handleHideNestedSubMenus);

          // Listen for escape key.
          // If escape key is pressed, close the menu.
          window.addEventListener("keyup", function (e) {
            if (e.code === "Escape") {
              subMenuToggle.setAttribute('aria-expanded', 'false');
              subMenuToggle.nextElementSibling.style.display = 'none';
              subMenuToggle.focus();
              nestedMenuToggles.forEach(handleHideNestedSubMenus);
            }
          });

          // Listen for click event.
          // If click is outside of the menu, close the menu.
          window.addEventListener("click", function (e) {
            if (!subMenu.contains(e.target) && !subMenuToggle.contains(e.target)) {
              subMenuToggle.setAttribute('aria-expanded', 'false');
              subMenuToggle.nextElementSibling.style.display = 'none';
              nestedMenuToggles.forEach(handleHideNestedSubMenus);
            }
          });

          // Listen for tab event.
          // If focus is outside of the menu, close the menu.
          window.addEventListener("keydown", function (e) {
            if (e.code === "Tab") {
              const focused = document.activeElement;
              if (!subMenu.contains(focused) && !subMenuToggle.contains(focused)) {
                subMenuToggle.setAttribute('aria-expanded', 'false');
                subMenuToggle.nextElementSibling.style.display = 'none';
                nestedMenuToggles.forEach(handleHideNestedSubMenus);
              }
            }
          });

        })
      }

      subMenuToggles.forEach(handleSubMenuToggle);
    },
  };
})(Drupal);
