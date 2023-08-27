const setupWoocommerceEvents = function ($: any) {
  function reset() {
    window.captchaFoxWPReset('form.woocommerce-checkout');
  }

  $(document.body).on('checkout_error', reset);

  $(document.body).on('updated_checkout', function () {
    window.captchaFoxOnLoad?.();
    reset();
  });
};

// @ts-expect-error jquery is globally available
jQuery(document).ready(setupWoocommerceEvents);
