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
     * Security Tab
     *
     * @var mixed
     */
    protected $security_tab;

    /**
     * Status Tab
     *
     * @var mixed
     */
    protected $status_tab;

    /**
     * Statistics / Events Tab
     *
     * @var mixed
     */
    protected $events_tab;

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
        // Registered here (not in init_admin_settings) so the "clear
        // statistics" action is handled before admin_init finishes.
        $this->events_tab = new Events();
        $this->events_tab->setup();

		add_action( 'admin_init', [ $this, 'init_admin_settings' ] );
        add_action( 'admin_menu', [ $this, 'add_to_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'add_styles_to_admin_head' ] );
        add_action( 'in_admin_header', [ $this, 'hide_admin_notices' ], PHP_INT_MAX );
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
            add_menu_page(
            'CaptchaFox',
            'CaptchaFox',
            'manage_options',
            'captchafox',
            [ $this, 'init_admin_page' ],
            constant( 'CAPTCHAFOX_BASE_URL' ) . '/assets/img/captchafox-icon.svg',
            58.89
        );

        add_submenu_page(
            'captchafox',
            'CaptchaFox',
            'Security',
            'manage_options',
            'captchafox-security',
            [ $this, 'show_security' ]
        );

        add_submenu_page(
            'captchafox',
            'CaptchaFox',
            'Plugins',
            'manage_options',
            'captchafox-plugins',
            [ $this, 'show_plugins' ]
        );

        add_submenu_page(
            'captchafox',
            'CaptchaFox',
            'Status',
            'manage_options',
            'captchafox-status',
            [ $this, 'show_status' ]
        );

        add_submenu_page(
            'captchafox',
            'CaptchaFox',
            'Statistics',
            'manage_options',
            'captchafox-stats',
            [ $this, 'show_stats' ]
        );
    }

    /**
     * Show plugins menu page
     *
     * @return void
     */
    public function show_plugins() {
        $this->render_settings( 'plugins' );
    }

    /**
     * Show security menu page
     *
     * @return void
     */
    public function show_security() {
        $this->render_settings( 'security' );
    }

    /**
     * Show status menu page
     *
     * @return void
     */
    public function show_status() {
        $this->render_settings( 'status' );
    }

    /**
     * Show statistics menu page
     *
     * @return void
     */
    public function show_stats() {
        $this->render_settings( 'stats' );
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
        $this->security_tab = new Security();
        $this->status_tab = new Status();

        $this->general_tab->setup();
        $this->plugins_tab->setup();
        $this->security_tab->setup();
        $this->status_tab->setup();
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
     * @param string $default_page Tab to render.
     *
     * @return void
     */
    public function render_settings( $default_page = 'general' ) {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $page = explode( $page, '-' )[0];
        $page = isset( $default_page ) ? $default_page : $page;
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
                if ( 'general' === $page ) :
					?>
                    nav-tab-active<?php endif; ?>">General</a>
                <a href="?page=captchafox-security" class="nav-tab
                <?php
                if ( 'security' === $page ) :
					?>
                    nav-tab-active<?php endif; ?>">Security</a>
                <a href="?page=captchafox-plugins" class="nav-tab
                <?php
                if ( 'plugins' === $page ) :
					?>
                    nav-tab-active<?php endif; ?>">Plugins</a>
                <a href="?page=captchafox-stats" class="nav-tab
                <?php
                if ( 'stats' === $page ) :
					?>
                    nav-tab-active<?php endif; ?>">Statistics</a>
                <a href="?page=captchafox-status" class="nav-tab
                <?php
                if ( 'status' === $page ) :
					?>
                    nav-tab-active<?php endif; ?>">Status</a>
            </nav>

            <div class="wrap tab-content">
                <?php
                switch ( $page ) :
                    case 'plugins':
                        echo esc_html( $this->plugins_tab->get_tab_content() );
                        break;
                    case 'security':
                        echo esc_html( $this->security_tab->get_tab_content() );
                        break;
                    case 'status':
                        echo esc_html( $this->status_tab->get_tab_content() );
                        break;
                    case 'stats':
                        echo esc_html( $this->events_tab->get_tab_content() );
                        break;
					default:
                        echo esc_html( $this->general_tab->get_tab_content() );
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
        return $this->is_captchafox_screen();
    }

    /**
     * Check if the current screen belongs to the plugin.
     *
     * @return boolean
     */
    private function is_captchafox_screen() {
        $screen = get_current_screen();

        if ( null === $screen ) {
            return false;
        }

        return false !== strpos( $screen->id, 'captchafox' );
    }

    /**
     * Hide third-party admin notices on the plugin's own screens.
     *
     * @return void
     */
    public function hide_admin_notices() {
        if ( ! $this->is_captchafox_screen() ) {
            return;
        }

        remove_all_actions( 'admin_notices' );
        remove_all_actions( 'all_admin_notices' );
        remove_all_actions( 'user_admin_notices' );
        remove_all_actions( 'network_admin_notices' );
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

        return sprintf(
            /* translators: 1: plugin name, 2: five-star rating link, 3: WordPress.org link. */
            __( 'Enjoying %1$s? Share a %2$s review on %3$s', 'captchafox-for-forms' ),
            '<strong>CaptchaFox for WordPress</strong>',
            '<a href="https://wordpress.org/support/plugin/captchafox-for-forms/reviews/?filter=5#new-post" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Rate CaptchaFox for WordPress five stars on WordPress.org', 'captchafox-for-forms' ) . '">★★★★★</a>',
            '<a href="https://wordpress.org/support/plugin/captchafox-for-forms/reviews/?filter=5#new-post" target="_blank" rel="noopener noreferrer">WordPress.org</a>'
        );
    }
}
