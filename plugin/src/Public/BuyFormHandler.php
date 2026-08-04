<?php

namespace DuckRace\Public;

use DuckRace\Security\RequestGuard;
use DuckRace\Services\DuckAllocationService;
use DuckRace\Services\RaceService;

defined( 'ABSPATH' ) || exit;

class BuyFormHandler {

    private const NONCE_ACTION = 'duck_race_public_buy';

    /** @var bool Tracks whether form CSS has been output this request. */
    private static bool $styles_output = false;

    /** @var bool Ensures the footer script is only queued once per page. */
    private static bool $script_queued = false;

    public function register(): void {
        add_shortcode( 'duck_race_buy', [ $this, 'render_shortcode' ] );
        add_action( 'template_redirect', [ $this, 'prevent_caching' ] );
    }

    /**
     * The buy form bakes live duck availability and a single-use nonce into the HTML on every
     * render — a cached copy hands every visitor the same sold-out duck number and an expired
     * nonce. Must run before any output starts, so this is hooked to template_redirect rather
     * than the shortcode callback.
     */
    public function prevent_caching(): void {
        if ( ! is_singular() ) {
            return;
        }

        $post = get_post();
        if ( ! $post instanceof \WP_Post || ! has_shortcode( (string) $post->post_content, 'duck_race_buy' ) ) {
            return;
        }

        RequestGuard::send_nocache_headers();
    }

    public function render_shortcode(): string {
        $race = $this->resolve_race();

        if ( null === $race ) {
            return '<p>' . esc_html__( 'Online duck sales are currently closed.', 'duck-race' ) . '</p>';
        }

        $settings  = get_option( 'duck_race_settings', [] );
        $org_name  = (string) ( $settings['organisation_name'] ?? '' );
        $price_raw = (float) $race->price_per_duck;
        $price     = number_format( $price_raw, 2 );

        // DR-201: Currency symbol.
        $currency_symbol = (string) ( $settings['currency_symbol'] ?? '£' );

        // DR-202: Gift Aid.
        $show_gift_aid = ! array_key_exists( 'enable_gift_aid', $settings ) || ! empty( $settings['enable_gift_aid'] );

        // DR-203: Consent labels and privacy URL.
        $consent_label_1  = (string) ( $settings['consent_label_duck_race'] ?? '' );
        $consent_label_2  = (string) ( $settings['consent_label_organisation'] ?? '' );
        $privacy_url      = (string) ( $settings['privacy_policy_url'] ?? '' );

        // DR-204: Donation amounts (skip button if value is 0).
        $don_amt_1 = max( 0, (int) ( $settings['donation_amount_1'] ?? 5 ) );
        $don_amt_2 = max( 0, (int) ( $settings['donation_amount_2'] ?? 10 ) );
        $don_amt_3 = max( 0, (int) ( $settings['donation_amount_3'] ?? 15 ) );
        $max_ducks = max( 1, (int) $race->max_ducks_per_transaction );

        // Pre-fetch available numbers so duck rows are server-rendered (no JS async needed).
        $allocator       = new DuckAllocationService();
        $preview_numbers = $allocator->next_available_numbers( $race, 'online', $max_ducks );
        $available_count = count( $preview_numbers );

        // Determine effective max the buyer can select.
        $effective_max = $available_count > 0 ? min( $max_ducks, $available_count ) : 0;

        $is_test         = 'test' === (string) $race->status;
        $check_email_url = esc_url( rest_url( 'duck-race/v1/check-email' ) );
        $duck_icon_url   = esc_url( DUCK_RACE_PLUGIN_URL . 'assets/images/lostduck.svg' );

        $date_display = $this->format_race_datetime( (string) $race->race_date, (string) $race->race_time );
        $location     = (string) $race->location;

        ob_start();

        if ( ! self::$styles_output ) {
            self::$styles_output = true;
            $this->render_styles( $duck_icon_url );
        }
        ?>
        <div class="duck-race-buy-wrap">

        <div class="duck-race-event-header">
            <h2 class="duck-race-event-title"><?php echo esc_html( (string) $race->title ); ?></h2>
            <p class="duck-race-event-meta">
                <?php if ( '' !== $location ) : ?>
                    <span><?php echo esc_html( $location ); ?></span>
                    <?php if ( '' !== $date_display ) : ?> &middot; <?php endif; ?>
                <?php endif; ?>
                <?php if ( '' !== $date_display ) : ?>
                    <span><?php echo esc_html( $date_display ); ?></span>
                <?php endif; ?>
            </p>
        </div>

        <?php if ( $is_test ) : ?>
            <div class="duck-race-test-banner" role="status">
                <strong>TEST MODE</strong> — this is a training race. No payment will be taken.
                Duck purchases will be cleared when the race status is changed.
            </div>
        <?php endif; ?>

        <?php if ( isset( $_GET['error'] ) ) : ?>
            <div class="duck-race-form-error-banner" role="alert">
                <?php echo esc_html( sanitize_text_field( wp_unslash( (string) $_GET['error'] ) ) ); ?>
            </div>
        <?php endif; ?>

        <form
            method="post"
            action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
            class="duck-race-buy-form"
            data-check-email-url="<?php echo $check_email_url; ?>"
            novalidate
        >
            <input type="hidden" name="action" value="<?php echo esc_attr( $is_test ? 'duck_race_simulate_checkout' : 'duck_race_start_checkout' ); ?>" />
            <?php wp_nonce_field( self::NONCE_ACTION, 'duck_race_nonce' ); ?>
            <input type="hidden" name="race_id" value="<?php echo esc_attr( (string) $race->id ); ?>" />

            <?php /* ── Email ─────────────────────────────────────── */ ?>
            <div class="drb-row">
                <label for="drb-email"><?php esc_html_e( 'Your email address', 'duck-race' ); ?> <span class="drb-req" aria-hidden="true">*</span></label>
                <input
                    id="drb-email"
                    class="drb-input drb-input--wide"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    inputmode="email"
                />
            </div>

            <?php /* ── Recognition banner ──────────────────────────── */ ?>
            <div id="drb-recognition" class="drb-recognition" role="status" aria-live="polite" style="display:none;"></div>

            <?php /* ── Name row ──────────────────────────────────── */ ?>
            <div class="drb-row drb-two-col">
                <div>
                    <label for="drb-first-name"><?php esc_html_e( 'First name', 'duck-race' ); ?> <span class="drb-req" aria-hidden="true">*</span></label>
                    <input id="drb-first-name" class="drb-input" type="text" name="first_name" required autocomplete="given-name" />
                </div>
                <div>
                    <label for="drb-last-name"><?php esc_html_e( 'Surname', 'duck-race' ); ?> <span class="drb-req" aria-hidden="true">*</span></label>
                    <input id="drb-last-name" class="drb-input" type="text" name="last_name" required autocomplete="family-name" />
                </div>
            </div>

            <?php /* ── Address ───────────────────────────────────── */ ?>
            <div class="drb-row">
                <label for="drb-address1"><?php esc_html_e( 'Address Line 1', 'duck-race' ); ?></label>
                <input id="drb-address1" class="drb-input" type="text" name="address_line_1" autocomplete="address-line1" />
            </div>
            <div class="drb-row">
                <label for="drb-address2"><?php esc_html_e( 'Address Line 2', 'duck-race' ); ?></label>
                <input id="drb-address2" class="drb-input" type="text" name="address_line_2" autocomplete="address-line2" />
            </div>
            <div class="drb-row drb-two-col">
                <div>
                    <label for="drb-city"><?php esc_html_e( 'Town / City', 'duck-race' ); ?></label>
                    <input id="drb-city" class="drb-input" type="text" name="city" autocomplete="address-level2" />
                </div>
                <div>
                    <label for="drb-county"><?php esc_html_e( 'County', 'duck-race' ); ?></label>
                    <input id="drb-county" class="drb-input" type="text" name="county" autocomplete="address-level1" />
                </div>
            </div>
            <div class="drb-row drb-two-col">
                <div>
                    <label for="drb-postcode"><?php esc_html_e( 'Postcode', 'duck-race' ); ?> <span class="drb-req" aria-hidden="true">*</span></label>
                    <input id="drb-postcode" class="drb-input" type="text" name="postcode" required autocomplete="postal-code" style="text-transform:uppercase;" />
                </div>
                <div>
                    <label for="drb-phone"><?php esc_html_e( 'Phone number', 'duck-race' ); ?></label>
                    <input id="drb-phone" class="drb-input" type="tel" name="phone" autocomplete="tel" inputmode="tel" />
                </div>
            </div>

            <?php /* ── Duck count ───────────────────────────────── */ ?>
            <div class="drb-row">
                <label for="drb-duck-count"><?php esc_html_e( 'How many ducks would you like to race?', 'duck-race' ); ?> <span class="drb-req" aria-hidden="true">*</span></label>
                <?php if ( $effective_max > 0 ) : ?>
                <input
                    id="drb-duck-count"
                    class="drb-input drb-input--narrow"
                    type="number"
                    name="duck_count"
                    min="1"
                    max="<?php echo esc_attr( (string) $effective_max ); ?>"
                    value="1"
                    required
                />
                <p class="drb-hint"><?php printf( esc_html__( '%1$s%2$s per duck — maximum %3$d per order', 'duck-race' ), esc_html( $currency_symbol ), esc_html( $price ), $effective_max ); ?></p>
                <?php else : ?>
                <p class="drb-no-ducks"><?php esc_html_e( 'Sorry, no ducks are currently available for online purchase.', 'duck-race' ); ?></p>
                <?php endif; ?>
            </div>

            <?php /* ── Duck entries — server-rendered, JS controls visibility ── */ ?>
            <?php if ( $available_count > 0 ) : ?>
            <div class="drb-entries-section">
                <p class="drb-entries-intro"><?php esc_html_e( 'You can give your ducks a name — great if you\'re racing one in honour of someone special:', 'duck-race' ); ?></p>
                <div id="drb-entries" class="drb-entries-list">
                    <?php foreach ( $preview_numbers as $i => $num ) :
                        $hidden_class = $i > 0 ? ' drb-entry-row--hidden' : '';
                        $disabled     = $i > 0 ? ' disabled' : '';
                    ?>
                    <div class="drb-entry-row<?php echo esc_attr( $hidden_class ); ?>" data-row="<?php echo esc_attr( (string) $i ); ?>">
                        <div class="drb-duck">
                            <span class="drb-duck__icon"></span>
                            <span class="drb-duck__number"><?php echo esc_html( (string) $num ); ?></span>
                        </div>
                        <input type="hidden" name="duck_number_preview[]" value="<?php echo esc_attr( (string) $num ); ?>"<?php echo $disabled; ?> />
                        <div class="drb-entry-name">
                            <label for="drb-duck-name-<?php echo esc_attr( (string) $i ); ?>">
                                <?php printf( esc_html__( 'Name for duck #%d', 'duck-race' ), $num ); ?>
                            </label>
                            <input
                                id="drb-duck-name-<?php echo esc_attr( (string) $i ); ?>"
                                type="text"
                                name="duck_name[]"
                                class="drb-input"
                                placeholder="<?php esc_attr_e( 'Optional', 'duck-race' ); ?>"
                                autocomplete="off"
                                <?php echo $disabled; ?>
                            />
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php /* ── Voluntary donation ──────────────────────── */ ?>
            <div class="drb-row">
                <span class="drb-label"><?php esc_html_e( 'Add a voluntary donation (optional)', 'duck-race' ); ?></span>
                <div class="drb-donation-buttons" role="group" aria-label="<?php esc_attr_e( 'Donation amount', 'duck-race' ); ?>">
                    <?php if ( $don_amt_1 > 0 ) : ?>
                    <button type="button" class="drb-donation-btn" data-amount="<?php echo esc_attr( (string) $don_amt_1 ); ?>"><?php echo esc_html( $currency_symbol . $don_amt_1 ); ?></button>
                    <?php endif; ?>
                    <?php if ( $don_amt_2 > 0 ) : ?>
                    <button type="button" class="drb-donation-btn" data-amount="<?php echo esc_attr( (string) $don_amt_2 ); ?>"><?php echo esc_html( $currency_symbol . $don_amt_2 ); ?></button>
                    <?php endif; ?>
                    <?php if ( $don_amt_3 > 0 ) : ?>
                    <button type="button" class="drb-donation-btn" data-amount="<?php echo esc_attr( (string) $don_amt_3 ); ?>"><?php echo esc_html( $currency_symbol . $don_amt_3 ); ?></button>
                    <?php endif; ?>
                    <button type="button" class="drb-donation-btn" data-amount="other"><?php esc_html_e( 'Other', 'duck-race' ); ?></button>
                </div>
                <input
                    id="drb-donation-custom"
                    class="drb-input drb-input--narrow"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="<?php echo esc_attr( $currency_symbol . '0.00' ); ?>"
                    autocomplete="off"
                    style="display:none;margin-top:8px;"
                    aria-label="<?php esc_attr_e( 'Custom donation amount', 'duck-race' ); ?>"
                />
                <input type="hidden" name="donation_amount" id="drb-donation" value="0" />
            </div>

            <?php /* ── Total ────────────────────────────────────── */ ?>
            <div class="drb-row drb-total-row">
                <span><?php esc_html_e( 'Total cost', 'duck-race' ); ?></span>
                <span id="drb-total" class="drb-total-amount"><?php echo esc_html( $currency_symbol . $price ); ?></span>
            </div>

            <?php /* ── Consent ──────────────────────────────────── */ ?>
            <fieldset class="drb-fieldset">
                <legend class="drb-legend"><?php esc_html_e( 'Communication preferences', 'duck-race' ); ?></legend>
                <label class="drb-check-label">
                    <input type="checkbox" name="consent_duck_race" value="1" id="drb-consent-duck-race" />
                    <span><?php echo esc_html( '' !== $consent_label_1 ? $consent_label_1 : __( 'Contact me about future duck races', 'duck-race' ) ); ?></span>
                </label>
                <label class="drb-check-label">
                    <input type="checkbox" name="consent_organisation" value="1" id="drb-consent-organisation" />
                    <span><?php
                    if ( '' !== $consent_label_2 ) {
                        echo esc_html( str_replace( '{organisation_name}', $org_name, $consent_label_2 ) );
                    } elseif ( '' !== $org_name ) {
                        printf( esc_html__( 'Contact me about other %s activities', 'duck-race' ), esc_html( $org_name ) );
                    } else {
                        esc_html_e( 'Contact me about other organisation activities', 'duck-race' );
                    }
                    ?></span>
                </label>
                <?php if ( '' !== $privacy_url ) : ?>
                <p style="margin:8px 0 0;font-size:0.85em;color:#555;">
                    <a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Privacy policy', 'duck-race' ); ?></a>
                </p>
                <?php endif; ?>
                <p id="drb-consent-imported" style="display:none;margin:6px 0 0;font-size:13px;color:#555;">
                    <?php esc_html_e( 'Consent settings imported from your previous visit — untick to change.', 'duck-race' ); ?>
                </p>
            </fieldset>

            <?php /* ── Gift Aid ─────────────────────────────────── */ ?>
            <?php if ( $show_gift_aid ) : ?>
            <fieldset class="drb-fieldset drb-gift-aid-fieldset">
                <legend class="drb-legend"><?php esc_html_e( 'Gift Aid', 'duck-race' ); ?></legend>
                <label class="drb-check-label">
                    <input type="checkbox" name="gift_aid" value="1" id="drb-gift-aid" />
                    <span><strong><?php esc_html_e( 'Boost your contribution by 25% at no cost to you!', 'duck-race' ); ?></strong><br />
                        <span class="drb-hint" style="margin-top:4px;display:block;">
                            <?php esc_html_e( 'If you are a UK taxpayer, we can claim an extra 25p for every £1 you pay — on your duck entry fee and any donation — through Gift Aid.', 'duck-race' ); ?>
                        </span>
                    </span>
                </label>
                <p class="drb-gift-aid-declaration" id="drb-gift-aid-text" style="display:none;font-size:0.85em;color:#444;border-left:3px solid #dfbe00;padding:8px 12px;margin:10px 0 4px;">
                    <?php esc_html_e( 'I am a UK taxpayer and understand that if I pay less Income Tax and/or Capital Gains Tax than the amount of Gift Aid claimed on all my donations in that tax year, it is my responsibility to pay any difference. Gift Aid is reclaimed by the charity from the tax you pay for the current tax year.', 'duck-race' ); ?>
                </p>
                <p class="drb-hint" id="drb-gift-aid-address-note" style="display:none;">
                    <?php esc_html_e( 'Your home address (entered above) is required for Gift Aid — please ensure it is filled in correctly.', 'duck-race' ); ?>
                </p>
            </fieldset>
            <?php endif; ?>

            <?php /* ── Honeypot ─────────────────────────────────── */ ?>
            <p style="display:none;" aria-hidden="true">
                <label for="drb-website"><?php esc_html_e( 'Website', 'duck-race' ); ?></label>
                <input id="drb-website" type="text" name="website" autocomplete="off" tabindex="-1" />
            </p>

            <?php /* ── Checkout button ─────────────────────────── */ ?>
            <div class="drb-row drb-submit-row">
                <?php if ( $is_test ) : ?>
                <button type="submit" class="drb-checkout-btn drb-checkout-btn--test"<?php echo $effective_max <= 0 ? ' disabled' : ''; ?>>
                    Simulate Checkout (Test Mode)
                </button>
                <?php else : ?>
                <button type="submit" class="drb-checkout-btn"<?php echo $effective_max <= 0 ? ' disabled' : ''; ?>><svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg><span><?php esc_html_e( 'Proceed to Checkout', 'duck-race' ); ?></span></button>
                <?php endif; ?>
            </div>

        </form>
        </div>

        <?php

        if ( $effective_max > 0 ) {
            $this->maybe_enqueue_form_js( (int) $race->id, $price_raw, $check_email_url );
        }



        return (string) ob_get_clean();
    }

    private function render_styles( string $duck_icon_url ): void {
        ?>
        <style>
        .duck-race-buy-wrap{max-width:600px;margin:0 auto;font-family:inherit;line-height:1.5;}
        .duck-race-event-header{background:#f5ef9a;border-left:4px solid #dfbe00;padding:10px 14px;margin-bottom:14px;border-radius:4px;}
        .duck-race-event-title{margin:0 0 2px;font-size:1.2em;font-weight:700;line-height:1.3;}
        .duck-race-event-meta{margin:0;color:#555;font-size:0.9em;}
        .duck-race-event-header p{margin:0;}
        .duck-race-form-error-banner{background:#fee2e2;border:1px solid #f87171;border-radius:4px;padding:8px 12px;margin-bottom:12px;color:#7f1d1d;}
        .duck-race-buy-form .drb-row{margin-bottom:10px;}
        .duck-race-buy-form label,.duck-race-buy-form .drb-legend{display:block;font-weight:600;margin-bottom:3px;font-size:0.88em;color:#222;}
        .drb-req{color:#c00;margin-left:1px;}
        .drb-input{width:100%;padding:6px 10px;border:1px solid #ccc;border-radius:4px;font-size:0.95em;box-sizing:border-box;background:#fff;}
        .drb-input:focus{outline:2px solid #2563eb;outline-offset:0;border-color:#2563eb;}
        .drb-input--wide{max-width:100%;}
        .drb-input--narrow{max-width:120px;}
        .drb-two-col{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
        .drb-hint{font-size:0.82em;color:#666;margin:3px 0 0;}
        .drb-no-ducks{color:#c00;font-size:0.9em;margin:3px 0 0;}
        .drb-recognition{background:#ecfdf5;border:1px solid #6ee7b7;border-radius:4px;padding:8px 12px;font-size:0.88em;color:#065f46;margin-bottom:10px;}
        .drb-entries-section{margin-bottom:12px;}
        .drb-entries-intro{font-size:0.9em;color:#444;margin-bottom:8px;}
        .drb-entries-list{border:1px solid #e0e0e0;border-radius:6px;padding:6px 14px;background:#fafafa;}
        .drb-entry-row{display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid #eee;}
        .drb-entry-row:last-child{border-bottom:none;}
        .drb-entry-row--hidden{display:none;}
        .drb-duck{position:relative;display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;flex-shrink:0;}
        .drb-duck__icon{width:56px;height:56px;background-color:#f5ef9a;-webkit-mask-image:url("<?php echo esc_url( $duck_icon_url ); ?>");mask-image:url("<?php echo esc_url( $duck_icon_url ); ?>");-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;}
        .drb-duck__number{position:absolute;top:66%;left:50%;transform:translate(-50%,-50%);font-size:13px;font-weight:800;color:#222;text-shadow:0 0 2px rgba(255,255,255,.7);line-height:1;z-index:2;pointer-events:none;}
        .drb-entry-name{flex:1;min-width:0;}
        .drb-entry-name label{font-weight:400;font-size:0.84em;color:#555;}
        .drb-total-row{display:flex;align-items:baseline;justify-content:space-between;border-top:2px solid #e0e0e0;padding-top:10px;font-size:1em;}
        .drb-total-amount{font-size:1.35em;font-weight:700;color:#1a1a1a;}
        .drb-fieldset{border:1px solid #e0e0e0;border-radius:4px;padding:10px 12px;margin-bottom:12px;}
        .drb-fieldset .drb-legend{font-size:0.95em;margin-bottom:6px;}
        .duck-race-buy-form .drb-check-label{display:flex;align-items:flex-start;gap:8px;font-weight:400;cursor:pointer;font-size:0.9em;margin-bottom:5px;}
        .duck-race-buy-form .drb-check-label input{margin-top:2px;flex-shrink:0;width:16px;height:16px;cursor:pointer;}
        .drb-label{display:block;font-weight:600;margin-bottom:6px;font-size:0.88em;color:#222;}
        .drb-donation-buttons{display:flex;gap:6px;flex-wrap:wrap;}
        .drb-donation-btn{padding:6px 14px;border:2px solid #ccc;border-radius:4px;background:#fff;cursor:pointer;font-size:0.92em;font-weight:600;color:#333;transition:border-color .15s,background .15s;}
        .drb-donation-btn:hover{border-color:#1d4ed8;color:#1d4ed8;}
        .drb-donation-btn.drb-donation-btn--active{border-color:#1d4ed8;background:#eff6ff;color:#1d4ed8;}
        .drb-gift-aid-fieldset{background:#fffbeb;border-color:#dfbe00;}
        .drb-submit-row{margin-top:6px;}
        .drb-checkout-btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:12px 24px;background:#1d4ed8;color:#fff;border:none;border-radius:6px;font-size:1.05em;font-weight:700;line-height:1.2;cursor:pointer;letter-spacing:.01em;transition:background .15s,transform .1s;text-decoration:none;text-transform:none;}
        .drb-checkout-btn:hover{background:#1e40af;}
        .drb-checkout-btn:active{transform:scale(.98);}
        .drb-checkout-btn:focus-visible{outline:3px solid #93c5fd;outline-offset:2px;}
        .drb-checkout-btn[disabled]{background:#93c5fd;cursor:not-allowed;}
        .drb-checkout-btn--test{background:#d97706;}
        .drb-checkout-btn--test:hover{background:#b45309;}
        .duck-race-test-banner{background:#fef3c7;border:2px dashed #d97706;border-radius:4px;padding:8px 14px;margin-bottom:12px;color:#92400e;font-size:0.9em;}
        @media(max-width:480px){.drb-two-col{grid-template-columns:1fr;}.drb-duck{width:48px;height:48px;}.drb-duck__icon{width:48px;height:48px;}.drb-duck__number{font-size:11px;}}
        </style>
        <?php
    }

    private function format_race_datetime( string $date, string $time ): string {
        if ( '' === $date ) {
            return '';
        }

        $ts = strtotime( $date );
        if ( false === $ts ) {
            return $date;
        }

        $day = (int) date( 'j', $ts );
        $suffix = match ( true ) {
            in_array( $day, [ 11, 12, 13 ], true ) => 'th',
            $day % 10 === 1 => 'st',
            $day % 10 === 2 => 'nd',
            $day % 10 === 3 => 'rd',
            default => 'th',
        };

        $formatted = $day . $suffix . ' ' . date( 'F Y', $ts );

        if ( '' !== $time ) {
            $time_ts = strtotime( $time );
            if ( false !== $time_ts ) {
                $formatted .= ' at ' . date( 'g:ia', $time_ts );
            }
        }

        return $formatted;
    }

    /**
     * Registers the form JS to run in wp_footer, bypassing the_content filters
     * (wptexturize corrupts inline scripts by converting straight quotes to curly quotes).
     */
    private function maybe_enqueue_form_js( int $race_id, float $price_raw, string $check_email_url ): void {
        if ( self::$script_queued ) {
            return;
        }
        self::$script_queued = true;

        $settings        = get_option( 'duck_race_settings', [] );
        $currency_symbol = (string) ( $settings['currency_symbol'] ?? '£' );

        $storage_key     = 'drb_form_' . $race_id;
        $price_json      = (string) json_encode( $price_raw );
        $key_json        = (string) json_encode( $storage_key );
        $url_json        = (string) json_encode( $check_email_url );
        $symbol_json     = (string) json_encode( $currency_symbol );

        add_action( 'wp_footer', static function () use ( $price_json, $key_json, $url_json, $symbol_json ) {
            ?>
<script>
(function () {
    var form     = document.querySelector(".duck-race-buy-form");
    var countEl  = document.getElementById("drb-duck-count");
    var totalEl  = document.getElementById("drb-total");
    var allRows  = document.querySelectorAll("#drb-entries .drb-entry-row");
    if (!form || !countEl || !totalEl) return;

    var pricePerDuck   = <?php echo $price_json; ?>;
    var STORAGE_KEY    = <?php echo $key_json; ?>;
    var checkEmailUrl  = <?php echo $url_json; ?>;
    var currencySymbol = <?php echo $symbol_json; ?>;

    var donationEl    = document.getElementById("drb-donation");
    var donationBtns  = document.querySelectorAll(".drb-donation-btn");
    var customInput   = document.getElementById("drb-donation-custom");
    var giftAidCb     = document.getElementById("drb-gift-aid");
    var giftAidText   = document.getElementById("drb-gift-aid-text");
    var giftAidNote   = document.getElementById("drb-gift-aid-address-note");

    function getDonation() {
        return parseFloat((donationEl && donationEl.value) || "0") || 0;
    }

    /* ── Row visibility + total ─────────────────────────────── */
    function updateRows(count) {
        count = Math.max(1, Math.min(count, allRows.length || 1));
        for (var i = 0; i < allRows.length; i++) {
            var show = i < count;
            if (show) {
                allRows[i].classList.remove("drb-entry-row--hidden");
            } else {
                allRows[i].classList.add("drb-entry-row--hidden");
            }
            var inputs = allRows[i].querySelectorAll("input");
            for (var j = 0; j < inputs.length; j++) {
                inputs[j].disabled = !show;
            }
        }
        totalEl.textContent = currencySymbol + (count * pricePerDuck + getDonation()).toFixed(2);
    }

    /* ── Donation buttons ────────────────────────────────────── */
    function setActiveDonationBtn(btn) {
        for (var b = 0; b < donationBtns.length; b++) {
            donationBtns[b].classList.remove("drb-donation-btn--active");
        }
        if (btn) btn.classList.add("drb-donation-btn--active");
    }

    function updateTotal() {
        var count = parseInt((countEl && countEl.value) || "1", 10) || 1;
        totalEl.textContent = currencySymbol + (count * pricePerDuck + getDonation()).toFixed(2);
    }

    for (var b = 0; b < donationBtns.length; b++) {
        donationBtns[b].addEventListener("click", (function(btn) {
            return function() {
                var amount = btn.getAttribute("data-amount");
                if (amount === "other") {
                    setActiveDonationBtn(btn);
                    if (customInput) {
                        customInput.style.display = "";
                        customInput.focus();
                        if (donationEl) donationEl.value = customInput.value || "0";
                    }
                } else {
                    setActiveDonationBtn(btn);
                    if (customInput) customInput.style.display = "none";
                    if (donationEl) donationEl.value = amount;
                }
                updateTotal();
                saveState();
            };
        })(donationBtns[b]));
    }

    if (customInput) {
        customInput.addEventListener("input", function() {
            if (donationEl) donationEl.value = customInput.value || "0";
            updateTotal();
            saveState();
        });
    }

    /* ── Gift Aid toggle ─────────────────────────────────────── */
    if (giftAidCb) {
        giftAidCb.addEventListener("change", function() {
            var show = giftAidCb.checked;
            if (giftAidText) giftAidText.style.display = show ? "" : "none";
            if (giftAidNote) giftAidNote.style.display = show ? "" : "none";
        });
    }

    countEl.addEventListener("input",  function () { updateRows(parseInt(countEl.value, 10) || 1); });
    countEl.addEventListener("change", function () { updateRows(parseInt(countEl.value, 10) || 1); });

    /* ── sessionStorage persistence ──────────────────────────── */
    var fieldIds = ["drb-email","drb-first-name","drb-last-name","drb-address1","drb-address2","drb-city","drb-county","drb-postcode","drb-phone","drb-duck-count","drb-donation-custom"];

    function saveState() {
        var state = {};
        for (var k = 0; k < fieldIds.length; k++) {
            var el = document.getElementById(fieldIds[k]);
            if (el) state[fieldIds[k]] = el.value;
        }
        if (donationEl) state["donation_amount"] = donationEl.value;
        var cbs = form.querySelectorAll("[type=checkbox]");
        for (var c = 0; c < cbs.length; c++) {
            if (cbs[c].name) state["cb_" + cbs[c].name] = cbs[c].checked ? "1" : "";
        }
        try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch (e) {}
    }

    function restoreState() {
        var raw = null;
        try { raw = sessionStorage.getItem(STORAGE_KEY); } catch (e) {}
        if (!raw) return;
        var state = null;
        try { state = JSON.parse(raw); } catch (e) { return; }
        for (var k = 0; k < fieldIds.length; k++) {
            var el = document.getElementById(fieldIds[k]);
            if (el && state[fieldIds[k]] !== undefined) el.value = state[fieldIds[k]];
        }
        if (donationEl && state["donation_amount"] !== undefined) {
            donationEl.value = state["donation_amount"];
            // Restore the "Other" custom input if the value doesn't match a preset button.
            var presets = ["0", "5", "10", "15"];
            var savedAmt = state["donation_amount"];
            if (savedAmt && presets.indexOf(savedAmt) === -1) {
                if (customInput) { customInput.style.display = ""; customInput.value = savedAmt; }
                // Mark "Other" button active.
                for (var b2 = 0; b2 < donationBtns.length; b2++) {
                    if (donationBtns[b2].getAttribute("data-amount") === "other") {
                        setActiveDonationBtn(donationBtns[b2]);
                    }
                }
            } else if (savedAmt && savedAmt !== "0") {
                for (var b3 = 0; b3 < donationBtns.length; b3++) {
                    if (donationBtns[b3].getAttribute("data-amount") === savedAmt) {
                        setActiveDonationBtn(donationBtns[b3]);
                    }
                }
            }
        }
        var cbs = form.querySelectorAll("[type=checkbox]");
        for (var c = 0; c < cbs.length; c++) {
            if (cbs[c].name && state["cb_" + cbs[c].name] !== undefined) {
                cbs[c].checked = (state["cb_" + cbs[c].name] === "1");
            }
        }
        // Restore Gift Aid display state.
        if (giftAidCb && giftAidCb.checked) {
            if (giftAidText) giftAidText.style.display = "";
            if (giftAidNote) giftAidNote.style.display = "";
        }
    }

    var inputs = form.querySelectorAll("input, select, textarea");
    for (var n = 0; n < inputs.length; n++) {
        inputs[n].addEventListener("change", saveState);
        inputs[n].addEventListener("input",  saveState);
    }
    form.addEventListener("submit", function () { try { sessionStorage.removeItem(STORAGE_KEY); } catch (e) {} });

    /* ── Contact recognition ─────────────────────────────────── */
    var emailEl = document.getElementById("drb-email");
    var recog   = document.getElementById("drb-recognition");

    if (emailEl && checkEmailUrl) {
        emailEl.addEventListener("blur", function () {
            var email = emailEl.value.trim();
            if (!email) return;
            fetch(checkEmailUrl + "?email=" + encodeURIComponent(email))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data || !data.exists) { if (recog) recog.style.display = "none"; return; }
                    if (recog) {
                        recog.innerHTML = "✓ Welcome back, <strong>" + escHtml(data.first_name || "") + "</strong>! We’ve pre-filled your details — please check and update if needed.";
                        recog.style.display = "block";
                    }
                    var fill = {"drb-first-name":data.first_name,"drb-last-name":data.last_name,"drb-phone":data.phone,"drb-address1":data.address_line_1,"drb-address2":data.address_line_2,"drb-city":data.city,"drb-county":data.county,"drb-postcode":data.postcode};
                    var ids = Object.keys(fill);
                    for (var i = 0; i < ids.length; i++) {
                        var el = document.getElementById(ids[i]);
                        if (el && !el.value && fill[ids[i]]) el.value = fill[ids[i]];
                    }
                    // Pre-check consent boxes from previous record; show imported note.
                    var cdrEl = document.getElementById("drb-consent-duck-race");
                    var coEl  = document.getElementById("drb-consent-organisation");
                    var cnEl  = document.getElementById("drb-consent-imported");
                    if (cdrEl) cdrEl.checked = !!data.consent_duck_race;
                    if (coEl)  coEl.checked  = !!data.consent_organisation;
                    if (cnEl)  cnEl.style.display = "block";
                    saveState();
                })
                .catch(function () {});
        });
    }

    /* ── Initialise ──────────────────────────────────────────── */
    restoreState();
    updateRows(parseInt(countEl.value, 10) || 1);

    function escHtml(s) {
        return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
    }
})();
</script>
            <?php
        }, 20 );
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
