interface Window {
  SetDefaultValues_captchafox: (field: any) => any;
}

window.SetDefaultValues_captchafox = function (field: any) {
  field.label = 'CaptchaFox';
  field.labelPlacement = 'hidden_label';
  field.inputs = null;
  field.displayOnly = true;

  return field;
};

jQuery(document).on(
  'gform_field_added',
  function (_event: any, _form: any, field: { type: string }) {
    if (field.type !== 'captchafox') {
      return;
    }

    window.captchaFoxOnLoad();
  },
);

jQuery(document).on(
  'gform_load_field_settings',
  function (_event: any, field: { type: string; captchafox_start?: string }) {
    if (field.type !== 'captchafox') {
      return;
    }

    jQuery('#captchafox_start').val(field.captchafox_start || 'inherit');
  },
);
