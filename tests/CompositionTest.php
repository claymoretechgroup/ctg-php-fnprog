<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CTG\Test\CTGTest;
use CTG\FnProg\CTGFnprog;

// Tests for CTGFnprog composition methods: pipe, compose, partial, curry

$config = ['output' => 'console'];

// ── pipe ────────────────────────────────────────────────────────

CTGTest::init('pipe — single function')
    ->stage('build', fn($_) => CTGFnprog::pipe([
        fn($x) => $x * 2,
    ]))
    ->stage('execute', fn($fn) => $fn(5))
    ->assert('returns doubled value', fn($r) => $r, 10)
    ->start(null, $config);

CTGTest::init('pipe — multiple functions left to right')
    ->stage('build', fn($_) => CTGFnprog::pipe([
        fn($x) => $x + 1,
        fn($x) => $x * 2,
        fn($x) => $x - 3,
    ]))
    ->stage('execute', fn($fn) => $fn(5))
    ->assert('applies in order: (5+1)*2-3 = 9', fn($r) => $r, 9)
    ->start(null, $config);

CTGTest::init('pipe — empty function list')
    ->stage('build', fn($_) => CTGFnprog::pipe([]))
    ->stage('execute', fn($fn) => $fn('hello'))
    ->assert('returns input unchanged', fn($r) => $r, 'hello')
    ->start(null, $config);

CTGTest::init('pipe — with collection operations')
    ->stage('build data', fn($_) => [
        ['name' => 'Alice', 'active' => true, 'email' => 'ALICE@TEST.COM'],
        ['name' => 'Bob', 'active' => false, 'email' => 'BOB@TEST.COM'],
        ['name' => 'Charlie', 'active' => true, 'email' => 'CHARLIE@TEST.COM'],
    ])
    ->stage('build pipeline', fn($data) => [
        'data' => $data,
        'pipeline' => CTGFnprog::pipe([
            CTGFnprog::filter(fn($r) => $r['active']),
            CTGFnprog::pluck('email'),
            CTGFnprog::map('strtolower'),
        ]),
    ])
    ->stage('execute', fn($ctx) => $ctx['pipeline']($ctx['data']))
    ->assert('filters, plucks, and maps', fn($r) => $r, ['alice@test.com', 'charlie@test.com'])
    ->start(null, $config);

// ── compose ─────────────────────────────────────────────────────

CTGTest::init('compose — applies right to left')
    ->stage('build', fn($_) => CTGFnprog::compose([
        fn($x) => $x - 3,
        fn($x) => $x * 2,
        fn($x) => $x + 1,
    ]))
    ->stage('execute', fn($fn) => $fn(5))
    ->assert('applies in reverse: (5+1)*2-3 = 9', fn($r) => $r, 9)
    ->start(null, $config);

CTGTest::init('compose — single function')
    ->stage('build', fn($_) => CTGFnprog::compose([
        fn($x) => $x * 3,
    ]))
    ->stage('execute', fn($fn) => $fn(4))
    ->assert('returns tripled value', fn($r) => $r, 12)
    ->start(null, $config);

CTGTest::init('compose — empty function list')
    ->stage('build', fn($_) => CTGFnprog::compose([]))
    ->stage('execute', fn($fn) => $fn(42))
    ->assert('returns input unchanged', fn($r) => $r, 42)
    ->start(null, $config);

// ── partial ─────────────────────────────────────────────────────

CTGTest::init('partial — pre-fills first argument')
    ->stage('build', fn($_) => CTGFnprog::partial(fn($x, $y) => $x * $y, 2))
    ->stage('execute', fn($fn) => $fn(5))
    ->assert('2 * 5 = 10', fn($r) => $r, 10)
    ->start(null, $config);

CTGTest::init('partial — pre-fills multiple arguments')
    ->stage('build', fn($_) => CTGFnprog::partial(fn($a, $b, $c) => "{$a}, {$b}! {$c}", 'Hello', 'Alice'))
    ->stage('execute', fn($fn) => $fn('Welcome.'))
    ->assert('fills first two args', fn($r) => $r, 'Hello, Alice! Welcome.')
    ->start(null, $config);

CTGTest::init('partial — pre-fills all arguments')
    ->stage('build', fn($_) => CTGFnprog::partial(fn($x, $y) => $x + $y, 3, 4))
    ->stage('execute', fn($fn) => $fn())
    ->assert('3 + 4 = 7', fn($r) => $r, 7)
    ->start(null, $config);

CTGTest::init('partial — string callable')
    ->stage('build', fn($_) => CTGFnprog::partial('str_repeat', 'ha'))
    ->stage('execute', fn($fn) => $fn(3))
    ->assert('repeats string', fn($r) => $r, 'hahaha')
    ->start(null, $config);

// ── curry ───────────────────────────────────────────────────────

CTGTest::init('curry — full currying one at a time')
    ->stage('build', fn($_) => CTGFnprog::curry(fn($a, $b, $c) => $a + $b + $c))
    ->stage('execute', fn($fn) => $fn(1)(2)(3))
    ->assert('1 + 2 + 3 = 6', fn($r) => $r, 6)
    ->start(null, $config);

CTGTest::init('curry — partial application with multiple args')
    ->stage('build', fn($_) => CTGFnprog::curry(fn($a, $b, $c) => $a + $b + $c))
    ->stage('execute', fn($fn) => $fn(1, 2)(3))
    ->assert('(1,2) then 3 = 6', fn($r) => $r, 6)
    ->start(null, $config);

CTGTest::init('curry — all args at once')
    ->stage('build', fn($_) => CTGFnprog::curry(fn($a, $b, $c) => $a + $b + $c))
    ->stage('execute', fn($fn) => $fn(1, 2, 3))
    ->assert('all at once = 6', fn($r) => $r, 6)
    ->start(null, $config);

CTGTest::init('curry — single argument function')
    ->stage('build', fn($_) => CTGFnprog::curry(fn($x) => $x * 2))
    ->stage('execute', fn($fn) => $fn(5))
    ->assert('immediately executes', fn($r) => $r, 10)
    ->start(null, $config);

CTGTest::init('curry — zero argument function')
    ->stage('build', fn($_) => CTGFnprog::curry(fn() => 42))
    ->stage('execute', fn($fn) => $fn())
    ->assert('returns value', fn($r) => $r, 42)
    ->start(null, $config);
