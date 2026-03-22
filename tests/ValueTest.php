<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CTG\Test\CTGTest;
use CTG\FnProg\CTGFnprog;

// Tests for CTGFnprog value methods: identity, always, tap, defaultTo, coalesce

$config = ['output' => 'console'];

// ── identity ────────────────────────────────────────────────────

CTGTest::init('identity — returns scalar unchanged')
    ->stage('execute', fn($_) => CTGFnprog::identity()(42))
    ->assert('returns 42', fn($r) => $r, 42)
    ->start(null, $config);

CTGTest::init('identity — returns string unchanged')
    ->stage('execute', fn($_) => CTGFnprog::identity()('hello'))
    ->assert('returns hello', fn($r) => $r, 'hello')
    ->start(null, $config);

CTGTest::init('identity — returns array unchanged')
    ->stage('data', fn($_) => ['a' => 1, 'b' => 2])
    ->stage('execute', fn($data) => CTGFnprog::identity()($data))
    ->assert('returns same array', fn($r) => $r, ['a' => 1, 'b' => 2])
    ->start(null, $config);

CTGTest::init('identity — returns null unchanged')
    ->stage('execute', fn($_) => CTGFnprog::identity()(null))
    ->assert('returns null', fn($r) => $r, null)
    ->start(null, $config);

CTGTest::init('identity — usable in pipe as passthrough')
    ->stage('execute', fn($_) => CTGFnprog::pipe([
        CTGFnprog::identity(),
        fn($x) => $x * 2,
    ])(5))
    ->assert('passes through then doubles', fn($r) => $r, 10)
    ->start(null, $config);

// ── always ──────────────────────────────────────────────────────

CTGTest::init('always — returns fixed value regardless of input')
    ->stage('build', fn($_) => CTGFnprog::always('default'))
    ->assert('ignores string input', fn($fn) => $fn('anything'), 'default')
    ->assert('ignores number input', fn($fn) => $fn(999), 'default')
    ->assert('ignores null input', fn($fn) => $fn(null), 'default')
    ->start(null, $config);

CTGTest::init('always — returns fixed array')
    ->stage('build', fn($_) => CTGFnprog::always(['x' => 1]))
    ->assert('returns array', fn($fn) => $fn('ignored'), ['x' => 1])
    ->start(null, $config);

CTGTest::init('always — returns null')
    ->stage('build', fn($_) => CTGFnprog::always(null))
    ->assert('returns null', fn($fn) => $fn('anything'), null)
    ->start(null, $config);

CTGTest::init('always — returns empty array with no input')
    ->stage('execute', fn($_) => CTGFnprog::always([])())
    ->assert('returns empty array', fn($r) => $r, [])
    ->start(null, $config);

CTGTest::init('always — as default case in cond')
    ->stage('build', fn($_) => CTGFnprog::cond([
        [fn($x) => $x > 10, CTGFnprog::always('big')],
        [CTGFnprog::always(true), CTGFnprog::always('small')],
    ]))
    ->assert('fallback works', fn($fn) => $fn(5), 'small')
    ->start(null, $config);

// ── tap ─────────────────────────────────────────────────────────

CTGTest::init('tap — returns original value')
    ->stage('execute', fn($_) => CTGFnprog::tap(fn($x) => null)(42))
    ->assert('value unchanged', fn($r) => $r, 42)
    ->start(null, $config);

CTGTest::init('tap — returns original array')
    ->stage('data', fn($_) => [1, 2, 3])
    ->stage('execute', fn($data) => CTGFnprog::tap(fn($x) => 'ignored')($data))
    ->assert('array unchanged', fn($r) => $r, [1, 2, 3])
    ->start(null, $config);

CTGTest::init('tap — side effect executes')
    ->stage('setup', fn($_) => ['captured' => null])
    ->stage('execute', function($ctx) {
        $captured = null;
        CTGFnprog::tap(function($val) use (&$captured) {
            $captured = $val;
        })('hello');
        return $captured;
    })
    ->assert('captured the value', fn($r) => $r, 'hello')
    ->start(null, $config);

CTGTest::init('tap — works in pipeline')
    ->stage('execute', function($_) {
        $log = [];
        $result = CTGFnprog::pipe([
            fn($x) => $x * 2,
            CTGFnprog::tap(function($x) use (&$log) { $log[] = "after double: {$x}"; }),
            fn($x) => $x + 1,
            CTGFnprog::tap(function($x) use (&$log) { $log[] = "after add: {$x}"; }),
        ])(5);
        return ['result' => $result, 'log' => $log];
    })
    ->assert('pipeline result correct', fn($r) => $r['result'], 11)
    ->assert('first log entry', fn($r) => $r['log'][0], 'after double: 10')
    ->assert('second log entry', fn($r) => $r['log'][1], 'after add: 11')
    ->start(null, $config);

CTGTest::init('tap — ignores return value of side effect')
    ->stage('execute', fn($_) => CTGFnprog::tap(fn($x) => 'this is ignored')('original'))
    ->assert('returns original', fn($r) => $r, 'original')
    ->start(null, $config);

// ── defaultTo ───────────────────────────────────────────────────

CTGTest::init('defaultTo — returns default when null')
    ->stage('execute', fn($_) => CTGFnprog::defaultTo('N/A')(null))
    ->assert('returns default', fn($r) => $r, 'N/A')
    ->start(null, $config);

CTGTest::init('defaultTo — returns value when not null')
    ->stage('execute', fn($_) => CTGFnprog::defaultTo('N/A')('hello'))
    ->assert('returns value', fn($r) => $r, 'hello')
    ->start(null, $config);

CTGTest::init('defaultTo — does not replace false')
    ->stage('execute', fn($_) => CTGFnprog::defaultTo('N/A')(false))
    ->assert('returns false not default', fn($r) => $r, false)
    ->start(null, $config);

CTGTest::init('defaultTo — does not replace 0')
    ->stage('execute', fn($_) => CTGFnprog::defaultTo(99)(0))
    ->assert('returns 0 not default', fn($r) => $r, 0)
    ->start(null, $config);

CTGTest::init('defaultTo — does not replace empty string')
    ->stage('execute', fn($_) => CTGFnprog::defaultTo('fallback')(''))
    ->assert('returns empty string', fn($r) => $r, '')
    ->start(null, $config);

CTGTest::init('defaultTo — does not replace empty array')
    ->stage('execute', fn($_) => CTGFnprog::defaultTo(['fallback'])([]))
    ->assert('returns empty array', fn($r) => $r, [])
    ->start(null, $config);

CTGTest::init('defaultTo — in pipeline after first()')
    ->stage('execute', fn($_) => CTGFnprog::pipe([
        CTGFnprog::first(fn($r) => $r['role'] === 'superadmin'),
        CTGFnprog::defaultTo(['name' => 'Fallback', 'role' => 'superadmin']),
    ])([
        ['name' => 'Alice', 'role' => 'admin'],
        ['name' => 'Bob', 'role' => 'editor'],
    ]))
    ->assert('returns default record', fn($r) => $r['name'], 'Fallback')
    ->start(null, $config);

// ── coalesce ────────────────────────────────────────────────────

CTGTest::init('coalesce — returns first non-null')
    ->stage('execute', fn($_) => CTGFnprog::coalesce(null, null, 'fallback'))
    ->assert('returns fallback', fn($r) => $r, 'fallback')
    ->start(null, $config);

CTGTest::init('coalesce — returns first value when not null')
    ->stage('execute', fn($_) => CTGFnprog::coalesce('first', 'second', 'third'))
    ->assert('returns first', fn($r) => $r, 'first')
    ->start(null, $config);

CTGTest::init('coalesce — skips leading nulls')
    ->stage('execute', fn($_) => CTGFnprog::coalesce(null, 'second'))
    ->assert('returns second', fn($r) => $r, 'second')
    ->start(null, $config);

CTGTest::init('coalesce — all null returns null')
    ->stage('execute', fn($_) => CTGFnprog::coalesce(null, null, null))
    ->assert('returns null', fn($r) => $r, null)
    ->start(null, $config);

CTGTest::init('coalesce — false is not null')
    ->stage('execute', fn($_) => CTGFnprog::coalesce(null, false, 'fallback'))
    ->assert('returns false', fn($r) => $r, false)
    ->start(null, $config);

CTGTest::init('coalesce — 0 is not null')
    ->stage('execute', fn($_) => CTGFnprog::coalesce(null, 0, 'fallback'))
    ->assert('returns 0', fn($r) => $r, 0)
    ->start(null, $config);

CTGTest::init('coalesce — empty string is not null')
    ->stage('execute', fn($_) => CTGFnprog::coalesce(null, '', 'fallback'))
    ->assert('returns empty string', fn($r) => $r, '')
    ->start(null, $config);

CTGTest::init('coalesce — single non-null value')
    ->stage('execute', fn($_) => CTGFnprog::coalesce('only'))
    ->assert('returns it', fn($r) => $r, 'only')
    ->start(null, $config);
