document.addEventListener('DOMContentLoaded', function () {
  function reset() {
    window.captchaFoxWPReset('.wpcf7-form');
  }

  document.addEventListener('wpcf7mailsent', reset);
  document.addEventListener('wpcf7mailfailed', reset);
  document.addEventListener('wpcf7spam', reset);
  document.addEventListener('wpcf7submit', reset);
  document.addEventListener('wpcf7invalid', reset);
});
