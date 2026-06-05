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
            'supporter_invitation' => 'You are invited to {race_title}',
            'abandoned_checkout' => 'Complete your Duck Race checkout for {race_title}',
            'winner_future_race_marketing' => 'Results and next race update for {race_title}',
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
            'supporter_invitation' => '<p>Hello {first_name},</p><p>You previously supported: {previous_race_result}</p><p>We would love your support again for {race_title}.</p><p><a href="{buy_link}">Buy ducks for this race</a></p>',
            'abandoned_checkout' => '<p>Hello {first_name},</p><p>Your recent checkout for {race_title} appears incomplete.</p><p>Reserved ducks: {duck_numbers}</p><p><a href="{buy_link}">Start a new checkout</a></p>',
            'winner_future_race_marketing' => '<p>Hello {first_name},</p><p>Thanks for taking part in {previous_race_result}.</p><p>Winner position: {winner_position}</p><p>Our next race is {race_title}. Join again here: <a href="{buy_link}">Buy ducks</a></p>',
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
            '{buy_link}' => esc_url( (string) ( $data['buy_link'] ?? '' ) ),
            '{previous_race_result}' => (string) ( $data['previous_race_result'] ?? '' ),
            '{winner_position}' => (string) ( $data['winner_position'] ?? '' ),
        ];

        return strtr( $text, $replacements );
    }
}
