<?php

namespace CaptchaFox\Settings;

trait FieldRenderer {

    /**
     * Text Field
     *
     * @param  mixed $args Args.
     * @return void
     */
    public function render_text_field( $args ) {
        $option_group = $args['group'];
        $options = get_option( $option_group );
        $field_name = esc_attr( $args['label_for'] );
        $field_type = isset( $args['type'] ) ? $args['type'] : 'text';
        $current_value = isset( $options[ $field_name ] ) ? $options[ $field_name ] : '';

        printf(
            '<input id="%s" name="%s[%s]" type="%s" value="%s">
        ',
            esc_attr( $field_name ),
            esc_attr( $option_group ),
            esc_attr( $field_name ),
            esc_attr( $field_type ),
            esc_html( $current_value )
        );
    }

    /**
     * Number Field
     *
     * @param  mixed $args Args.
     * @return void
     */
    public function render_number_field( $args ) {
        $option_group = $args['group'];
        $options = get_option( $option_group );
        $field_name = esc_attr( $args['label_for'] );
        $min = isset( $args['min'] ) ? (int) $args['min'] : 0;
        $default = isset( $args['default'] ) ? $args['default'] : '';
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $current_value = isset( $options[ $field_name ] ) ? $options[ $field_name ] : $default;

        printf(
            '<input id="%1$s" name="%2$s[%1$s]" type="number" min="%3$d" value="%4$s">',
            esc_attr( $field_name ),
            esc_attr( $option_group ),
            esc_attr( $min ),
            esc_attr( $current_value )
        );

        if ( '' !== $description ) {
            printf( '<p class="description">%s</p>', esc_html( $description ) );
        }
    }

    /**
     * Textarea Field
     *
     * @param  mixed $args Args.
     * @return void
     */
    public function render_textarea_field( $args ) {
        $option_group = $args['group'];
        $options = get_option( $option_group );
        $field_name = esc_attr( $args['label_for'] );
        $placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $current_value = isset( $options[ $field_name ] ) ? $options[ $field_name ] : '';

        printf(
            '<textarea id="%1$s" name="%2$s[%1$s]" rows="4" cols="40" placeholder="%3$s">%4$s</textarea>',
            esc_attr( $field_name ),
            esc_attr( $option_group ),
            esc_attr( $placeholder ),
            esc_textarea( $current_value )
        );

        if ( '' !== $description ) {
            printf( '<p class="description">%s</p>', esc_html( $description ) );
        }
    }

    /**
     * Checkbox Field
     *
     * @param  mixed $args Args.
     * @return void
     */
    public function render_checkbox_field( $args ) {
        $option_group = $args['group'];
        $options = get_option( $option_group );
        $field_name = esc_attr( $args['label_for'] );
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $current_value = isset( $options[ $field_name ] ) ? $options[ $field_name ] : '';

        printf(
            '<label class="cf-switch"><input id="%1$s" name="%2$s[%1$s]" type="checkbox" value="1" %3$s><span class="cf-switch-track"></span><span class="cf-switch-label">%4$s</span></label>',
            esc_attr( $field_name ),
            esc_attr( $option_group ),
            checked( '1', $current_value, false ),
            esc_html( $description )
        );
    }

    /**
     * Select Field
     *
     * @param  mixed $args Args.
     * @return void
     */
    public function render_select_field( $args ) {
        $option_group = $args['group'];
        $options = get_option( $option_group );
        $field_name = esc_attr( $args['label_for'] );
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $current_value = isset( $options[ $field_name ] ) ? $options[ $field_name ] : '';

        $select_options = '';

        foreach ( $args['options'] as $attr => $value ) {
            if ( null === $value ) {
                continue;
            }
            $select_options .= sprintf( '<option value="%s" %s>%s</option>', $attr, selected( $attr, $current_value, false ), $value );
        }

        $allowed_html = [
            'option' => [
                'value'    => [],
                'selected' => [],
            ],
        ];

        printf(
            '<select id="%s" name="%s[%s]" />%s</select>
			',
            esc_attr( $field_name ),
            esc_attr( $option_group ),
            esc_attr( $field_name ),
            wp_kses( $select_options, $allowed_html )
        );

        if ( '' !== $description ) {
            printf( '<p class="description">%s</p>', esc_html( $description ) );
        }
    }
}
