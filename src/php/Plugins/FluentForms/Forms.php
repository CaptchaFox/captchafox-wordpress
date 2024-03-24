<?php

namespace CaptchaFox\Plugins\FluentForms;

use CaptchaFox\Plugins\Plugin;

class Forms extends Plugin {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
		add_action('fluentform/loaded', function () {
			new CaptchaFoxElement();
		});
    }
}
