const setupWoocommerceEvents = ($: any) => {
  function reset() {
    window.captchaFoxWPReset('form.woocommerce-checkout');
  }

  $(document.body).on('checkout_error', reset);

  $(document.body).on('updated_checkout', function () {
    window.captchaFoxOnLoad?.();
    reset();
  });
};

jQuery(document).ready(setupWoocommerceEvents);
