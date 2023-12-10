<?php

namespace CaptchaFox\Settings;

class Settings {

    /**
     * General Tab
     *
     * @var mixed
     */
    protected $general_tab;

    /**
     * Plugins Tab
     *
     * @var mixed
     */
    protected $plugins_tab;

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
		add_action( 'admin_init', [ $this, 'init_admin_settings' ] );
        add_action( 'admin_menu', [ $this, 'add_to_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'add_styles_to_admin_head' ] );
        add_filter( 'admin_footer_text', [ $this, 'admin_footer_text' ] );
        add_filter( 'update_footer', [ $this, 'update_footer' ], PHP_INT_MAX );
        add_filter(
            'plugin_action_links_' . plugin_basename( constant( 'CAPTCHAFOX_BASE_FILE' ) ),
            [ $this, 'add_settings_link' ]
        );
        add_filter( 'plugin_row_meta', [ $this, 'add_meta_links' ], 10, 4 );
    }

    /**
     * Add plugin to admin menu
     *
     * @return void
     */
    public function add_to_admin_menu() {
        add_options_page( 'CaptchaFox', 'CaptchaFox', 'manage_options', 'captchafox', [ $this, 'init_admin_page' ] );
    }

    /**
     * Add admin styles to head
     *
     * @return void
     */
    public function add_styles_to_admin_head() {
		wp_enqueue_style( 'admin', constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/css/settings.css', [], PLUGIN_VERSION, false );
    }

    /**
     * Init admin settings
     *
     * @return void
     */
    public function init_admin_settings() {
        $this->general_tab = new General();
        $this->plugins_tab = new Plugins();

        $this->general_tab->setup();
        $this->plugins_tab->setup();
    }

    /**
     * Init Admin Page
     *
     * @return void
     */
    public function init_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
            return;
		}

        settings_errors( 'captchafox_messages' );

        $this->render_settings();
    }


    /**
     * Render Settings Content
     *
     * @return void
     */
    public function render_settings() {
		$default_tab = 'general';
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $tab = isset( $tab ) ? $tab : $default_tab;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
		?>
        <div class="cf-admin">
            <div class="header container">
                <?php printf( '<img src="%s" height="25px"/>', esc_url( constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/img/captchafox-logo.svg' ) ); ?>
                <div class="cf-row">
                    <a class="cf-button" target="_blank" href="https://portal.captchafox.com/dashboard?ref=wp">Dashboard ↗</a>
                    <a class="cf-button" target="_blank" href="https://docs.captchafox.com?ref=wp">Documentation ↗</a>
                </div>
            </div>
            <nav id="cf-admin-nav" class="container nav-tab-wrapper">
                <a href="?page=captchafox" class="nav-tab 
                <?php
                if ( $tab === $default_tab ) :
					?>
                    nav-tab-active<?php endif; ?>">General</a>
                <a href="?page=captchafox&tab=plugins" class="nav-tab 
                <?php
                if ( 'plugins' === $tab ) :
					?>
                    nav-tab-active<?php endif; ?>">Plugins</a>
            </nav>

            <div class="wrap tab-content">
                <?php
                switch ( $tab ) :
                    case $default_tab:
                        echo esc_html( $this->general_tab->get_tab_content() );
                        break;
                    case 'plugins':
                        echo esc_html( $this->plugins_tab->get_tab_content() );
                        break;
                endswitch;
                ?>
            </div>
        </div>
		<?php
    }

    /**
     * Add links to plugin meta
     *
     * @param  mixed $plugin_meta Meta.
     * @param  mixed $plugin_file File.
     * @param  mixed $plugin_data Data.
     * @param  mixed $status Status.
     * @return mixed
     */
    public function add_meta_links( $plugin_meta, $plugin_file, $plugin_data, $status ) {
        if ( strpos( $plugin_file, plugin_basename( constant( 'CAPTCHAFOX_BASE_FILE' ) ) ) !== false ) {
            $new_links = array(
                'doc' => sprintf( '<a href="https://docs.captchafox.com" target="_blank">%s</a>', esc_attr( __( 'Documentation', 'captchafox-for-forms' ) ) ),
            );

            $plugin_meta = array_merge( $plugin_meta, $new_links );
        }

        return $plugin_meta;
    }


    /**
     * Add settings link to plugin
     *
     * @param  array $actions Actions.
     * @return array
     */
    public function add_settings_link( array $actions ) {
        $new_actions = [
            'settings' =>
            '<a href="' . admin_url( 'options-general.php?page=captchafox' ) .
                '" aria-label="' . esc_attr( __( 'Settings', 'captchafox-for-forms' ) ) . '">' .
                esc_html( __( 'Settings', 'captchafox-for-forms' ) ) . '</a>',
        ];

        return array_merge( $new_actions, $actions );
    }

    /**
     * Check if settings page is visible
     *
     * @return boolean
     */
    private function is_visible() {
        $current_screen = get_current_screen()->id;
        return 'settings_page_captchafox' === $current_screen;
    }

    /**
     * Update footer text
     *
     * @param  string $content Content.
     * @return string
     */
    public function update_footer( $content ) {
        if ( ! $this->is_visible() ) {
            return $content;
        }

        return sprintf(
        /* translators: 1: plugin version. */
        __( 'Version %s', 'captchafox-for-forms' ),
        PLUGIN_VERSION
        );
	}

    /**
     * Update admin footer text
     *
     * @param  string $text Text.
     * @return string
     */
    public function admin_footer_text( $text ) {
        if ( ! $this->is_visible() ) {
            return $text;
        }

        return '';
    }
}
