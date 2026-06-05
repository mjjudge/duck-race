<?php

namespace DuckRace\Mail;

defined( 'ABSPATH' ) || exit;

class TemplateRenderer {

    /**
     * @param array<string, mixed> $data
     */
    public function render_subject( string $template_key, array $data ): string {
        $settings = get_option( 'duck_race_settings', [] );

        $default = match ( $template_key ) {
            'purchase_confirmation' => 'Your Duck Race purchase confirmation - {race_title}',
            'race_reminder' => 'Reminder: {race_title} is on {race_date}',
            default => 'Duck Race update',
        };

        $subject = (string) ( $settings[ 'email_' . $template_key . '_subject' ] ?? $default );

        return $this->replace_tags( $subject, $data );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render_body( string $template_key, array $data ): string {
        $settings = get_option( 'duck_race_settings', [] );

        $default = match ( $template_key ) {
            'purchase_confirmation' => '<p>Hello {first_name},</p><p>Thank you for your purchase for {race_title}.</p><p>Duck numbers: {duck_numbers}</p><p>Duck names: {duck_names}</p><p>Total paid: {purchase_total}</p>',
            'race_reminder' => '<p>Hello {first_name},</p><p>This is an operational reminder for {race_title} on {race_date} at {race_time}, {race_location}.</p><p>Your ducks: {duck_numbers}</p>',
            default => '<p>Hello {first_name},</p><p>Duck Race update.</p>',
        };

        $body = (string) ( $settings[ 'email_' . $template_key . '_body' ] ?? $default );
        $body = $this->replace_tags( $body, $data );

        $logo_url = esc_url( (string) ( $settings['email_logo_url'] ?? '' ) );
        if ( '' !== $logo_url ) {
            $body = '<p><img src="' . $logo_url . '" alt="" style="max-height:64px;width:auto;" /></p>' . $body;
        }

        return wp_kses_post( $body );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function replace_tags( string $text, array $data ): string {
        $replacements = [
            '{first_name}' => (string) ( $data['first_name'] ?? '' ),
            '{last_name}' => (string) ( $data['last_name'] ?? '' ),
            '{organisation_name}' => (string) ( $data['organisation_name'] ?? '' ),
            '{race_title}' => (string) ( $data['race_title'] ?? '' ),
            '{race_date}' => (string) ( $data['race_date'] ?? '' ),
            '{race_time}' => (string) ( $data['race_time'] ?? '' ),
            '{race_location}' => (string) ( $data['race_location'] ?? '' ),
            '{duck_numbers}' => (string) ( $data['duck_numbers'] ?? '' ),
            '{duck_names}' => (string) ( $data['duck_names'] ?? '' ),
            '{purchase_total}' => (string) ( $data['purchase_total'] ?? '' ),
        ];

        return strtr( $text, $replacements );
    }
}
