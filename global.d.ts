declare global {
  interface Window {
    captchaFoxOnLoad: () => void;
    captchaFoxWPReset: (selector: string) => void;
    captchaFoxConfig?: {
      api: string;
      delay: string;
    };
  }

  function jQuery(args: any);
}

export {};
