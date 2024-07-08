declare global {
  interface Window {
    captchaFoxOnLoad: () => void;
    captchaFoxWPReset: (selector: string) => void;
  }

  function jQuery(args: any);
}

export {};
