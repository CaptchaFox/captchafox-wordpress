<?php

namespace CaptchaFox\Plugins\GravityForms;

use GF_Field;
use GF_Fields;
use Exception;
use GFCommon;
use CaptchaFox\Helper\CaptchaFox;

class CaptchaFoxField extends GF_Field {

    /**
     * Type
     *
     * @var string
     */
    public $type = 'captchafox';

	/**
	 * Label
     *
     * @var string
     */
	public $label = 'CaptchaFox';

	/**
	 * Constructor
	 *
	 * @param array $data Data.
	 */
	public function __construct( array $data = [] ) {
		parent::__construct( $data );
		$this->setup();
	}

    /**
     * Setup
     *
     * @return void
     */
	private function setup(): void {
		try {
			GF_Fields::register( $this );
		} catch ( Exception $e ) {
			return;
		}

		add_filter( 'gform_field_groups_form_editor', [ $this, 'add_to_field_groups' ] );
		add_action( 'admin_print_footer_scripts-toplevel_page_gf_edit_forms', [ $this, 'load_scripts' ] );
		add_action( 'gform_field_standard_settings', [ $this, 'render_start_setting' ], 10, 2 );
	}

	/**
	 * Render the "Verification Start" field setting in the form editor.
	 *
	 * @param int $position Settings position being rendered.
	 * @param int $form_id  Current form id.
	 *
	 * @return void
	 */
	public function render_start_setting( $position, $form_id ) {
		// Render just after the standard label/visibility settings.
		if ( 25 !== $position ) {
			return;
		}
		?>
		<li class="captchafox_start_setting field_setting">
			<label for="captchafox_start" class="section_label">
				<?php esc_html_e( 'Verification Start', 'captchafox-for-forms' ); ?>
			</label>
			<select id="captchafox_start" onchange="SetFieldProperty( 'captchafox_start', this.value );">
				<option value="inherit"><?php esc_html_e( 'Use global setting', 'captchafox-for-forms' ); ?></option>
				<option value="none"><?php esc_html_e( 'On interaction', 'captchafox-for-forms' ); ?></option>
				<option value="focus"><?php esc_html_e( 'On form focus', 'captchafox-for-forms' ); ?></option>
				<option value="auto"><?php esc_html_e( 'Automatically', 'captchafox-for-forms' ); ?></option>
			</select>
		</li>
		<?php
	}

	/**
	 * Adds the field button to the specified group
	 *
	 * @param array $field_groups Field groups.
	 *
	 * @return array
	 */
	public function add_to_field_groups( array $field_groups ): array {
		$field_groups['advanced_fields']['fields'][] = [
			'data-type' => 'captchafox',
			'value'     => 'CaptchaFox',
		];

		return $field_groups;
	}

    /**
     * Get form editor field title
     *
     * @return string
     */
	public function get_form_editor_field_title() {
		return esc_attr( 'CaptchaFox' );
	}

	/**
	 * Returns the field's form editor description
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return (
			esc_attr__(
				'Adds a CaptchaFox field to your form to help protect your website from spam and bot abuse.',
				'captchafox-for-forms'
			)
		);
	}

	/**
	 * Returns the warning message to be displayed in the form editor sidebar
	 *
	 * @return string|array
	 */
	public function get_field_sidebar_messages() {
		if ( ( ! empty( CaptchaFox::get_sitekey() ) && ! empty( CaptchaFox::get_secret() ) ) ) {
			return '';
		}

		// Translators: 1. Opening <a> tag with link to the CaptchaFox plugin settings page. 2. closing <a> tag.
		return sprintf( __( 'To use CaptchaFox you must configure the site and secret keys on the %1$sCaptchaFox General Settings%2$s page.', 'captchafox-for-forms' ), "<a href='/wp-admin/options-general.php?page=captchafox' target='_blank'>", '</a>' );
	}

	/**
	 * Set field icon
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return CAPTCHAFOX_BASE_URL . '/assets/img/captchafox-icon-dark.svg';
	}

	/**
	 * Get field settings
	 *
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return [
			'label_placement_setting',
			'captchafox_start_setting',
			'description_setting',
			'css_class_setting',
		];
	}

	/**
	 * Render field
	 *
	 * @param array $form  Form.
	 * @param mixed $value Value.
	 * @param mixed $entry Entry.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = (int) $form['id'];
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$id              = (int) $this->id;
		$field_id        = $is_entry_detail || $is_form_editor || 0 === $form_id ? "input_$id" : 'input_' . $form_id . "_$id";
		$tabindex = GFCommon::$tab_index > 0 ? GFCommon::$tab_index++ : 0;
		$search = 'class="captchafox"';

		if ( ! $is_entry_detail && ! $is_form_editor ) {
			$this->load_frontend_scripts();
		}

		$start = isset( $this->captchafox_start ) ? $this->captchafox_start : 'inherit';

		return str_replace(
			$search,
			$search . ' id="' . $field_id . '" data-tabindex="' . $tabindex . '"',
			CaptchaFox::build_html( [ 'start' => $start ] )
		);
	}

	/**
	 * Load required scripts for the form editor preview
	 *
	 * @return void
	 */
    public function load_scripts() {
        wp_enqueue_script( 'captchafox-form', constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/js/form.js', [], PLUGIN_VERSION, true );
        wp_enqueue_script( 'captchafox-admin', constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/js/gravityFormsAdmin.js', [], PLUGIN_VERSION, true );
        wp_enqueue_script( 'captchafox', CaptchaFox::get_script(), [], PLUGIN_VERSION, true );
	}

	/**
	 * Load required scripts when the field is displayed on the frontend.
	 * The core assets are enqueued through CaptchaFox::build_html.
	 *
	 * @return void
	 */
	public function load_frontend_scripts() {
		wp_enqueue_script(
			'gravity-forms',
			constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/js/gravityForms.js',
			[ 'jquery', 'captchafox-form' ],
			PLUGIN_VERSION,
			true
		);
	}
}
