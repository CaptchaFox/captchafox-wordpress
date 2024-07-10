interface Window {
  elementorFrontend: {
    hooks: {
      addAction: (name: string, func: (scope: any) => void) => void;
    };
  };
}

jQuery(document).on(
  'ajaxSuccess',
  (_event: any, _xhr: any, settings: { data: Record<string, string> }) => {
    const params = new URLSearchParams(settings.data);
    if (params.get('action') !== 'elementor_pro_forms_send_form') {
      return;
    }

    const formId = params.get('form_id');
    window.captchaFoxWPReset(`div[data-id="${formId}"] form`);
  }
);

jQuery(document).ready(() => {
  window.elementorFrontend?.hooks?.addAction(
    'frontend/element_ready/widget',
    function (scope) {
      if (scope[0].classList.contains('elementor-widget-form')) {
        window.captchaFoxOnLoad();
      }
    }
  );
});
