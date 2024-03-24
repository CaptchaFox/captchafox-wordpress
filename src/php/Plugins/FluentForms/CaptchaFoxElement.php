<?php

namespace CaptchaFox\Plugins\FluentForms;

use CaptchaFox\Helper\CaptchaFox;
use CaptchaFox\Helper\Request;

class CaptchaFoxElement extends \FluentForm\App\Services\FormBuilder\BaseFieldManager {

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
        'cf-captcha-response',
        'CaptchaFox',
        [ 'captcha' ],
        'advanced'
		);

		add_filter( 'fluentform/response_render_' . $this->key, array( $this, 'renderResponse' ), 10, 3 );
		add_filter( 'fluentform/validate_input_item_' . $this->key, array( $this, 'verify' ), 10, 5 );
	}

	/**
	 * Get Element Component
	 *
	 * @return array
	 */
	public function getComponent() {
		return [
			'index'          => 16,
			'element'        => $this->key,
			'attributes'     => [
				'name' => $this->key,
			],
			'settings'       => [
				'label'            => '',
				'validation_rules' => [],
			],
			'editor_options' => [
				'title'      => $this->title,
				'icon_class' => 'ff-edit-recaptha',
				'template'   => 'inputHidden',
			],
		];
	}

	/**
	 * Render element
	 *
	 * @param  mixed $data Settings.
	 * @param  mixed $form Form.
	 * @return void
	 */
	public function render( $data, $form ) {
		$element_name = $data['element'];
		$settings = $data['settings'];

		$label = '';
		if ( ! empty( $settings['label'] ) ) {
            $label = "<div class='ff-el-input--label'><label>" . $settings['label'] . '</label></div>';
        }

		$container_class = '';
        if ( ! empty( $settings['label_placement'] ) ) {
            $container_class = 'ff-el-form-' . $settings['label_placement'];
        }

		$captcha = CaptchaFox::build_html();

		$el = "<div class='ff-el-input--content'><div data-fluent_id='" . $form->id . "'>{$captcha}</div></div>";
        $html = "<div class='ff-el-group " . esc_attr( $container_class ) . "' >" . fluentform_sanitize_html( $label ) . "{$el}</div>";

		$this->printContent( 'fluentform/rendering_field_html_' . $element_name, $html, $data, $form );
	}

	/**
	 * Render response
	 *
	 * @param string|array|number|null $response Original input from form submission.
	 * @param array                    $field The form field component array.
	 * @param string                   $form_id Form id.
	 * @return string
	 */
    public function renderResponse( $response, $field, $form_id ) {
        return $response;
    }

	/**
	 * Verify input
	 *
	 * @param  mixed $error_message Error message.
	 * @param  mixed $field Field.
	 * @param  mixed $form_data Form Data.
	 * @param  mixed $fields Form fields.
	 * @param  mixed $form Form.
	 * @return array
	 */
	public function verify( $error_message, $field, $form_data, $fields, $form ) {
		$field_name = $field['name'];
		$response = $form_data[ $field_name ];

		if ( empty( $response ) ) {
			return [ __( 'Please fill out the captcha', 'captchafox-for-forms' ) ];
		}

        $verification = Request::validate( $response );

        if ( ! $verification->success ) {
            $error_message = [ __( 'Invalid Captcha', 'captchafox-for-forms' ) ];
        }

		return $error_message;
	}
}
