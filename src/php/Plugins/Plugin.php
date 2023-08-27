<?php

namespace CaptchaFox\Plugins;

abstract class Plugin {

    /**
     * Setup
     *
     * @return void
     */
    abstract public function setup();

    /**
     * __construct
     *
     * @return void
     */
    public function __construct() {
		$this->setup();
    }
}
