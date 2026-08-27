(function headerScript(Drupal) {
  Drupal.behaviors.micrositeHeader = {
    attach: function (context, settings) {
      context = context || document;

      const offCanvas = context.querySelector("#off-canvas");
      const offCanvasToggle = context.querySelector(
        ".microsite-header__off-canvas-toggle"
      );
      const offCanvasClose = context.querySelector(".off-canvas__close");

      if (offCanvas) {
        const focusable = offCanvas.querySelectorAll(
          'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );

        function handleOpenOffCanvas() {
          offCanvas.setAttribute("data-expanded", "true");
          focusable[0].focus();
        }

        function handleCloseOffCanvas() {
          offCanvasToggle.focus();
          offCanvas.setAttribute("data-expanded", "false");
        }

        function trapFocusInOffCanvasArea(element) {
          const firstFocusableElement = focusable[0];
          const lastFocusableElement = focusable[focusable.length - 1];

          element.addEventListener("keydown", function (e) {
            const isTabPressed = e.code === "Tab";
            if (!isTabPressed) {
              return;
            }
            /* shift + tab */
            if (e.shiftKey) {
              if (context.activeElement === firstFocusableElement) {
                lastFocusableElement.focus();
                e.preventDefault();
              }
            } else {
              /* tab */
              if (context.activeElement === lastFocusableElement) {
                firstFocusableElement.focus();
                e.preventDefault();
              }
            }
          });
        }

        if (!offCanvasToggle) {
          return;
        } else {
          offCanvasToggle.addEventListener("click", handleOpenOffCanvas);
          trapFocusInOffCanvasArea(offCanvas);
          window.addEventListener("keyup", function (e) {
            if (e.code === "Escape") {
              handleCloseOffCanvas();
            }
          });
          document.addEventListener("click", function (e) {
            if (
              offCanvasToggle &&
              !offCanvasToggle.contains(e.target) &&
              !offCanvas.contains(e.target) &&
              offCanvas.getAttribute("data-expanded") === "true"
            ) {
              handleCloseOffCanvas();
            }
          });
        }
        offCanvasClose.addEventListener("click", () => {
          handleCloseOffCanvas();
        });
      }
    },
  };
})(Drupal);
