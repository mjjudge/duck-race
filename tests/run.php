<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Unit/AllocationServiceTest.php';
require_once __DIR__ . '/Unit/StripeWebhookProcessorTest.php';

$tests = [
    'AllocationServiceTest' => [ \DuckRace\Tests\Unit\AllocationServiceTest::class, 'run' ],
    'StripeWebhookProcessorTest' => [ \DuckRace\Tests\Unit\StripeWebhookProcessorTest::class, 'run' ],
];

$failed = 0;
foreach ( $tests as $name => $callable ) {
    try {
        $callable();
        echo "[PASS] {$name}\n";
    } catch ( \Throwable $e ) {
        $failed++;
        echo "[FAIL] {$name}: {$e->getMessage()}\n";
    }
}

exit( $failed > 0 ? 1 : 0 );
