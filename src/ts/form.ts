import type { WidgetDisplayMode } from '@captchafox/types';

declare global {
  interface Window {
    captchaFoxOnLoad: () => void;
    captchaFoxWPReset: (selector: string) => void;
  }
}

(() => {
  let executeListener: (event: Event) => void;

  function resetFormWidget(formSelector: string) {
    const element = document.querySelector<HTMLFormElement>(formSelector);
    if (!element) return;

    const widgetId = element.dataset.cfWidgetId;
    if (!widgetId) return;

    window.captchafox?.reset(widgetId);
  }

  function initializeForms() {
    const forms = document.querySelectorAll('form');

    forms.forEach(async (form) => {
      const [submitButton] = form.querySelectorAll<HTMLElement>(
        '[type="submit"], .forminator-button-submit'
      );
      const captchaSlot: HTMLDivElement | null =
        form.querySelector('.captchafox');
      const isAlreadyRendered = captchaSlot?.hasChildNodes();

      if (!captchaSlot || !window.captchafox || isAlreadyRendered) return;

      const mode = captchaSlot.dataset.mode as WidgetDisplayMode;
      const sitekey = captchaSlot.dataset.sitekey;
      const theme = captchaSlot.dataset.theme;
      const lang = captchaSlot.dataset.lang;

      const widgetId = await window.captchafox.render(captchaSlot, {
        sitekey: sitekey ?? '',
        ...(mode && { mode }),
        ...(lang && { lang }),
        ...(theme && { theme }),
        onError: (error) => console.error(error),
      });

      form.dataset.cfWidgetId = widgetId;

      if ('hidden' !== captchaSlot.dataset.mode) {
        return;
      }

      if (submitButton) {
        executeListener = (event: Event) =>
          executeCaptcha(event, form, widgetId, submitButton);
        submitButton.addEventListener('click', executeListener, true);
      }
    });
  }

  async function executeCaptcha(
    event: Event,
    form: HTMLFormElement,
    widgetId: string,
    submitButton: HTMLElement
  ) {
    event.preventDefault();
    event.stopPropagation();

    if (!form || !window.captchafox) return;

    await window.captchafox.execute(widgetId);

    // handle ninja forms
    if (submitButton.classList.contains('ninja-forms-field')) {
      submitButton.removeEventListener('click', executeListener, true);
      submitButton.click();
      return;
    }

    if (form.requestSubmit) {
      form.requestSubmit(submitButton);
      return;
    }

    form.submit();
  }

  window.captchaFoxWPReset = resetFormWidget;

  window.captchaFoxOnLoad = initializeForms;
})();
