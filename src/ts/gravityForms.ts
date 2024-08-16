const setupRenderEvent = ($: any) => {
  $(document).on('gform_post_render', function (_event: any, formId: string) {
    const currentForm = $('#gform_' + formId);
    if (!currentForm?.attr('target')) {
      return;
    }

    window.captchaFoxOnLoad();
  });
};

jQuery(document).ready(setupRenderEvent);
