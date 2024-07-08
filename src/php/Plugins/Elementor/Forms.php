<?php

namespace CaptchaFox\Plugins\Elementor;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;
use CaptchaFox\Plugins\Plugin;
use Elementor\Controls_Stack;
use Elementor\Plugin as ElementorPlugin;
use ElementorPro\Modules\Forms\Classes\Ajax_Handler;
use ElementorPro\Modules\Forms\Classes\Form_Record;

class Forms extends Plugin {

    const FIELD_NAME = 'captchafox';

    /**
     * Get setup message
     *
     * @return string
     */
    public static function get_setup_message() {
		return esc_html__( 'To use CaptchaFox, you need to add your keys in the CaptchaFox plugin settings.', 'captchafox-for-forms' );
	}

    /**
     * Check if setup is complete
     *
     * @return boolean
     */
	public static function is_enabled() {
		return CaptchaFox::get_sitekey() && CaptchaFox::get_secret();
	}

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
		add_filter( 'elementor_pro/forms/field_types', [ $this, 'add_field_type' ] );
		add_filter( 'elementor_pro/forms/render/item', [ $this, 'filter_field_item' ] );
		add_action( 'elementor_pro/forms/render_field/' . static::FIELD_NAME, [ $this, 'render_field' ], 10, 3 );
        add_action(
			'elementor/element/form/section_form_fields/after_section_end',
			[ $this, 'update_controls' ],
			10,
			2
		);
        add_action( 'elementor/preview/enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        if ( static::is_enabled() ) {
			add_action( 'elementor_pro/forms/validation', [ $this, 'verify' ], 10, 2 );
		}
    }

    /**
     * Add field type
     *
     * @param  mixed $field_types Field Types.
     * @return mixed
     */
    public function add_field_type( $field_types ) {
		$field_types[ static::FIELD_NAME ] = esc_html__( 'CaptchaFox', 'captchafox-for-forms' );

		return $field_types;
	}

	/**
     * Update field control in preview
     *
     * @param Controls_Stack $controls_stack Controls stack.
     * @param array          $args           Arguments.
     *
     * @return void
     */
	public function update_controls( Controls_Stack $controls_stack, array $args ) {
		$control_id   = 'form_fields';
		$control_data = ElementorPlugin::$instance->controls_manager->get_control_from_stack(
			$controls_stack->get_unique_name(),
			$control_id
		);

		$term = [
			'name'     => 'field_type',
			'operator' => '!in',
			'value'    => [ static::FIELD_NAME ],
		];

		$control_data['fields']['width']['conditions']['terms'][]    = $term;
		$control_data['fields']['required']['conditions']['terms'][] = $term;

		ElementorPlugin::$instance->controls_manager->update_control_in_stack(
			$controls_stack,
			$control_id,
			$control_data,
			[ 'recursive' => true ]
		);
	}

    /**
     * Render field
     *
     * @param mixed       $item Item.
     * @param int         $item_index Index.
     * @param Widget_Base $widget Widget Base.
     *
     * @return void
     */
	public function render_field( $item, $item_index, $widget ) {
		$html = '<div class="elementor-field" id="form-field-' . $item['custom_id'] . '">';

		if ( static::is_enabled() ) {
			$this->enqueue_scripts();
			$html .= CaptchaFox::get_html();
		} elseif ( current_user_can( 'manage_options' ) ) {
			$html .= '<div class="elementor-alert elementor-alert-info">';
			$html .= static::get_setup_message();
			$html .= '</div>';
		}

		$html .= '</div>';

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

    /**
     * Remove label from field
     *
     * @param  array|mixed $item Field item.
     * @return array
     */
    public function filter_field_item( $item ) {
		if ( static::FIELD_NAME === $item['field_type'] ) {
			$item['field_label'] = false;
		}

		return $item;
	}

    /**
     * Verify captcha
     *
     * @param Form_Record  $record Record.
     * @param Ajax_Handler $ajax_handler Ajax Handler.
     */
	public function verify( $record, $ajax_handler ) {
		$fields = $record->get_field( [
			'type' => static::FIELD_NAME,
		] );

		if ( empty( $fields ) ) {
			return;
		}
        $field = current( $fields );

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$response_token = isset( $_POST['cf-captcha-response'] ) ?
			filter_var( wp_unslash( $_POST['cf-captcha-response'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS ) :
			'';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

        if ( empty( $response_token ) ) {
			$ajax_handler->add_error( $field['id'], __( 'Please fill out the captcha', 'captchafox-for-forms' ) );

			return;
		}

		$verification = Request::validate( $response_token );

		if ( ! $verification->success ) {
			$ajax_handler->add_error( $field['id'], __( 'Invalid Captcha', 'captchafox-for-forms' ) );

			return;
		}

		$record->remove_field( $field['id'] );
    }

    /**
     * Enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'captchafox', CaptchaFox::get_script(), [], PLUGIN_VERSION, true );
        wp_enqueue_script( 'captchafox-form', constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/js/form.js', [ 'captchafox' ], PLUGIN_VERSION, true );
        wp_enqueue_script(
		'captchafox-elementor',
		constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/js/elementor.js',
		[ 'jquery', 'captchafox-form' ],
		PLUGIN_VERSION,
		true
		);
    }
}
