// @ts-expect-error Marionette is globally available
const CaptchaFoxFieldController = Marionette.Object.extend({
  initialize() {
    this.listenTo(
      // @ts-expect-error nfRadio is globally available
      nfRadio.channel('submit'),
      'validate:field',
      this.updateField
    );

    this.listenTo(
      // @ts-expect-error nfRadio is globally available
      nfRadio.channel('fields'),
      'change:modelValue',
      this.updateField
    );

    this.listenTo(
      // @ts-expect-error nfRadio is globally available
      nfRadio.channel('nfMP'),
      'change:part',
      this.changePart,
      this
    );
  },
  changePart() {
    window.captchaFoxOnLoad();
  },
  updateField(model: any) {
    if ('captchafox' !== model.get('type')) {
      return;
    }

    const id = model.get('id');
    const value = model.get('value');

    if (value) {
      // @ts-expect-error nfRadio is globally available
      nfRadio.channel('fields').request('remove:error', id, 'required-error');
      return;
    }

    const widgetInForm = document.querySelector(`#nf-cf-${id}`);
    if (!widgetInForm) {
      return;
    }

    const parentForm = widgetInForm.closest('form');
    if (!parentForm) {
      return;
    }

    const widgetId = parentForm.dataset.cfWidgetId;
    const response = window.captchafox?.getResponse(widgetId);
    model.set('value', response);
  },
});

// @ts-expect-error jquery is globally available
jQuery(document).ready(() => {
  new CaptchaFoxFieldController();
});

// @ts-expect-error jquery is globally available
jQuery(document).on('nfFormReady', () => {
  window.captchaFoxOnLoad();
});
