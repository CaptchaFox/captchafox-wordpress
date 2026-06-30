import type {
  Theme,
  WidgetDisplayMode,
  WidgetStart,
} from '@captchafox/types';

const executeListeners = new WeakMap<HTMLElement, (event: Event) => void>();

function resetFormElement(element: HTMLFormElement | HTMLElement) {
  const widgetId = element.dataset.cfWidgetId;
  if (!widgetId) return;

  window.captchafox?.reset(widgetId);
}

function resetFormWidget(formSelector: string) {
  const element = document.querySelector<HTMLFormElement>(formSelector);
  if (!element) return;

  resetFormElement(element);
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
    const isRendering = captchaSlot?.dataset.cfRendering === '1';

    if (!captchaSlot || !window.captchafox || isAlreadyRendered || isRendering)
      return;

    captchaSlot.dataset.cfRendering = '1';

    const mode = captchaSlot.dataset.mode as WidgetDisplayMode;
    const sitekey = captchaSlot.dataset.sitekey;
    const theme = captchaSlot.dataset.theme as Theme;
    const lang = captchaSlot.dataset.lang;
    const start = captchaSlot.dataset.start as WidgetStart | undefined;

    let widgetId: string;

    try {
      widgetId = await window.captchafox.render(captchaSlot, {
        sitekey: sitekey ?? '',
        ...(mode && { mode }),
        ...(lang && { lang }),
        ...(theme && { theme }),
        ...(start && { start }),
        onError: (error) => console.error(error),
      });
    } catch (error) {
      console.error(error);
      return;
    } finally {
      delete captchaSlot.dataset.cfRendering;
    }

    form.dataset.cfWidgetId = widgetId;

    if ('hidden' !== captchaSlot.dataset.mode) {
      return;
    }

    if (submitButton) {
      const existingListener = executeListeners.get(submitButton);

      if (existingListener) {
        submitButton.removeEventListener('click', existingListener, true);
      }

      const executeListener = (event: Event) =>
        executeCaptcha(event, form as HTMLFormElement, widgetId, submitButton);

      executeListeners.set(submitButton, executeListener);
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
    const executeListener = executeListeners.get(submitButton);

    if (executeListener) {
      executeListeners.delete(submitButton);
      submitButton.removeEventListener('click', executeListener, true);
    }

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

// Some optimizers can move the local form script behind the external
// CaptchaFox api script. In that case the api onload callback was missed, so
// initialize immediately once this script finally runs.
if (window.captchafox) {
  initializeForms();
}
