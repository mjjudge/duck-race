<?php

namespace DuckRace\Core;

defined( 'ABSPATH' ) || exit;

class Roles {

    /**
     * Capabilities held by each plugin role.
     *
     * @var array<string, array<string, bool>>
     */
    private const ROLE_CAPS = [
        'duck_race_manager' => [
            'read' => true,
            'duck_race_access' => true,
            'duck_race_manage_races' => true,
            'duck_race_manage_sales' => true,
            'duck_race_manage_entries' => true,
            'duck_race_manage_contacts' => true,
            'duck_race_manage_winners' => true,
            'duck_race_manage_settings' => false,
            'duck_race_process_refunds' => false,
        ],
        'duck_race_settings_admin' => [
            'read' => true,
            'duck_race_access' => true,
            'duck_race_manage_races' => true,
            'duck_race_manage_sales' => true,
            'duck_race_manage_entries' => true,
            'duck_race_manage_contacts' => true,
            'duck_race_manage_winners' => true,
            'duck_race_manage_settings' => true,
            'duck_race_process_refunds' => true,
        ],
    ];

    private const ALL_CAPS = [
        'duck_race_access',
        'duck_race_manage_races',
        'duck_race_manage_sales',
        'duck_race_manage_entries',
        'duck_race_manage_contacts',
        'duck_race_manage_winners',
        'duck_race_manage_settings',
        'duck_race_process_refunds',
    ];

    /**
     * Register runtime hooks. Must be called on every page load (not just activation).
     * Grants duck_race_manager capabilities to WordPress Editors dynamically, without
     * permanently modifying the editor role in the database.
     */
    public static function boot(): void {
        add_filter(
            'user_has_cap',
            static function ( array $allcaps, array $caps, array $args, \WP_User $user ): array {
                if ( ! in_array( 'editor', $user->roles, true ) ) {
                    return $allcaps;
                }
                foreach ( self::ROLE_CAPS['duck_race_manager'] as $cap => $granted ) {
                    if ( $granted ) {
                        $allcaps[ $cap ] = true;
                    }
                }
                return $allcaps;
            },
            10,
            4
        );
    }

    public static function register(): void {
        foreach ( self::ROLE_CAPS as $role => $caps ) {
            $display_name = match ( $role ) {
                'duck_race_manager' => __( 'Duck Race Manager', 'duck-race' ),
                'duck_race_settings_admin' => __( 'Duck Race Settings Admin', 'duck-race' ),
                default => $role,
            };

            // add_role() is a no-op when the role already exists.
            add_role( $role, $display_name, $caps );

            // Keep existing roles in sync if capabilities evolve.
            $role_obj = get_role( $role );
            if ( $role_obj ) {
                foreach ( self::ALL_CAPS as $cap ) {
                    if ( ! empty( $caps[ $cap ] ) ) {
                        $role_obj->add_cap( $cap );
                    } else {
                        $role_obj->remove_cap( $cap );
                    }
                }
            }
        }

        $admin = get_role( 'administrator' );
        if ( $admin ) {
            foreach ( self::ALL_CAPS as $cap ) {
                $admin->add_cap( $cap );
            }
        }
    }

    /**
     * Reserved for uninstall flow. Not called during deactivation.
     */
    public static function remove(): void {
        foreach ( array_keys( self::ROLE_CAPS ) as $role ) {
            remove_role( $role );
        }

        $admin = get_role( 'administrator' );
        if ( $admin ) {
            foreach ( self::ALL_CAPS as $cap ) {
                $admin->remove_cap( $cap );
            }
        }
    }
}
