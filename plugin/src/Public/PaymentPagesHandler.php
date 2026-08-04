<?php

namespace DuckRace\Public;

use DuckRace\Security\RequestGuard;

defined( 'ABSPATH' ) || exit;

class PaymentPagesHandler {

    public function register(): void {
        add_shortcode( 'duck_race_payment_success', [ $this, 'success_shortcode' ] );
        add_shortcode( 'duck_race_payment_failure', [ $this, 'failure_shortcode' ] );
        add_action( 'template_redirect', [ $this, 'prevent_caching' ] );
    }

    /**
     * The failure page echoes a per-request ?error= message and the success page carries a
     * per-buyer ?purchase_id= — a cached copy would show one visitor's payment result to the
     * next. Must run before any output starts, so this is hooked to template_redirect.
     */
    public function prevent_caching(): void {
        if ( ! is_singular() ) {
            return;
        }

        $post = get_post();
        if ( ! $post instanceof \WP_Post ) {
            return;
        }

        $content = (string) $post->post_content;
        if ( ! has_shortcode( $content, 'duck_race_payment_success' ) && ! has_shortcode( $content, 'duck_race_payment_failure' ) ) {
            return;
        }

        RequestGuard::send_nocache_headers();
    }

    public function success_shortcode(): string {
        return '<div class="duck-race-payment-result"><h2>'
            . esc_html__( 'Thank you. Your checkout has been received.', 'duck-race' )
            . '</h2><p>'
            . esc_html__( 'Your payment is only confirmed once Stripe webhook verification completes. Please wait for your confirmation email.', 'duck-race' )
            . '</p></div>';
    }

    public function failure_shortcode(): string {
        $error = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) );

        $html = '<div class="duck-race-payment-result"><h2>'
            . esc_html__( 'Payment not confirmed', 'duck-race' )
            . '</h2><p>'
            . esc_html__( 'Your ducks are not confirmed until payment succeeds.', 'duck-race' )
            . '</p>';

        if ( '' !== $error ) {
            $html .= '<p><strong>' . esc_html( $error ) . '</strong></p>';
        }

        $html .= '</div>';

        return $html;
    }
}
