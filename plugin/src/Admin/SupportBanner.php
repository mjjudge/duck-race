<?php

namespace DuckRace\Admin;

defined( 'ABSPATH' ) || exit;

class SupportBanner {

    private const META_KEY     = 'duck_race_support_dismissed';
    private const NONCE_ACTION = 'duck_race_dismiss_support';

    public static function register_ajax(): void {
        add_action( 'wp_ajax_duck_race_dismiss_support', [ self::class, 'handle_dismiss' ] );
    }

    public static function handle_dismiss(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        update_user_meta( get_current_user_id(), self::META_KEY, '1' );
        wp_send_json_success();
    }

    public static function maybe_render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( get_user_meta( get_current_user_id(), self::META_KEY, true ) ) {
            return;
        }

        $coffee_url = apply_filters( 'duck_race_coffee_url', defined( 'DUCK_RACE_COFFEE_URL' ) ? DUCK_RACE_COFFEE_URL : '' );
        $rotary_url = defined( 'DUCK_RACE_ROTARY_URL' ) ? DUCK_RACE_ROTARY_URL : 'https://rotaryinthevale.org/';
        $nonce      = wp_create_nonce( self::NONCE_ACTION );
        $ajax_url   = wp_json_encode( admin_url( 'admin-ajax.php' ) );

        echo '<div id="duck-race-support-banner" style="background:#e8f5e9;border-left:4px solid #388e3c;border-radius:0 4px 4px 0;padding:14px 18px;margin-bottom:16px;max-width:900px;">';
        echo '<p style="margin:0 0 6px;font-weight:600;font-size:0.97em;">' . esc_html__( 'Enjoying Duck Race?', 'duck-race' ) . '</p>';
        echo '<p style="margin:0 0 10px;font-size:0.9em;color:#333;">';
        echo esc_html__( 'The plugin is free and open source. If it has helped your club run a successful event, please consider supporting its development or donating to Rotary in the Vale.', 'duck-race' );
        echo '</p>';
        echo '<p style="margin:0;">';

        if ( '' !== $coffee_url ) {
            echo '<a href="' . esc_url( $coffee_url ) . '" target="_blank" rel="noopener noreferrer" class="button button-small" style="margin-right:8px;" aria-label="' . esc_attr__( 'Buy the developer a coffee — opens in new tab', 'duck-race' ) . '">';
            echo esc_html__( '☕ Buy a Coffee', 'duck-race' );
            echo '</a>';
        }

        echo '<a href="' . esc_url( $rotary_url ) . '" target="_blank" rel="noopener noreferrer" class="button button-small" style="margin-right:12px;" aria-label="' . esc_attr__( 'Donate to Rotary in the Vale — opens in new tab', 'duck-race' ) . '">';
        echo esc_html__( '❤️ Donate to Rotary', 'duck-race' );
        echo '</a>';

        echo '<button type="button" id="duck-support-dismiss" class="button-link" data-nonce="' . esc_attr( $nonce ) . '" style="font-size:0.85em;text-decoration:underline;color:#555;cursor:pointer;background:none;border:none;padding:0;" aria-label="' . esc_attr__( 'Dismiss this notice permanently', 'duck-race' ) . '">';
        echo esc_html__( 'Dismiss', 'duck-race' );
        echo '</button>';

        echo '</p>';
        echo '</div>';

        ?>
        <script>
        (function () {
            var btn = document.getElementById('duck-support-dismiss');
            if (!btn) return;
            btn.addEventListener('click', function () {
                var banner = document.getElementById('duck-race-support-banner');
                if (banner) { banner.style.display = 'none'; }
                var xhr = new XMLHttpRequest();
                xhr.open('POST', <?php echo $ajax_url; // phpcs:ignore WordPress.Security.EscapeOutput ?>);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send('action=duck_race_dismiss_support&nonce=' + encodeURIComponent(btn.getAttribute('data-nonce')));
            });
        }());
        </script>
        <?php
    }
}
