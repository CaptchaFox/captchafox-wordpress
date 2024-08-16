<?php

namespace CaptchaFox\Settings;

class Plugins {

    /**
     * Setup
     *
     * @return void
     */
    public function setup() {
        $setting_plugins = 'captchafox_plugins';
        register_setting( $setting_plugins, $setting_plugins );
        add_settings_section( $setting_plugins, __( 'Manage plugins', 'captchafox-for-forms' ), [ $this, 'init_plugins_section' ], $setting_plugins );
        add_settings_field('WordPress', $this->get_plugin_logo( 'wp' ), [ $this, 'render_plugin_field' ], $setting_plugins, $setting_plugins, [
            'label_for' => 'wordpress',
            'class'     => 'cf-plugin-item',
            'group'     => $setting_plugins,
            'available' => true,
            'options'   => [
                'login'         => __( 'Login Form', 'captchafox-for-forms' ),
                'register'      => __( 'Register Form', 'captchafox-for-forms' ),
                'lost_password' => __( 'Lost Password Form', 'captchafox-for-forms' ),
                'comment'       => __( 'Comment Form', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('woocommerce', $this->get_plugin_logo( 'woocommerce' ), [ $this, 'render_plugin_field' ], $setting_plugins, $setting_plugins, [
            'label_for' => 'woocommerce',
            'class'     => 'cf-plugin-item',
            'group'     => $setting_plugins,
            'available' => is_plugin_active( 'woocommerce/woocommerce.php' ),
            'options'   => [
                'login'         => __( 'Login Form', 'captchafox-for-forms' ),
                'register'      => __( 'Register Form', 'captchafox-for-forms' ),
                'lost_password' => __( 'Lost Password Form', 'captchafox-for-forms' ),
                'checkout'      => __( 'Checkout', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('elementor', $this->get_plugin_logo( 'elementor' ), [ $this, 'render_plugin_field' ], $setting_plugins, $setting_plugins, [
            'label_for' => 'elementor',
            'class'     => 'cf-plugin-item',
            'group'     => $setting_plugins,
            'available' => is_plugin_active( 'elementor-pro/elementor-pro.php' ),
            'options'   => [
                'form' => __( 'Forms', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('wpforms', $this->get_plugin_logo( 'wpforms' ), [ $this, 'render_plugin_field' ], $setting_plugins, $setting_plugins, [
            'label_for' => 'wpforms',
            'class'     => 'cf-plugin-item',
            'group'     => $setting_plugins,
            'available' => is_plugin_active( 'wpforms-lite/wpforms.php' ),
            'options'   => [
                'form' => __( 'Forms', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('mailchimp', $this->get_plugin_logo( 'mailchimp' ), [ $this, 'render_plugin_field' ], $setting_plugins, $setting_plugins, [
            'label_for' => 'mailchimp',
            'class'     => 'cf-plugin-item',
            'group'     => $setting_plugins,
            'available' => is_plugin_active( 'mailchimp-for-wp/mailchimp-for-wp.php' ),
            'options'   => [
                'form' => __( 'Forms', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('forminator', $this->get_plugin_logo( 'forminator' ), [ $this, 'render_plugin_field' ], $setting_plugins, $setting_plugins, [
            'label_for' => 'forminator',
            'class'     => 'cf-plugin-item',
            'group'     => $setting_plugins,
            'available' => is_plugin_active( 'forminator/forminator.php' ),
            'options'   => [
                'form' => __( 'Forms', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('bbpress', $this->get_plugin_logo( 'bbpress' ), [ $this, 'render_plugin_field' ], $setting_plugins, $setting_plugins, [
            'label_for' => 'bbpress',
            'class'     => 'cf-plugin-item',
            'group'     => $setting_plugins,
            'available' => is_plugin_active( 'bbpress/bbpress.php' ),
            'options'   => [
                'reply'     => __( 'Reply Form', 'captchafox-for-forms' ),
                'new_topic' => __( 'New Topic Form', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('cf7', $this->get_plugin_logo( 'cf7' ), [ $this, 'render_plugin_field' ], $setting_plugins, $setting_plugins, [
            'label_for' => 'cf7',
            'class'     => 'cf-plugin-item',
            'group'     => $setting_plugins,
            'available' => is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ),
            'options'   => [
                'form' => __( 'Forms', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('ninja-forms', $this->get_plugin_logo( 'ninja-forms' ), [ $this, 'render_plugin_field' ], $setting_plugins, $setting_plugins, [
            'label_for' => 'ninja-forms',
            'class'     => 'cf-plugin-item',
            'group'     => $setting_plugins,
            'available' => is_plugin_active( 'ninja-forms/ninja-forms.php' ),
            'options'   => [
                'form' => __( 'Forms', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('gravityforms', $this->get_plugin_logo( 'gravityforms' ), [ $this, 'render_plugin_field' ], $setting_plugins, $setting_plugins, [
            'label_for' => 'gravityforms',
            'class'     => 'cf-plugin-item',
            'group'     => $setting_plugins,
            'available' => is_plugin_active( 'gravityforms/gravityforms.php' ),
            'options'   => [
                'form' => __( 'Forms', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('otter-blocks', $this->get_plugin_logo( 'otter-blocks' ), [ $this, 'render_plugin_field' ], $setting_plugins, $setting_plugins, [
            'label_for' => 'otter-blocks',
            'class'     => 'cf-plugin-item',
            'group'     => $setting_plugins,
            'available' => is_plugin_active( 'otter-blocks/otter-blocks.php' ),
            'options'   => [
                'form' => __( 'Forms', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('fluent-forms', $this->get_plugin_logo( 'fluent-forms' ), [ $this, 'render_plugin_field' ], $setting_plugins, $setting_plugins, [
            'label_for' => 'fluent-forms',
            'class'     => 'cf-plugin-item',
            'group'     => $setting_plugins,
            'available' => is_plugin_active( 'fluentform/fluentform.php' ),
            'options'   => [
                'form' => __( 'Forms', 'captchafox-for-forms' ),
            ],
        ]);
        add_settings_field('avada-forms', $this->get_plugin_logo( 'avada' ), [ $this, 'render_plugin_field' ], $setting_plugins, $setting_plugins, [
            'label_for' => 'avada-forms',
            'class'     => 'cf-plugin-item',
            'group'     => $setting_plugins,
            'available' => get_template() === 'Avada',
            'options'   => [
                'form' => __( 'Forms', 'captchafox-for-forms' ),
            ],
        ]);
    }

    /**
     * Init Section
     *
     * @return void
     */
    public function init_plugins_section() {
		?>
        <p class="cf-plugins-text"><?php esc_html_e( 'Activate CaptchaFox for third-party plugins.', 'captchafox-for-forms' ); ?> <?php esc_html_e( 'Is your plugin not listed below?', 'captchafox-for-forms' ); ?> <a href="https://github.com/captchafox/captchafox-wordpress/issues/new" target="_blank"><?php esc_html_e( 'Request it here', 'captchafox-for-forms' ); ?></a></p>
		<?php
    }

    /**
     * Get Tab Content
     *
     * @return void
     */
    public function get_tab_content() {
		?>
        <form action="options.php" method="post" class="cf-plugins">
            <?php
            settings_fields( 'captchafox_plugins' );
            do_settings_sections( 'captchafox_plugins' );
            submit_button( __( 'Save Changes', 'captchafox-for-forms' ) );
            ?>
        </form>
		<?php
    }

    /**
     * Plugin Card Field
     *
     * @param  mixed $args Args.
     * @return void
     */
    public function render_plugin_field( $args ) {
        $option_group = $args['group'];
        $plugin_available = isset( $args['available'] ) ? $args['available'] : false;
        $options = get_option( $option_group );
        $field_name = esc_attr( $args['label_for'] );
        $current_value = isset( $options[ $field_name ] ) ? $options[ $field_name ] : [];

        $select_options = '';

        foreach ( $args['options'] as $attr => $value ) {
            if ( null === $value ) {
                continue;
            }
            $name = sprintf(
                '%s[%s][]',
                $option_group,
                $field_name,
            );

            $id = sprintf(
                '%s[%s]',
                $field_name,
                $attr
            );

            $checked = in_array( $attr, $current_value, true ) ? 'checked ' : '';
            $disabled = $plugin_available ? '' : 'disabled=""';

            $select_options .= sprintf(
                '<div><input name="%s" id="%s" type="checkbox" value="%s" %s/><label for="%s">%s</label></div>
            ',
                esc_attr( $name ),
                esc_attr( $id ),
                esc_attr( $attr ),
                esc_attr( $checked . $disabled ),
                esc_attr( $id ),
                esc_attr( $value )
            );
        }

        $allowed_html = [
            'div'   => [],
            'input' => [
                'value'    => [],
                'name'     => [],
                'type'     => [],
                'id'       => [],
                'checked'  => [],
                'disabled' => [],
            ],
            'label' => [
                'for' => [],
            ],
        ];

        printf(
            '<div class="cf-input-plugin">%s</div>
		',
        wp_kses( rtrim( $select_options ), $allowed_html )
        );
    }

    /**
     * Get Plugin Logo
     *
     * @param  string $plugin Plugin Name.
     * @return string
     */
    private function get_plugin_logo( string $plugin ) {
        return sprintf( '<img src="%s" height="40px"/>', constant( 'CAPTCHAFOX_BASE_URL' ) . "/assets/img/$plugin-logo.png" );
    }
}
