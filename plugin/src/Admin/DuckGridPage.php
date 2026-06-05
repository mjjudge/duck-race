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
        $per_page = max( 100, min( 400, (int) ( $_GET['per_page'] ?? 250 ) ) );

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

        echo '<label for="per_page"><strong>' . esc_html__( 'Tiles/Page', 'duck-race' ) . '</strong></label> ';
        echo '<select id="per_page" name="per_page">';
        foreach ( [ 100, 250, 400 ] as $size ) {
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

        echo '<div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:10px;">';
        $this->legend( '#f5ef9a', __( 'Available', 'duck-race' ) );
        $this->legend( '#ffe04f', __( 'Sold', 'duck-race' ) );
        $this->legend( '#2f2f2f', __( 'Lost', 'duck-race' ), '#fff' );
        $this->legend( '#b8c1cc', __( 'Reserved', 'duck-race' ) );
        $this->legend( '#f6c744', __( 'Winner', 'duck-race' ) );
        echo '</div>';

        echo '<div id="duck-race-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(64px,1fr));gap:8px;max-width:1200px;">';
        foreach ( $data['tiles'] as $tile ) {
            $style = $this->tile_style( (string) $tile['status'] );
            $detail = [
                'duck' => (int) $tile['duck_number'],
                'status' => (string) $tile['status'],
                'purchase_id' => (int) $tile['purchase_id'],
                'payment_status' => (string) $tile['payment_status'],
                'purchase_source' => (string) $tile['purchase_source'],
                'duck_name' => (string) $tile['duck_name'],
                'winner_position' => (int) $tile['winner_position'],
                'contact_name' => (string) $tile['contact_name'],
                'organisation_name' => (string) $tile['organisation_name'],
            ];
            if ( current_user_can( 'duck_race_manage_contacts' ) ) {
                $detail['contact_email'] = (string) $tile['contact_email'];
                $detail['contact_phone'] = (string) $tile['contact_phone'];
            }

            echo '<button type="button" class="duck-race-tile" data-detail="' . esc_attr( wp_json_encode( $detail ) ) . '"';
            echo ' style="border:1px solid #666;border-radius:6px;padding:10px 6px;font-weight:700;cursor:pointer;' . esc_attr( $style ) . '">';
            echo esc_html( (string) $tile['duck_number'] );
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
        $filter = sanitize_key( (string) ( $_POST['filter'] ?? 'all' ) );
        $search = max( 0, (int) ( $_POST['duck_number_filter'] ?? 0 ) );
        $page = max( 1, (int) ( $_POST['grid_page'] ?? 1 ) );
        $per_page = max( 100, min( 400, (int) ( $_POST['per_page'] ?? 250 ) ) );

        $service = new DuckGridService();
        $race = $service->get_race( $race_id );
        if ( ! is_object( $race ) ) {
            $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, __( 'Invalid race selected.', 'duck-race' ) );
        }

        if ( $duck_number < (int) $race->total_range_start || $duck_number > (int) $race->total_range_end ) {
            $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, __( 'Duck number is out of race range.', 'duck-race' ) );
        }

        global $wpdb;
        $status_table = Schema::table_name( 'duck_status' );

        if ( 'mark_lost' === $operation ) {
            if ( ! $service->can_mark_lost( $race_id, $duck_number ) ) {
                $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, __( 'Only available ducks can be marked lost.', 'duck-race' ) );
            }

            $before = [ 'status' => $service->is_lost( $race_id, $duck_number ) ? 'lost' : 'available' ];
            $now = current_time( 'mysql', true );
            $wpdb->replace(
                $status_table,
                [
                    'race_id' => $race_id,
                    'duck_number' => $duck_number,
                    'status' => 'lost',
                    'reason' => sanitize_text_field( (string) ( $_POST['reason'] ?? '' ) ),
                    'changed_at' => $now,
                    'changed_by' => get_current_user_id() ?: null,
                ]
            );

            Logger::log(
                'duck.status_changed',
                'duck',
                $duck_number,
                $before,
                [ 'status' => 'lost' ],
                [ 'race_id' => $race_id ]
            );

            $this->redirect_grid( $race_id, $filter, $search, $page, $per_page, '', true );
        }

        if ( 'restore' === $operation ) {
            $before = [ 'status' => $service->is_lost( $race_id, $duck_number ) ? 'lost' : 'available' ];
            $wpdb->delete( $status_table, [ 'race_id' => $race_id, 'duck_number' => $duck_number ] );

            Logger::log(
                'duck.status_changed',
                'duck',
                $duck_number,
                $before,
                [ 'status' => 'available' ],
                [ 'race_id' => $race_id ]
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
        echo '<div style="background:#fff;max-width:520px;margin:8vh auto;padding:16px;border-radius:8px;">';
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
        echo '<p><label for="duck-detail-reason">' . esc_html__( 'Reason (optional)', 'duck-race' ) . '</label><br />';
        echo '<input class="regular-text" type="text" id="duck-detail-reason" name="reason" /></p>';
        echo '<p>';
        echo '<button type="submit" class="button" name="operation" value="mark_lost">' . esc_html__( 'Mark Lost', 'duck-race' ) . '</button> ';
        echo '<button type="submit" class="button" name="operation" value="restore">' . esc_html__( 'Restore', 'duck-race' ) . '</button> ';
        echo '<button type="button" class="button button-secondary" id="duck-detail-close">' . esc_html__( 'Close', 'duck-race' ) . '</button>';
        echo '</p>';
        echo '</form>';

        echo '</div>';
        echo '</div>';

        echo '<script>';
        echo '(function(){';
        echo 'const modal=document.getElementById("duck-detail-modal");';
        echo 'const body=document.getElementById("duck-detail-body");';
        echo 'const numberInput=document.getElementById("duck-detail-number");';
        echo 'const closeBtn=document.getElementById("duck-detail-close");';
        echo 'document.querySelectorAll(".duck-race-tile").forEach(function(tile){';
        echo 'tile.addEventListener("click",function(){';
        echo 'const detail=JSON.parse(tile.getAttribute("data-detail")||"{}");';
        echo 'numberInput.value=detail.duck||0;';
        echo 'let html="";';
        echo 'html+="<p><strong>Duck #:</strong> "+(detail.duck||"")+"</p>";';
        echo 'html+="<p><strong>Status:</strong> "+(detail.status||"")+"</p>";';
        echo 'if(detail.duck_name){html+="<p><strong>Duck Name:</strong> "+detail.duck_name+"</p>";}';
        echo 'if(detail.purchase_id){html+="<p><strong>Purchase:</strong> #"+detail.purchase_id+" ("+(detail.payment_status||"")+", "+(detail.purchase_source||"")+")</p>";}';
        echo 'if(detail.winner_position){html+="<p><strong>Winner Position:</strong> "+detail.winner_position+"</p>";}';
        echo 'if(detail.contact_name||detail.organisation_name){html+="<p><strong>Buyer:</strong> "+(detail.organisation_name||detail.contact_name)+"</p>";}';
        echo 'if(detail.contact_email){html+="<p><strong>Email:</strong> "+detail.contact_email+"</p>";}';
        echo 'if(detail.contact_phone){html+="<p><strong>Phone:</strong> "+detail.contact_phone+"</p>";}';
        echo 'body.innerHTML=html;';
        echo 'modal.style.display="block";';
        echo '});';
        echo '});';
        echo 'closeBtn.addEventListener("click",function(){modal.style.display="none";});';
        echo 'modal.addEventListener("click",function(e){if(e.target===modal){modal.style.display="none";}});';
        echo '})();';
        echo '</script>';
    }

    private function tile_style( string $status ): string {
        return match ( $status ) {
            'available' => 'background:#f5ef9a;color:#222;',
            'sold_online', 'sold_manual' => 'background:#ffe04f;color:#222;',
            'lost' => 'background:#2f2f2f;color:#fff;',
            'reserved' => 'background:#b8c1cc;color:#1c1c1c;',
            'winner' => 'background:#f6c744;color:#222;',
            default => 'background:#f0f0f0;color:#222;',
        };
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
