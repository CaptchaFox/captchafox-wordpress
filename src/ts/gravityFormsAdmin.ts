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
  }
);
