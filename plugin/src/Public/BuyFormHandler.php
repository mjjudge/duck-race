<?php

namespace DuckRace\Public;

use DuckRace\Services\RaceService;

defined( 'ABSPATH' ) || exit;

class BuyFormHandler {

    private const NONCE_ACTION = 'duck_race_public_buy';

    public function register(): void {
        add_shortcode( 'duck_race_buy', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode(): string {
        $race = $this->resolve_race();

        if ( null === $race ) {
            return '<p>' . esc_html__( 'Online duck sales are currently closed for this race.', 'duck-race' ) . '</p>';
        }

        ob_start();
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="duck-race-buy-form" data-check-email-url="<?php echo esc_url( rest_url( 'duck-race/v1/check-email' ) ); ?>">
            <input type="hidden" name="action" value="duck_race_start_checkout" />
            <?php wp_nonce_field( self::NONCE_ACTION, 'duck_race_nonce' ); ?>
            <input type="hidden" name="race_id" value="<?php echo esc_attr( (string) $race->id ); ?>" />

            <h3><?php echo esc_html( (string) $race->title ); ?></h3>
            <p><?php echo esc_html( (string) $race->location ); ?> | <?php echo esc_html( (string) $race->race_date ); ?></p>

            <p>
                <label for="duck-race-duck-count"><?php esc_html_e( 'Number of ducks', 'duck-race' ); ?></label>
                <input id="duck-race-duck-count" type="number" name="duck_count" min="1" max="<?php echo esc_attr( (string) $race->max_ducks_per_transaction ); ?>" value="1" required />
            </p>

            <p>
                <label for="duck-race-duck-names"><?php esc_html_e( 'Duck names (optional, comma separated)', 'duck-race' ); ?></label>
                <input id="duck-race-duck-names" type="text" name="duck_names" />
            </p>

            <p>
                <label for="duck-race-chosen"><?php esc_html_e( 'Choose specific online duck numbers (optional, comma separated)', 'duck-race' ); ?></label>
                <input id="duck-race-chosen" type="text" name="chosen_numbers" />
                <small><?php printf( esc_html__( 'Chosen number uplift: %s per duck', 'duck-race' ), esc_html( (string) $race->chosen_number_uplift ) ); ?></small>
            </p>

            <p>
                <label for="duck-race-email"><?php esc_html_e( 'Email', 'duck-race' ); ?></label>
                <input id="duck-race-email" type="email" name="email" required />
            </p>

            <div id="duck-race-recognition" style="display:none;"></div>

            <p>
                <label for="duck-race-first-name"><?php esc_html_e( 'First name', 'duck-race' ); ?></label>
                <input id="duck-race-first-name" type="text" name="first_name" required />
            </p>

            <p>
                <label for="duck-race-last-name"><?php esc_html_e( 'Last name', 'duck-race' ); ?></label>
                <input id="duck-race-last-name" type="text" name="last_name" required />
            </p>

            <p>
                <label><input type="checkbox" name="consent_duck_race" value="1" /> <?php esc_html_e( 'Contact me about future duck races', 'duck-race' ); ?></label><br />
                <label><input type="checkbox" name="consent_organisation" value="1" /> <?php esc_html_e( 'Contact me about other organisation activities', 'duck-race' ); ?></label>
            </p>

            <p style="display:none;" aria-hidden="true">
                <label for="duck-race-website"><?php esc_html_e( 'Website', 'duck-race' ); ?></label>
                <input id="duck-race-website" type="text" name="website" autocomplete="off" tabindex="-1" />
            </p>

            <p>
                <button type="submit"><?php esc_html_e( 'Proceed to Checkout', 'duck-race' ); ?></button>
            </p>
        </form>
        <script>
        (function () {
            const form = document.querySelector('.duck-race-buy-form');
            if (!form) return;
            const emailEl = form.querySelector('#duck-race-email');
            const firstNameEl = form.querySelector('#duck-race-first-name');
            const lastNameEl = form.querySelector('#duck-race-last-name');
            const recognition = form.querySelector('#duck-race-recognition');
            const endpoint = form.getAttribute('data-check-email-url');

            if (!emailEl || !endpoint) return;

            emailEl.addEventListener('blur', async () => {
                const email = emailEl.value.trim();
                if (!email) return;
                try {
                    const res = await fetch(endpoint + '?email=' + encodeURIComponent(email));
                    const data = await res.json();
                    if (data && data.exists) {
                        recognition.style.display = 'block';
                        recognition.textContent = 'Welcome back ' + (data.first_name || '') + '. We found your details from a previous duck race. Please review and update if needed.';
                        if (firstNameEl && !firstNameEl.value) firstNameEl.value = data.first_name || '';
                        if (lastNameEl && !lastNameEl.value) lastNameEl.value = data.last_name || '';
                    }
                } catch (e) {
                    // fail silently for convenience feature
                }
            });
        })();
        </script>
        <?php

        return (string) ob_get_clean();
    }

    private function resolve_race(): ?object {
        $race_attr = sanitize_text_field( (string) ( $_GET['race'] ?? '' ) );

        $service = new RaceService();
        if ( '' !== $race_attr && 'current' !== $race_attr ) {
            return $service->get_by_slug( $race_attr );
        }

        return $service->get_current_open_race();
    }
}
