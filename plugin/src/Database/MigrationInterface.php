<?php

namespace DuckRace\Database;

defined( 'ABSPATH' ) || exit;

interface MigrationInterface {

    public function up(): void;
}
