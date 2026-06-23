import type { Theme, WidgetDisplayMode } from '@captchafox/types';

let executeListener: (event: Event) => void;

function resetFormWidget(formSelector: string) {
  const element = document.querySelector<HTMLFormElement>(formSelector);
  if (!element) return;

  const widgetId = element.dataset.cfWidgetId;
  if (!widgetId) return;

  window.captchafox?.reset(widgetId);
}

function initializeForms() {
  const forms = document.querySelectorAll<HTMLFormElement | HTMLElement>(
    'form, .gform_editor',
  );

  forms.forEach(async (form) => {
    const [submitButton] = form.querySelectorAll<HTMLElement>(
      '[type="submit"], .forminator-button-submit',
    );
    const captchaSlot: HTMLDivElement | null =
      form.querySelector('.captchafox');
    const isAlreadyRendered = captchaSlot?.hasChildNodes();

    if (!captchaSlot || !window.captchafox || isAlreadyRendered) return;

    const mode = captchaSlot.dataset.mode as WidgetDisplayMode;
    const sitekey = captchaSlot.dataset.sitekey;
    const theme = captchaSlot.dataset.theme as Theme;
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
        executeCaptcha(event, form as HTMLFormElement, widgetId, submitButton);
      submitButton.addEventListener('click', executeListener, true);
    }
  });
}

async function executeCaptcha(
  event: Event,
  form: HTMLFormElement,
  widgetId: string,
  submitButton: HTMLElement,
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

/**
 * Inject the CaptchaFox api script. Once it loads it calls
 * window.captchaFoxOnLoad (via its onload parameter) and renders the widgets.
 */
function injectApiScript() {
  if (document.getElementById('captchafox-api')) return;

  const api = window.captchaFoxConfig?.api;
  if (!api) return;

  const script = document.createElement('script');
  script.id = 'captchafox-api';
  script.src = api;
  script.async = true;
  document.head.appendChild(script);
}

/**
 * When loading is delayed, load the api script only after the first user
 * interaction.
 */
function setupDelayedLoading() {
  if (window.captchaFoxConfig?.delay !== '1' || window.captchafox) return;

  const events: (
    | 'mousemove'
    | 'mousedown'
    | 'keydown'
    | 'touchstart'
    | 'focusin'
  )[] = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'focusin'];

  const trigger = () => {
    events.forEach((event) => window.removeEventListener(event, trigger, true));
    injectApiScript();
  };

  events.forEach((event) =>
    window.addEventListener(event, trigger, { capture: true, passive: true }),
  );
}

setupDelayedLoading();
