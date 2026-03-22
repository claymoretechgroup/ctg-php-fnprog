<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CTG\Test\CTGTest;
use CTG\FnProg\CTGFnprog;

// Tests for CTGFnprog logic methods: when, unless, either, all, not, cond

$config = ['output' => 'console'];

$users = [
    ['name' => 'Alice', 'role' => 'admin', 'active' => true, 'age' => 30, 'banned' => false],
    ['name' => 'Bob', 'role' => 'editor', 'active' => false, 'age' => 25, 'banned' => false],
    ['name' => 'Charlie', 'role' => 'admin', 'active' => true, 'age' => 35, 'banned' => false],
    ['name' => 'Diana', 'role' => 'viewer', 'active' => true, 'age' => 17, 'banned' => true],
];

$GLOBALS['logic_users'] = $users;

// ── when ────────────────────────────────────────────────────────

CTGTest::init('when — applies function when predicate is true')
    ->stage('execute', fn($_) => CTGFnprog::when(
        fn($rows) => count($rows) > 3,
        CTGFnprog::take(2)
    )($GLOBALS['logic_users']))
    ->assert('took first 2', fn($r) => count($r), 2)
    ->assert('first is Alice', fn($r) => $r[0]['name'], 'Alice')
    ->start(null, $config);

CTGTest::init('when — passes through when predicate is false')
    ->stage('execute', fn($_) => CTGFnprog::when(
        fn($rows) => count($rows) > 100,
        CTGFnprog::take(2)
    )($GLOBALS['logic_users']))
    ->assert('all 4 returned', fn($r) => count($r), 4)
    ->start(null, $config);

CTGTest::init('when — works with scalar values')
    ->stage('execute', fn($_) => CTGFnprog::when(
        fn($x) => $x > 10,
        fn($x) => $x * 2
    )(15))
    ->assert('doubled', fn($r) => $r, 30)
    ->start(null, $config);

CTGTest::init('when — scalar passes through when false')
    ->stage('execute', fn($_) => CTGFnprog::when(
        fn($x) => $x > 10,
        fn($x) => $x * 2
    )(5))
    ->assert('unchanged', fn($r) => $r, 5)
    ->start(null, $config);

// ── unless ──────────────────────────────────────────────────────

CTGTest::init('unless — applies when predicate is false')
    ->stage('execute', fn($_) => CTGFnprog::unless(
        fn($rows) => count($rows) === 0,
        CTGFnprog::sortBy('name')
    )($GLOBALS['logic_users']))
    ->assert('sorted', fn($r) => $r[0]['name'], 'Alice')
    ->start(null, $config);

CTGTest::init('unless — passes through when predicate is true')
    ->stage('execute', fn($_) => CTGFnprog::unless(
        fn($rows) => count($rows) === 0,
        CTGFnprog::sortBy('name')
    )([]))
    ->assert('empty unchanged', fn($r) => $r, [])
    ->start(null, $config);

// ── either ──────────────────────────────────────────────────────

CTGTest::init('either — true when any predicate matches')
    ->stage('build predicate', fn($_) => CTGFnprog::either(
        fn($r) => $r['role'] === 'admin',
        fn($r) => $r['role'] === 'editor'
    ))
    ->stage('filter', fn($pred) => CTGFnprog::filter($pred)($GLOBALS['logic_users']))
    ->assert('returns 3 users', fn($r) => count($r), 3)
    ->assert('Alice included', fn($r) => $r[0]['name'], 'Alice')
    ->assert('Bob included', fn($r) => $r[1]['name'], 'Bob')
    ->assert('Charlie included', fn($r) => $r[2]['name'], 'Charlie')
    ->start(null, $config);

CTGTest::init('either — false when no predicate matches')
    ->stage('build predicate', fn($_) => CTGFnprog::either(
        fn($r) => $r['role'] === 'superadmin',
        fn($r) => $r['role'] === 'moderator'
    ))
    ->stage('filter', fn($pred) => CTGFnprog::filter($pred)($GLOBALS['logic_users']))
    ->assert('returns empty', fn($r) => $r, [])
    ->start(null, $config);

CTGTest::init('either — single predicate')
    ->stage('build predicate', fn($_) => CTGFnprog::either(
        fn($r) => $r['role'] === 'viewer'
    ))
    ->stage('filter', fn($pred) => CTGFnprog::filter($pred)($GLOBALS['logic_users']))
    ->assert('returns 1 viewer', fn($r) => count($r), 1)
    ->start(null, $config);

// ── all ─────────────────────────────────────────────────────────

CTGTest::init('all — true when every predicate matches')
    ->stage('build predicate', fn($_) => CTGFnprog::all(
        fn($r) => $r['active'] === true,
        fn($r) => $r['role'] === 'admin'
    ))
    ->stage('filter', fn($pred) => CTGFnprog::filter($pred)($GLOBALS['logic_users']))
    ->assert('returns 2 active admins', fn($r) => count($r), 2)
    ->assert('Alice', fn($r) => $r[0]['name'], 'Alice')
    ->assert('Charlie', fn($r) => $r[1]['name'], 'Charlie')
    ->start(null, $config);

CTGTest::init('all — false when any predicate fails')
    ->stage('build predicate', fn($_) => CTGFnprog::all(
        fn($r) => $r['active'] === true,
        fn($r) => $r['age'] >= 18,
        fn($r) => $r['banned'] === false
    ))
    ->stage('filter', fn($pred) => CTGFnprog::filter($pred)($GLOBALS['logic_users']))
    ->assert('returns 2 eligible users', fn($r) => count($r), 2)
    ->assert('Alice', fn($r) => $r[0]['name'], 'Alice')
    ->assert('Charlie', fn($r) => $r[1]['name'], 'Charlie')
    ->start(null, $config);

// ── not ─────────────────────────────────────────────────────────

CTGTest::init('not — negates predicate')
    ->stage('build predicate', fn($_) => CTGFnprog::not(fn($r) => $r['banned']))
    ->stage('filter', fn($pred) => CTGFnprog::filter($pred)($GLOBALS['logic_users']))
    ->assert('returns 3 non-banned', fn($r) => count($r), 3)
    ->start(null, $config);

CTGTest::init('not — double negation')
    ->stage('build predicate', fn($_) => CTGFnprog::not(CTGFnprog::not(fn($r) => $r['active'])))
    ->stage('filter', fn($pred) => CTGFnprog::filter($pred)($GLOBALS['logic_users']))
    ->assert('same as original', fn($r) => count($r), 3)
    ->start(null, $config);

// ── cond ────────────────────────────────────────────────────────

CTGTest::init('cond — matches first true predicate')
    ->stage('build', fn($_) => CTGFnprog::cond([
        [fn($n) => $n >= 90, CTGFnprog::always('A')],
        [fn($n) => $n >= 80, CTGFnprog::always('B')],
        [fn($n) => $n >= 70, CTGFnprog::always('C')],
        [fn($n) => $n >= 60, CTGFnprog::always('D')],
        [CTGFnprog::always(true), CTGFnprog::always('F')],
    ]))
    ->assert('95 = A', fn($fn) => $fn(95), 'A')
    ->assert('85 = B', fn($fn) => $fn(85), 'B')
    ->assert('75 = C', fn($fn) => $fn(75), 'C')
    ->assert('65 = D', fn($fn) => $fn(65), 'D')
    ->assert('42 = F', fn($fn) => $fn(42), 'F')
    ->start(null, $config);

CTGTest::init('cond — no match returns null')
    ->stage('build', fn($_) => CTGFnprog::cond([
        [fn($x) => $x > 100, CTGFnprog::always('big')],
    ]))
    ->stage('execute', fn($fn) => $fn(5))
    ->assert('returns null', fn($r) => $r, null)
    ->start(null, $config);

CTGTest::init('cond — http status categorization')
    ->stage('build', fn($_) => CTGFnprog::cond([
        [fn($code) => $code >= 500, CTGFnprog::always('server_error')],
        [fn($code) => $code >= 400, CTGFnprog::always('client_error')],
        [fn($code) => $code >= 300, CTGFnprog::always('redirect')],
        [fn($code) => $code >= 200, CTGFnprog::always('success')],
        [CTGFnprog::always(true), CTGFnprog::always('unknown')],
    ]))
    ->assert('200 = success', fn($fn) => $fn(200), 'success')
    ->assert('301 = redirect', fn($fn) => $fn(301), 'redirect')
    ->assert('404 = client_error', fn($fn) => $fn(404), 'client_error')
    ->assert('500 = server_error', fn($fn) => $fn(500), 'server_error')
    ->assert('100 = unknown', fn($fn) => $fn(100), 'unknown')
    ->start(null, $config);

// ── composed logic in pipeline ──────────────────────────────────

CTGTest::init('composed predicates in pipeline')
    ->stage('build pipeline', fn($_) => CTGFnprog::pipe([
        CTGFnprog::filter(CTGFnprog::all(
            fn($r) => $r['active'],
            fn($r) => $r['age'] >= 18,
            CTGFnprog::not(fn($r) => $r['banned'])
        )),
        CTGFnprog::pluck('name'),
    ]))
    ->stage('execute', fn($pipeline) => $pipeline($GLOBALS['logic_users']))
    ->assert('eligible names', fn($r) => $r, ['Alice', 'Charlie'])
    ->start(null, $config);

// ── Edge case: vacuous either/all ──────────────────────────────

CTGTest::init('either — zero predicates returns false')
    ->stage('build', fn($_) => CTGFnprog::either())
    ->assert('always false', fn($fn) => $fn('anything'), false)
    ->start(null, $config);

CTGTest::init('all — zero predicates returns true')
    ->stage('build', fn($_) => CTGFnprog::all())
    ->assert('always true', fn($fn) => $fn('anything'), true)
    ->start(null, $config);

// ── Edge case: cond with empty pairs ───────────────────────────

CTGTest::init('cond — empty pairs returns null')
    ->stage('build', fn($_) => CTGFnprog::cond([]))
    ->stage('execute', fn($fn) => $fn('anything'))
    ->assert('returns null', fn($r) => $r, null)
    ->start(null, $config);
