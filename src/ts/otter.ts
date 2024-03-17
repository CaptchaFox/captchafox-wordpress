// Based on https://github.com/Codeinwp/otter-blocks/blob/master/src/blocks/frontend/form/captcha.js

interface Window {
  captchaFoxLoadOtter: () => void;
  grecaptcha: any;
  themeisleGutenbergForm: {
    reRecaptchaSitekey: string;
    reRecaptchaAPIURL: string;
  };
  themeisleGutenberg: {
    tokens?: Record<
      string,
      {
        token: string | null;
        reset: () => any;
      }
    >;
  };
}

document.addEventListener('DOMContentLoaded', function () {
  if (!window.themeisleGutenbergForm?.reRecaptchaSitekey) {
    console.warn(
      'Open the Otter Blocks settings to set the sitekey for CaptchaFox'
    );
    return;
  }

  // noop for otter checks
  window.grecaptcha = {};
  window.captchaFoxLoadOtter();
});

window.captchaFoxLoadOtter = () => {
  const forms = document.querySelectorAll<HTMLDivElement>(
    '.wp-block-themeisle-blocks-form'
  );

  forms.forEach(async (form: HTMLDivElement) => {
    if (form?.classList?.contains('has-captcha')) {
      await renderCaptchaFoxOnOtterForm(form);
    }
  });
};

const renderCaptchaFoxOnOtterForm = async (form: HTMLDivElement) => {
  const { id } = form;

  const captchaNode = document.createElement('div');
  const container = form.querySelector('.otter-form__container');
  const existingNode = container?.querySelector(`#${id}`);

  if (existingNode) {
    return;
  }

  captchaNode.id = id;
  container?.insertBefore(captchaNode, container.lastChild);

  const captchaId = await window.captchafox?.render(captchaNode, {
    sitekey: window?.themeisleGutenbergForm?.reRecaptchaSitekey,
    onError: (error) => console.error(error),
    onVerify: (token: string) => {
      if (!window.themeisleGutenberg?.tokens) {
        window.themeisleGutenberg = {};
        window.themeisleGutenberg.tokens = {};
      }
      window.themeisleGutenberg.tokens[id] = {
        token,
        reset: () => window.captchafox?.reset(captchaId),
      };
    },
    onExpire: () => {
      if (!window.themeisleGutenberg?.tokens) {
        window.themeisleGutenberg = {};
        window.themeisleGutenberg.tokens = {};
      }
      window.themeisleGutenberg.tokens[id] = {
        token: null,
        reset: () => null,
      };
    },
  });
};
