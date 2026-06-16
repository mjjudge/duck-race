<?php

namespace DuckRace\Admin;

use DuckRace\Audit\Logger;
use DuckRace\Database\Schema;
use DuckRace\Security\RequestGuard;
use DuckRace\Services\DuckGridService;

defined( 'ABSPATH' ) || exit;

class DuckGridPage {

    private const NONCE_ACTION = 'duck_race_set_duck_status';

    public function register(): void {
        add_action( 'admin_post_duck_race_set_duck_status', [ $this, 'handle_set_status' ] );
    }

    public function render(): void {
        RequestGuard::require_capability( 'duck_race_manage_entries' );

        $service = new DuckGridService();
        $races = $service->list_races();
        $race_id = (int) ( $_GET['race_id'] ?? ( ! empty( $races ) ? (int) $races[0]->id : 0 ) );
        $filter = sanitize_key( (string) ( $_GET['filter'] ?? 'all' ) );
        $search = max( 0, (int) ( $_GET['duck_number'] ?? 0 ) );
        $page = max( 1, (int) ( $_GET['grid_page'] ?? 1 ) );
        $per_page = max( 50, min( 500, (int) ( $_GET['per_page'] ?? 250 ) ) );

        $data = $service->tiles( $race_id, $filter, $search, $page, $per_page );
        $race = $data['race'] ?? null;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Duck Grid', 'duck-race' ) . '</h1>';

        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Duck status updated.', 'duck-race' ) . '</p></div>';
        }
        if ( isset( $_GET['error'] ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( (string) $_GET['error'] ) ) ) . '</p></div>';
        }

        echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '" style="margin-bottom: 12px;">';
        echo '<input type="hidden" name="page" value="duck-race-duck-grid" />';

        echo '<label for="race_id"><strong>' . esc_html__( 'Race', 'duck-race' ) . '</strong></label> ';
        echo '<select id="race_id" name="race_id">';
        foreach ( $races as $r ) {
            echo '<option value="' . esc_attr( (string) $r->id ) . '" ' . selected( $race_id, (int) $r->id, false ) . '>' . esc_html( (string) $r->title ) . '</option>';
        }
        echo '</select> ';

        echo '<label for="filter"><strong>' . esc_html__( 'Filter', 'duck-race' ) . '</strong></label> ';
        echo '<select id="filter" name="filter">';
        foreach ( [ 'all', 'available', 'sold', 'manual', 'online', 'lost', 'reserved', 'winners' ] as $option ) {
            echo '<option value="' . esc_attr( $option ) . '" ' . selected( $filter, $option, false ) . '>' . esc_html( ucfirst( $option ) ) . '</option>';
        }
        echo '</select> ';

        echo '<label for="duck_number"><strong>' . esc_html__( 'Duck #', 'duck-race' ) . '</strong></label> ';
        echo '<input type="number" min="0" id="duck_number" name="duck_number" value="' . esc_attr( (string) $search ) . '" style="width:100px;" /> ';

        echo '<label for="per_page"><strong>' . esc_html__( 'Ducks/Page', 'duck-race' ) . '</strong></label> ';
        echo '<select id="per_page" name="per_page">';
        foreach ( [ 50, 100, 250, 400, 500 ] as $size ) {
            echo '<option value="' . esc_attr( (string) $size ) . '" ' . selected( $per_page, $size, false ) . '>' . esc_html( (string) $size ) . '</option>';
        }
        echo '</select> ';

        submit_button( __( 'Apply', 'duck-race' ), 'secondary', 'submit', false );
        echo '</form>';

        if ( ! is_object( $race ) ) {
            echo '<p>' . esc_html__( 'No race found.', 'duck-race' ) . '</p></div>';
            return;
        }

        echo '<p>' . sprintf(
            esc_html__( 'Showing %1$d ducks (page %2$d/%3$d).', 'duck-race' ),
            (int) count( $data['tiles'] ),
            (int) $data['page'],
            (int) $data['total_pages']
        ) . '</p>';

        $tile_colours = $this->tile_colours();
        echo '<div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:10px;">';
        $this->legend( $tile_colours['available']['bg'], __( 'Available', 'duck-race' ), $tile_colours['available']['text'] );
        $this->legend( $tile_colours['sold']['bg'], __( 'Sold', 'duck-race' ), $tile_colours['sold']['text'] );
        $this->legend( $tile_colours['lost']['bg'], __( 'Lost', 'duck-race' ), $tile_colours['lost']['text'] );
        $this->legend( $tile_colours['reserved']['bg'], __( 'Reserved', 'duck-race' ), $tile_colours['reserved']['text'] );
        $this->legend( $tile_colours['winner']['bg'], __( 'Winner', 'duck-race' ), $tile_colours['winner']['text'] );
        echo '</div>';

        $this->render_grid_styles( $tile_colours );

        echo '<div id="duck-race-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(64px,1fr));gap:2px;max-width:1200px;">';
        foreach ( $data['tiles'] as $tile ) {
            $tile_class = 'duck-race-tile duck-race-tile--' . esc_attr( $this->tile_status_key( (string) $tile['status'] ) );
            $detail = [
                'duck' => (int) $tile['duck_number'],
                'status' => (string) $tile['status'],
                'purchase_id' => (int) $tile['purchase_id'],
                'payment_status' => (string) $tile['payment_status'],
                'purchase_source' => (string) $tile['purchase_source'],
                'duck_name' => (string) $tile['duck_name'],
                'winner_position' => (int) $tile['winner_position'],
                'prize_label' => (string) $tile['prize_label'],
                'contact_name' => (string) $tile['contact_name'],
                'organisation_name' => (string) $tile['organisation_name'],
                'has_comment' => (bool) $tile['has_comment'],
                'duck_comment' => (string) $tile['duck_comment'],
            ];
            if ( current_user_can( 'duck_race_manage_contacts' ) ) {
                $detail['contact_email'] = (string) $tile['contact_email'];
                $detail['contact_phone'] = (string) $tile['contact_phone'];
            }

            echo '<button type="button" class="' . $tile_class . '" data-detail="' . esc_attr( wp_json_encode( $detail ) ) . '">';
            echo '<span class="duck-race-tile__icon" aria-hidden="true"></span>';
            echo '<span class="duck-race-tile__number">' . esc_html( (string) $tile['duck_number'] ) . '</span>';
            if ( ! empty( $tile['has_comment'] ) ) {
                echo '<span class="duck-race-tile__note" aria-label="' . esc_attr__( 'Has comment', 'duck-race' ) . '" title="' . esc_attr__( 'This duck has a comment', 'duck-race' ) . '">&#9998;</span>';
            }
            echo '</button>';
        }
        echo '</div>';

        $this->render_pagination( (int) $data['page'], (int) $data['total_pages'], $race_id, $filter, $search, $per_page );
        $this->render_detail_modal( $race_id, $filter, $search, (int) $data['page'], $per_page );

        echo '</div>';
    }

    public function handle_set_status(): void {
        RequestGuard::require_capability( 'duck_race_manage_entries' );
        RequestGuard::verify_admin_nonce( self::NONCE_ACTION, '_wpnonce' );

        $race_id = (int) ( $_POST['race_id'] ?? 0 );
        $duck_number = (int) ( $_POST['duck_number'] ?? 0 );
        $operation = sanitize_key( (string) ( $_POST['operation'] ?? '' ) );
        $comment = sanitize_textarea_field( wp_unslash( (string) ( $_POST['duck_comment'] ?? '' ) ) );
        $filter = sanitize_key( (string) ( $_POST['filter'] ?? 'all' ) );
        $search = max( 0, (int) ( $_POST['duck_number_filter'] ?? 0 ) );
        $page = max( 1, (int) ( $_POST['grid_page'] ?? 1 ) );
        $per_page = max( 50, min( 500, (int) ( $_POST['per_page'] ?? 250 ) ) );

        $service = new DuckGridService();
        $race = $service->get_race( $race_id );
        if ( ! is_object( $race ) ) {
            $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, __( 'Invalid race selected.', 'duck-race' ) );
        }

        if ( $duck_number < (int) $race->total_range_start || $duck_number > (int) $race->total_range_end ) {
            $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, __( 'Duck number is out of race range.', 'duck-race' ) );
        }

        global $wpdb;
        $physical_table = Schema::table_name( 'duck_physical_state' );
        $now = current_time( 'mysql', true );
        $user_id = get_current_user_id() ?: null;

        if ( 'mark_lost' === $operation ) {
            $before = [ 'is_lost' => $service->is_lost( $duck_number ) ? 1 : 0 ];

            $wpdb->replace(
                $physical_table,
                [
                    'duck_number' => $duck_number,
                    'is_lost' => 1,
                    'comment' => $comment,
                    'changed_at' => $now,
                    'changed_by' => $user_id,
                ]
            );

            Logger::log(
                'duck.status_changed',
                'duck',
                $duck_number,
                $before,
                [ 'is_lost' => 1, 'comment' => $comment ],
                [ 'race_id' => $race_id ]
            );

            $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, '', true );
        }

        if ( 'restore' === $operation ) {
            $before = [ 'is_lost' => $service->is_lost( $duck_number ) ? 1 : 0 ];

            // Write is_lost = 0 to authoritative physical-state table.
            $wpdb->replace(
                $physical_table,
                [
                    'duck_number' => $duck_number,
                    'is_lost'     => 0,
                    'comment'     => $comment,
                    'changed_at'  => $now,
                    'changed_by'  => $user_id,
                ]
            );

            // Also remove any legacy duck_status row so allocation checks agree with the grid.
            $status_table = Schema::table_name( 'duck_status' );
            $wpdb->delete( $status_table, [ 'duck_number' => $duck_number ] );

            Logger::log(
                'duck.status_changed',
                'duck',
                $duck_number,
                $before,
                [ 'is_lost' => 0, 'comment' => $comment ],
                [ 'race_id' => $race_id ]
            );

            $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, '', true );
        }

        if ( 'save_comment' === $operation ) {
            $is_lost = $service->is_lost( $duck_number ) ? 1 : 0;

            $wpdb->replace(
                $physical_table,
                [
                    'duck_number' => $duck_number,
                    'is_lost' => $is_lost,
                    'comment' => $comment,
                    'changed_at' => $now,
                    'changed_by' => $user_id,
                ]
            );

            $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, '', true );
        }

        if ( 'reassign' === $operation ) {
            $new_duck_number = (int) ( $_POST['new_duck_number'] ?? 0 );

            if ( $new_duck_number <= 0 ) {
                $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, __( 'Please enter a valid target duck number.', 'duck-race' ) );
            }

            if ( $new_duck_number === $duck_number ) {
                $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, __( 'Target duck number is the same as the current number.', 'duck-race' ) );
            }

            if ( $new_duck_number < (int) $race->total_range_start || $new_duck_number > (int) $race->total_range_end ) {
                $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, __( 'Target duck number is outside the race range.', 'duck-race' ) );
            }

            $entries_table = Schema::table_name( 'entries' );

            // Source entry must exist and be in a reassignable state.
            $entry = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$entries_table} WHERE race_id = %d AND duck_number = %d LIMIT 1",
                    $race_id,
                    $duck_number
                )
            );

            if ( ! is_object( $entry ) ) {
                $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, __( 'No entry found for this duck.', 'duck-race' ) );
            }

            $reassignable = [ 'sold_online', 'sold_manual', 'reserved' ];
            if ( ! in_array( (string) $entry->entry_status, $reassignable, true ) ) {
                $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, __( 'This duck cannot be reassigned (winner or voided entries are locked).', 'duck-race' ) );
            }

            // Target must be free (no existing entry in this race).
            $target_taken = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$entries_table} WHERE race_id = %d AND duck_number = %d",
                    $race_id,
                    $new_duck_number
                )
            );

            if ( $target_taken > 0 ) {
                $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, sprintf( __( 'Duck #%d is already taken.', 'duck-race' ), $new_duck_number ) );
            }

            // Target must not be physically lost.
            if ( $service->is_lost( $new_duck_number ) ) {
                $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, sprintf( __( 'Duck #%d is marked as lost and cannot be assigned.', 'duck-race' ), $new_duck_number ) );
            }

            $wpdb->update(
                $entries_table,
                [ 'duck_number' => $new_duck_number, 'updated_at' => $now ],
                [ 'id' => (int) $entry->id ]
            );

            Logger::log(
                'duck.reassigned',
                'duck',
                $duck_number,
                [ 'duck_number' => $duck_number ],
                [ 'duck_number' => $new_duck_number ],
                [
                    'race_id'    => $race_id,
                    'purchase_id' => (int) $entry->purchase_id,
                    'contact_id' => (int) $entry->contact_id,
                ]
            );

            $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, '', true );
        }

        $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, __( 'Unsupported duck status operation.', 'duck-race' ) );
    }

    private function render_pagination( int $page, int $total_pages, int $race_id, string $filter, int $search, int $per_page ): void {
        if ( $total_pages <= 1 ) {
            return;
        }

        echo '<p style="margin-top:12px;">';
        for ( $p = 1; $p <= $total_pages; $p++ ) {
            $url = add_query_arg(
                [
                    'page' => 'duck-race-duck-grid',
                    'race_id' => $race_id,
                    'filter' => $filter,
                    'duck_number' => $search,
                    'grid_page' => $p,
                    'per_page' => $per_page,
                ],
                admin_url( 'admin.php' )
            );

            if ( $p === $page ) {
                echo '<strong style="margin-right:8px;">' . esc_html( (string) $p ) . '</strong>';
            } else {
                echo '<a style="margin-right:8px;" href="' . esc_url( $url ) . '">' . esc_html( (string) $p ) . '</a>';
            }
        }
        echo '</p>';
    }

    private function render_detail_modal( int $race_id, string $filter, int $search, int $page, int $per_page ): void {
        echo '<div id="duck-detail-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:100000;">';
        echo '<div style="background:#fff;max-width:520px;margin:8vh auto;padding:16px;border-radius:8px;overflow-y:auto;max-height:84vh;">';
        echo '<h3 id="duck-detail-title">' . esc_html__( 'Duck Details', 'duck-race' ) . '</h3>';
        echo '<div id="duck-detail-body"></div>';

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:12px;">';
        echo '<input type="hidden" name="action" value="duck_race_set_duck_status" />';
        wp_nonce_field( self::NONCE_ACTION, '_wpnonce' );
        echo '<input type="hidden" name="race_id" value="' . esc_attr( (string) $race_id ) . '" />';
        echo '<input type="hidden" name="duck_number" id="duck-detail-number" value="0" />';
        echo '<input type="hidden" name="filter" value="' . esc_attr( $filter ) . '" />';
        echo '<input type="hidden" name="duck_number_filter" value="' . esc_attr( (string) $search ) . '" />';
        echo '<input type="hidden" name="grid_page" value="' . esc_attr( (string) $page ) . '" />';
        echo '<input type="hidden" name="per_page" value="' . esc_attr( (string) $per_page ) . '" />';

        echo '<p><label for="duck-detail-comment"><strong>' . esc_html__( 'Comments', 'duck-race' ) . '</strong></label><br />';
        echo '<textarea class="large-text" rows="3" id="duck-detail-comment" name="duck_comment" placeholder="' . esc_attr__( 'Notes about this duck\'s condition, e.g. damaged, needs replacing', 'duck-race' ) . '"></textarea></p>';

        echo '<p id="duck-detail-actions">';
        echo '<button type="submit" class="button" id="duck-btn-lost" name="operation" value="mark_lost">' . esc_html__( 'Lost Duck', 'duck-race' ) . '</button> ';
        echo '<button type="submit" class="button" id="duck-btn-found" name="operation" value="restore">' . esc_html__( 'Duck Found', 'duck-race' ) . '</button> ';
        echo '<button type="submit" class="button button-primary" id="duck-btn-comment" name="operation" value="save_comment">' . esc_html__( 'Save Comment', 'duck-race' ) . '</button> ';
        echo '<button type="button" class="button button-secondary" id="duck-detail-close">' . esc_html__( 'Close', 'duck-race' ) . '</button>';
        echo '</p>';

        echo '<div id="duck-reassign-section" style="display:none;margin-top:14px;padding-top:14px;border-top:2px solid #ddd;">';
        echo '<h4 style="margin:0 0 10px;">' . esc_html__( 'Reassign Duck Number', 'duck-race' ) . '</h4>';
        echo '<div style="background:#fff3cd;border-left:4px solid #d97706;padding:10px 14px;margin-bottom:12px;font-size:0.88em;border-radius:0 4px 4px 0;">';
        echo '<strong>' . esc_html__( '⚠ Permission required', 'duck-race' ) . '</strong><br />';
        echo esc_html__( 'Only reassign a duck number with the buyer\'s knowledge and agreement. Their record is updated immediately to the new number. You must notify them separately — no email is sent automatically.', 'duck-race' );
        echo '</div>';
        echo '<p><label for="duck-reassign-target"><strong>' . esc_html__( 'Move to duck number:', 'duck-race' ) . '</strong></label><br />';
        echo '<input type="number" name="new_duck_number" id="duck-reassign-target" min="1" style="width:110px;margin-top:6px;" placeholder="' . esc_attr__( 'e.g. 123', 'duck-race' ) . '" /></p>';
        $reassign_confirm = __( "Reassign this duck to the new number?\n\nOnly proceed if you have the buyer's permission. The change is immediate. Use Manual Sales afterwards to assign the freed number to its correct owner.\n\nProceed?", 'duck-race' );
        echo '<p>';
        echo '<button type="submit" class="button button-warning" name="operation" value="reassign" style="background:#d97706;color:#fff;border-color:#b45309;" onclick="return confirm(' . esc_attr( wp_json_encode( $reassign_confirm ) ) . ');">';
        echo esc_html__( 'Reassign Duck', 'duck-race' );
        echo '</button>';
        echo '</p>';
        echo '<p class="description">' . esc_html__( 'The original number is freed once reassigned.', 'duck-race' ) . '</p>';
        echo '</div>';

        echo '</form>';

        echo '</div>';
        echo '</div>';

        echo '<script>';
        echo '(function(){';
        echo 'var manualSaleBase=' . wp_json_encode( admin_url( 'admin.php?page=duck-race-manual-sale' ) ) . ';';
        echo 'var gridRaceId=' . wp_json_encode( $race_id ) . ';';
        echo 'const modal=document.getElementById("duck-detail-modal");';
        echo 'const body=document.getElementById("duck-detail-body");';
        echo 'const numberInput=document.getElementById("duck-detail-number");';
        echo 'const commentInput=document.getElementById("duck-detail-comment");';
        echo 'const closeBtn=document.getElementById("duck-detail-close");';
        echo 'const btnLost=document.getElementById("duck-btn-lost");';
        echo 'const btnFound=document.getElementById("duck-btn-found");';
        echo 'const reassignSection=document.getElementById("duck-reassign-section");';
        echo 'const reassignTarget=document.getElementById("duck-reassign-target");';

        echo 'document.querySelectorAll(".duck-race-tile").forEach(function(tile){';
        echo 'tile.addEventListener("click",function(){';
        echo 'const detail=JSON.parse(tile.getAttribute("data-detail")||"{}");';
        echo 'numberInput.value=detail.duck||0;';
        echo 'commentInput.value=detail.duck_comment||"";';

        // Human-readable status labels.
        echo 'var statusLabels={"available":"Available","sold_online":"Sold (online)","sold_manual":"Sold (in person)","reserved":"Reserved — awaiting payment","lost":"Lost","winner":"Winner"};';

        echo 'let html="";';
        echo 'html+="<p><strong>Duck #:</strong> "+(detail.duck||"")+"</p>";';
        echo 'var statusLabel=statusLabels[detail.status]||detail.status;';
        echo 'html+="<p><strong>Status:</strong> "+statusLabel+"</p>";';
        echo 'if(detail.duck_name){html+="<p><strong>Name for duck:</strong> "+detail.duck_name+"</p>";}';
        echo 'if(detail.contact_name||detail.organisation_name){html+="<p><strong>Buyer:</strong> "+(detail.organisation_name||detail.contact_name)+"</p>";}';
        echo 'if(detail.contact_email){html+="<p><strong>Email:</strong> "+detail.contact_email+"</p>";}';
        echo 'if(detail.contact_phone){html+="<p><strong>Phone:</strong> "+detail.contact_phone+"</p>";}';
        echo 'if(detail.purchase_id){html+="<p><strong>Purchase:</strong> #"+detail.purchase_id+" ("+(detail.payment_status||"")+", "+(detail.purchase_source||"")+")</p>";}';
        echo 'if(detail.winner_position){';
        echo 'let wp="<p><strong>Winner:</strong> Position "+detail.winner_position;';
        echo 'if(detail.prize_label){wp+=" &mdash; "+detail.prize_label;}';
        echo 'wp+="</p>";html+=wp;';
        echo '}';
        echo 'if(detail.status==="available"){';
        echo 'var sellUrl=manualSaleBase+"&duck_number="+detail.duck+"&race_id="+gridRaceId;';
        echo 'html+="<p><a href=\""+sellUrl+"\" class=\"button button-small\">Mark as Sold (manual sale)</a></p>";';
        echo '}';
        echo 'if(detail.status==="reserved"&&detail.payment_status==="pending"){';
        echo 'var reportingUrl=' . wp_json_encode( admin_url( 'admin.php?page=duck-race-reporting' ) ) . '+"&race_id="+gridRaceId;';
        echo 'html+="<p style=\"background:#fff3cd;padding:8px;border-left:4px solid #c00;\"><strong>Payment pending.</strong> If Stripe collected payment, go to <a href=\""+reportingUrl+"\">Reporting</a> and use <em>Mark as Paid</em> to rescue this purchase.</p>";';
        echo '}';
        echo 'body.innerHTML=html;';

        // Show buttons relevant to the duck status.
        // isReassignable: fix — raw status values are "sold_online" / "sold_manual", NOT "sold".
        echo 'const isLost=(detail.status==="lost");';
        echo 'const isAvailable=(detail.status==="available");';
        echo 'const isReassignable=(detail.status==="sold_online"||detail.status==="sold_manual"||detail.status==="reserved");';
        echo 'btnLost.style.display=isAvailable?"":"none";';
        echo 'btnFound.style.display=isLost?"":"none";';
        echo 'reassignSection.style.display=isReassignable?"":"none";';
        echo 'if(isReassignable){reassignTarget.value="";}';

        echo 'modal.style.display="block";';
        echo '});';
        echo '});';

        echo 'closeBtn.addEventListener("click",function(){modal.style.display="none";});';
        echo 'modal.addEventListener("click",function(e){if(e.target===modal){modal.style.display="none";}});';
        echo '})();';
        echo '</script>';
    }

    private function tile_status_key( string $status ): string {
        return match ( $status ) {
            'available' => 'available',
            'sold_online', 'sold_manual' => 'sold',
            'lost' => 'lost',
            'reserved' => 'reserved',
            'winner' => 'winner',
            default => 'default',
        };
    }

    /**
     * @param array<string, array{bg: string, text: string}> $colours
     */
    private function render_grid_styles( array $colours ): void {
        $duck_icon_url = esc_url( DUCK_RACE_PLUGIN_URL . 'assets/images/lostduck.svg' );

        echo '<style>';
        echo '.duck-race-tile{position:relative;border:0;background:transparent;padding:0;cursor:pointer;display:flex;align-items:center;justify-content:center;min-height:64px;}';
        echo '.duck-race-tile:focus-visible{outline:2px solid #1d4ed8;outline-offset:2px;border-radius:4px;}';
        echo '.duck-race-tile__icon{width:62px;height:62px;background-color:var(--duck-color,#f0f0f0);-webkit-mask-image:url("' . $duck_icon_url . '");mask-image:url("' . $duck_icon_url . '");-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;filter:var(--duck-shadow,none);}';
        echo '.duck-race-tile__number{position:absolute;top:66%;left:50%;transform:translate(-50%,-50%);line-height:1;font-size:16px;font-weight:800;color:var(--duck-number-color,#222);text-shadow:0 0 1px rgba(255,255,255,.4);z-index:2;pointer-events:none;}';
        echo '.duck-race-tile__note{position:absolute;bottom:2px;right:4px;font-size:12px;line-height:1;color:var(--duck-number-color,#222);z-index:3;pointer-events:none;opacity:.75;}';

        $c = $colours;
        echo '.duck-race-tile--available{--duck-color:' . esc_attr( $c['available']['bg'] ) . ';--duck-number-color:' . esc_attr( $c['available']['text'] ) . ';}';
        echo '.duck-race-tile--sold{--duck-color:' . esc_attr( $c['sold']['bg'] ) . ';--duck-number-color:' . esc_attr( $c['sold']['text'] ) . ';--duck-shadow:drop-shadow(0 0 0 #111) drop-shadow(1px 0 0 #111) drop-shadow(-1px 0 0 #111) drop-shadow(0 1px 0 #111) drop-shadow(0 -1px 0 #111);}';
        echo '.duck-race-tile--lost{--duck-color:' . esc_attr( $c['lost']['bg'] ) . ';--duck-number-color:' . esc_attr( $c['lost']['text'] ) . ';}';
        echo '.duck-race-tile--reserved{--duck-color:' . esc_attr( $c['reserved']['bg'] ) . ';--duck-number-color:' . esc_attr( $c['reserved']['text'] ) . ';}';
        echo '.duck-race-tile--winner{--duck-color:' . esc_attr( $c['winner']['bg'] ) . ';--duck-number-color:' . esc_attr( $c['winner']['text'] ) . ';--duck-shadow:drop-shadow(0 0 0 #111) drop-shadow(1px 0 0 #111) drop-shadow(-1px 0 0 #111) drop-shadow(0 1px 0 #111) drop-shadow(0 -1px 0 #111);}';
        echo '.duck-race-tile--default{--duck-color:#f0f0f0;--duck-number-color:#222;}';
        echo '</style>';
    }

    /**
     * @return array<string, array{bg: string, text: string}>
     */
    private function tile_colours(): array {
        $settings = get_option( 'duck_race_settings', [] );
        $saved = is_array( $settings['tile_colors'] ?? null ) ? (array) $settings['tile_colors'] : [];
        $colours = [
            'available' => [ 'bg' => '#f5ef9a', 'text' => '#222222' ],
            'sold'      => [ 'bg' => '#dfbe00', 'text' => '#222222' ],
            'lost'      => [ 'bg' => '#2f2f2f', 'text' => '#ffffff' ],
            'reserved'  => [ 'bg' => '#b8c1cc', 'text' => '#222222' ],
            'winner'    => [ 'bg' => '#c25a00', 'text' => '#ffffff' ],
        ];
        foreach ( array_keys( $colours ) as $state ) {
            if ( ! empty( $saved[ $state ]['bg'] ) ) {
                $colours[ $state ]['bg'] = (string) $saved[ $state ]['bg'];
            }
            if ( ! empty( $saved[ $state ]['text'] ) ) {
                $colours[ $state ]['text'] = (string) $saved[ $state ]['text'];
            }
        }
        return $colours;
    }

    private function legend( string $bg, string $label, string $text = '#222' ): void {
        echo '<span style="display:inline-flex;align-items:center;gap:6px;">';
        echo '<span style="display:inline-block;width:18px;height:18px;border:1px solid #666;background:' . esc_attr( $bg ) . ';color:' . esc_attr( $text ) . ';"></span>';
        echo '<span>' . esc_html( $label ) . '</span>';
        echo '</span>';
    }

    private function redirect_grid( int $race_id, string $filter, int $search, int $page, int $per_page, string $error = '', bool $updated = false ): void {
        $args = [
            'page' => 'duck-race-duck-grid',
            'race_id' => $race_id,
            'filter' => $filter,
            'duck_number' => $search,
            'grid_page' => $page,
            'per_page' => $per_page,
        ];

        if ( '' !== $error ) {
            $args['error'] = rawurlencode( $error );
        }

        if ( $updated ) {
            $args['updated'] = '1';
        }

        wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
        exit;
    }
}
