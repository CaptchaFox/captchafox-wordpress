<?php

namespace CaptchaFox\Plugins\NinjaForms;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Plugins\Plugin;

class Forms extends Plugin {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
        add_filter( 'ninja_forms_register_fields', [ $this, 'register_field' ] );
		add_filter( 'ninja_forms_field_template_file_paths', [ $this, 'register_template' ] );
		add_filter( 'ninja_forms_localize_field_captchafox', [ $this, 'render_field' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ] );
    }


    /**
	 * Register CaptchaFox field
	 *
	 * @param array|mixed $fields Fields.
	 *
	 * @return array
	 */
	public function register_field( $fields ): array {
		$fields = (array) $fields;
		$fields['captchafox'] = new CaptchaFoxField();

		return $fields;
	}

	/**
	 * Add custom template
	 *
	 * @param array|mixed $paths Paths.
	 *
	 * @return array
	 */
	public function register_template( $paths ): array {
		$paths = (array) $paths;
		$paths[] = __DIR__ . '/templates/';

		return $paths;
	}

    /**
     * Render widget into field
     *
     * @param  mixed $field Field.
     * @return array
     */
    public function render_field( $field ): array {
		$field = (array) $field;

		$id = $field['id'] ?? 0;
		$captchafox = str_replace(
			'<div',
			'<div id="nf-cf-' . $id . '"',
			CaptchaFox::get_ob_html()
		);

		$field['settings']['captchafox'] = $captchafox;

		return $field;
	}

	/**
     * Load required scripts
     *
     * @return void
     */
    public function load_scripts() {
		wp_enqueue_script(
            'captchafox-ninjaforms',
            constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/js/ninjaforms.js',
            [ 'nf-front-end' ],
            PLUGIN_VERSION,
            true
        );
    }
}
