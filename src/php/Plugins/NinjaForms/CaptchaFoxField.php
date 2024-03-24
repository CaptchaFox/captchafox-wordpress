<?php
namespace CaptchaFox\Plugins\NinjaForms;

use CaptchaFox\Helper\Request;
use NF_Fields_Recaptcha;

class CaptchaFoxField extends NF_Fields_Recaptcha {
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
    /**
     * Name
     *
     * @var string
     */
    protected $_name = 'captchafox';

    /**
     * Type
     *
     * @var string
     */
    protected $_type = 'captchafox';

    /**
     * Template
     *
     * @var string
     */
    protected $_templates = 'captchafox';

    /**
     * Nicename
     *
     * @var string
     */
    protected $_nicename;
    // phpcs:enable PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * __construct
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->_nicename = __( 'CaptchaFox', 'captchafox-for-forms' );
    }

    /**
	 * Verify form
	 *
	 * @param array $field Field.
	 * @param mixed $data  Data.
	 *
	 * @return null|string
	 */
	public function validate( $field, $data ) {
		$value = $field['value'] ?? '';

        if ( empty( $value ) ) {
            return __( 'Please complete the captcha', 'captchafox-for-forms' );
        }

        $verified = Request::validate( $value )->success;
        if ( ! $verified ) {
            return __( 'Invalid Captcha', 'captchafox-for-forms' );
        }
	}
}
