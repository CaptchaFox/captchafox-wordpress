import type { Theme, WidgetDisplayMode, WidgetStart } from '@captchafox/types';

const executeListeners = new WeakMap<HTMLElement, (event: Event) => void>();

const SUBMIT_BUTTON_SELECTOR =
  '[type="submit"], .forminator-button-submit, button.ff-btn-submit';

// requestSubmit never runs the button's own click handlers, so submit through
// a replayed click instead.
function isClickDrivenSubmit(form: HTMLElement, submitButton: HTMLElement) {
  if ('form' !== form.tagName.toLowerCase()) return true;

  if ('submit' !== submitButton.getAttribute('type')?.toLowerCase())
    return true;

  return Boolean(
    submitButton.classList.contains('ninja-forms-field') ||
    submitButton.id.startsWith('gform_submit_button_') ||
    submitButton.closest('.elementor-form'),
  );
}

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

// Ninja Forms replaces the button when it re-renders, which would otherwise
// drop the listener.
function bindHiddenMode(
  form: HTMLFormElement | HTMLElement,
  captchaSlot: HTMLDivElement,
  widgetId: string,
) {
  if ('hidden' !== captchaSlot.dataset.mode) return;

  const [submitButton] = form.querySelectorAll<HTMLElement>(
    SUBMIT_BUTTON_SELECTOR,
  );

  if (!submitButton) return;

  const existingListener = executeListeners.get(submitButton);

  if (existingListener) {
    submitButton.removeEventListener('click', existingListener, true);
  }

  const executeListener = (event: Event) =>
    executeCaptcha(event, form as HTMLFormElement, widgetId, submitButton);

  executeListeners.set(submitButton, executeListener);
  submitButton.addEventListener('click', executeListener, true);
}

function initializeForms() {
  const forms = document.querySelectorAll<HTMLFormElement | HTMLElement>(
    'form, .gform_editor',
  );

  forms.forEach(async (form) => {
    const captchaSlot: HTMLDivElement | null =
      form.querySelector('.captchafox');
    const isAlreadyRendered = captchaSlot?.hasChildNodes();
    const isRendering = captchaSlot?.dataset.cfRendering === '1';

    if (!captchaSlot || !window.captchafox) return;

    // Nothing to render, but the submit button may be a new element.
    if (isAlreadyRendered || isRendering) {
      const renderedWidgetId = form.dataset.cfWidgetId;

      if (renderedWidgetId) {
        bindHiddenMode(form, captchaSlot, renderedWidgetId);
      }

      return;
    }

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

    bindHiddenMode(form, captchaSlot, widgetId);
  });
}

async function executeCaptcha(
  event: Event,
  form: HTMLFormElement,
  widgetId: string,
  submitButton: HTMLElement,
) {
  // Let the replayed click through, but keep the listener for the next attempt.
  if ('1' === submitButton.dataset.cfSubmitting) {
    delete submitButton.dataset.cfSubmitting;
    return;
  }

  event.preventDefault();
  event.stopPropagation();

  if (!form || !window.captchafox) return;

  try {
    await window.captchafox.execute(widgetId);
  } catch (error) {
    console.error(error);
    return;
  }

  if (isClickDrivenSubmit(form, submitButton)) {
    submitButton.dataset.cfSubmitting = '1';
    submitButton.click();
    delete submitButton.dataset.cfSubmitting;
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

// Once the api script loads it calls window.captchaFoxOnLoad (via its onload
// parameter), which renders the widgets.
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

// Delayed loading injects the api script on the first user interaction.
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
