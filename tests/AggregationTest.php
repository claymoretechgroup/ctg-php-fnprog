<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CTG\Test\CTGTest;
use CTG\FnProg\CTGFnprog;

// Tests for CTGFnprog aggregation methods: sum, avg, min, max, count, reduce, first, last

$config = ['output' => 'console'];

$orders = [
    ['id' => 1, 'product' => 'Widget', 'total' => 25.00, 'status' => 'completed'],
    ['id' => 2, 'product' => 'Gadget', 'total' => 75.50, 'status' => 'completed'],
    ['id' => 3, 'product' => 'Doohickey', 'total' => 150.00, 'status' => 'pending'],
    ['id' => 4, 'product' => 'Thingamajig', 'total' => 12.99, 'status' => 'completed'],
    ['id' => 5, 'product' => 'Whatsit', 'total' => 200.00, 'status' => 'cancelled'],
];

$GLOBALS['orders'] = $orders;

// ── sum ─────────────────────────────────────────────────────────

CTGTest::init('sum — sums a numeric field')
    ->stage('execute', fn($_) => CTGFnprog::sum('total')($GLOBALS['orders']))
    ->assert('total is 463.49', fn($r) => $r, 463.49)
    ->start(null, $config);

CTGTest::init('sum — empty array returns 0')
    ->stage('execute', fn($_) => CTGFnprog::sum('total')([]))
    ->assert('returns 0', fn($r) => $r, 0)
    ->start(null, $config);

// ── avg ─────────────────────────────────────────────────────────

CTGTest::init('avg — averages a numeric field')
    ->stage('execute', fn($_) => CTGFnprog::avg('total')($GLOBALS['orders']))
    ->assert('average is 92.698', fn($r) => round($r, 3), 92.698)
    ->start(null, $config);

CTGTest::init('avg — empty array returns 0')
    ->stage('execute', fn($_) => CTGFnprog::avg('total')([]))
    ->assert('returns 0', fn($r) => $r, 0)
    ->start(null, $config);

// ── min ─────────────────────────────────────────────────────────

CTGTest::init('min — finds minimum value')
    ->stage('execute', fn($_) => CTGFnprog::min('total')($GLOBALS['orders']))
    ->assert('min is 12.99', fn($r) => $r, 12.99)
    ->start(null, $config);

CTGTest::init('min — empty array returns null')
    ->stage('execute', fn($_) => CTGFnprog::min('total')([]))
    ->assert('returns null', fn($r) => $r, null)
    ->start(null, $config);

// ── max ─────────────────────────────────────────────────────────

CTGTest::init('max — finds maximum value')
    ->stage('execute', fn($_) => CTGFnprog::max('total')($GLOBALS['orders']))
    ->assert('max is 200.00', fn($r) => $r, 200.00)
    ->start(null, $config);

CTGTest::init('max — empty array returns null')
    ->stage('execute', fn($_) => CTGFnprog::max('total')([]))
    ->assert('returns null', fn($r) => $r, null)
    ->start(null, $config);

// ── count ───────────────────────────────────────────────────────

CTGTest::init('count — counts elements')
    ->stage('execute', fn($_) => CTGFnprog::count()($GLOBALS['orders']))
    ->assert('count is 5', fn($r) => $r, 5)
    ->start(null, $config);

CTGTest::init('count — empty array')
    ->stage('execute', fn($_) => CTGFnprog::count()([]))
    ->assert('count is 0', fn($r) => $r, 0)
    ->start(null, $config);

// ── reduce ──────────────────────────────────────────────────────

CTGTest::init('reduce — accumulates values')
    ->stage('execute', fn($_) => CTGFnprog::reduce(
        fn($acc, $row) => $acc + $row['total'],
        0
    )($GLOBALS['orders']))
    ->assert('same as sum', fn($r) => $r, 463.49)
    ->start(null, $config);

CTGTest::init('reduce — builds summary object')
    ->stage('execute', fn($_) => CTGFnprog::reduce(
        function($acc, $row) {
            $acc['count']++;
            $acc['total'] += $row['total'];
            return $acc;
        },
        ['count' => 0, 'total' => 0]
    )($GLOBALS['orders']))
    ->assert('count is 5', fn($r) => $r['count'], 5)
    ->assert('total is 463.49', fn($r) => $r['total'], 463.49)
    ->start(null, $config);

CTGTest::init('reduce — empty array returns initial')
    ->stage('execute', fn($_) => CTGFnprog::reduce(fn($acc, $row) => $acc + 1, 0)([]))
    ->assert('returns 0', fn($r) => $r, 0)
    ->start(null, $config);

CTGTest::init('reduce — string concatenation')
    ->stage('execute', fn($_) => CTGFnprog::reduce(
        fn($acc, $row) => $acc . $row['product'] . ', ',
        ''
    )($GLOBALS['orders']))
    ->assert('concatenated', fn($r) => rtrim($r, ', '), 'Widget, Gadget, Doohickey, Thingamajig, Whatsit')
    ->start(null, $config);

// ── first ───────────────────────────────────────────────────────

CTGTest::init('first — returns first element')
    ->stage('execute', fn($_) => CTGFnprog::first()($GLOBALS['orders']))
    ->assert('is Widget', fn($r) => $r['product'], 'Widget')
    ->start(null, $config);

CTGTest::init('first — with predicate')
    ->stage('execute', fn($_) => CTGFnprog::first(fn($r) => $r['total'] > 100)($GLOBALS['orders']))
    ->assert('is Doohickey', fn($r) => $r['product'], 'Doohickey')
    ->start(null, $config);

CTGTest::init('first — no match returns null')
    ->stage('execute', fn($_) => CTGFnprog::first(fn($r) => $r['total'] > 9999)($GLOBALS['orders']))
    ->assert('returns null', fn($r) => $r, null)
    ->start(null, $config);

CTGTest::init('first — empty array returns null')
    ->stage('execute', fn($_) => CTGFnprog::first()([]))
    ->assert('returns null', fn($r) => $r, null)
    ->start(null, $config);

// ── last ────────────────────────────────────────────────────────

CTGTest::init('last — returns last element')
    ->stage('execute', fn($_) => CTGFnprog::last()($GLOBALS['orders']))
    ->assert('is Whatsit', fn($r) => $r['product'], 'Whatsit')
    ->start(null, $config);

CTGTest::init('last — with predicate')
    ->stage('execute', fn($_) => CTGFnprog::last(fn($r) => $r['status'] === 'completed')($GLOBALS['orders']))
    ->assert('is Thingamajig', fn($r) => $r['product'], 'Thingamajig')
    ->start(null, $config);

CTGTest::init('last — no match returns null')
    ->stage('execute', fn($_) => CTGFnprog::last(fn($r) => $r['total'] > 9999)($GLOBALS['orders']))
    ->assert('returns null', fn($r) => $r, null)
    ->start(null, $config);

CTGTest::init('last — empty array returns null')
    ->stage('execute', fn($_) => CTGFnprog::last()([]))
    ->assert('returns null', fn($r) => $r, null)
    ->start(null, $config);

// ── pipeline integration ────────────────────────────────────────

CTGTest::init('aggregation in pipeline — filter then sum')
    ->stage('execute', fn($_) => CTGFnprog::pipe([
        CTGFnprog::where('status', 'completed'),
        CTGFnprog::sum('total'),
    ])($GLOBALS['orders']))
    ->assert('sum of completed orders', fn($r) => $r, 113.49)
    ->start(null, $config);

CTGTest::init('aggregation in pipeline — multi-stat')
    ->stage('execute', fn($_) => CTGFnprog::pipe([
        CTGFnprog::where('status', 'completed'),
        fn($orders) => [
            'count' => CTGFnprog::count()($orders),
            'total' => CTGFnprog::sum('total')($orders),
            'avg'   => round(CTGFnprog::avg('total')($orders), 2),
        ],
    ])($GLOBALS['orders']))
    ->assert('count', fn($r) => $r['count'], 3)
    ->assert('total', fn($r) => $r['total'], 113.49)
    ->assert('avg', fn($r) => $r['avg'], 37.83)
    ->start(null, $config);
