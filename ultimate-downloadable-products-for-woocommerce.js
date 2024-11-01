function ULTIMATE_DOWNLOADABLE_PRODUCTS_init() {
  if (
    "undefined" !== typeof window.ultimate_downloadable &&
    window.ultimate_downloadable.initialized === true
  ) {
    return;
  }
  window.ultimate_downloadable.initialized = true;
}

jQuery(document).ready(ULTIMATE_DOWNLOADABLE_PRODUCTS_init);
// proper init if loaded by ajax
jQuery(document).ajaxComplete(function (event, xhr, settings) {
  ULTIMATE_DOWNLOADABLE_PRODUCTS_init();
});
