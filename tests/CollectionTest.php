<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CTG\Test\CTGTest;
use CTG\FnProg\CTGFnprog;

// Tests for CTGFnprog collection methods

$config = ['output' => 'console'];

// Shared test data
$users = [
    ['id' => 1, 'name' => 'Alice', 'email' => 'alice@test.com', 'role' => 'admin', 'active' => true, 'age' => 30],
    ['id' => 2, 'name' => 'Bob', 'email' => 'bob@test.com', 'role' => 'editor', 'active' => false, 'age' => 25],
    ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@test.com', 'role' => 'admin', 'active' => true, 'age' => 35],
    ['id' => 4, 'name' => 'Diana', 'email' => 'diana@test.com', 'role' => 'viewer', 'active' => true, 'age' => 28],
    ['id' => 5, 'name' => 'Eve', 'email' => 'eve@test.com', 'role' => 'editor', 'active' => false, 'age' => 22],
];

// ── pluck ───────────────────────────────────────────────────────

CTGTest::init('pluck — extracts single field')
    ->stage('execute', fn($_) => CTGFnprog::pluck('name')($GLOBALS['users']))
    ->assert('returns names', fn($r) => $r, ['Alice', 'Bob', 'Charlie', 'Diana', 'Eve'])
    ->start(null, $config);

CTGTest::init('pluck — extracts numeric field')
    ->stage('execute', fn($_) => CTGFnprog::pluck('id')($GLOBALS['users']))
    ->assert('returns ids', fn($r) => $r, [1, 2, 3, 4, 5])
    ->start(null, $config);

CTGTest::init('pluck — empty array')
    ->stage('execute', fn($_) => CTGFnprog::pluck('name')([]))
    ->assert('returns empty', fn($r) => $r, [])
    ->start(null, $config);

// ── pick ────────────────────────────────────────────────────────

CTGTest::init('pick — keeps specified fields')
    ->stage('execute', fn($_) => CTGFnprog::pick(['id', 'name'])($GLOBALS['users']))
    ->assert('first row has only id and name', fn($r) => $r[0], ['id' => 1, 'name' => 'Alice'])
    ->assert('row count preserved', fn($r) => count($r), 5)
    ->start(null, $config);

CTGTest::init('pick — nonexistent field ignored')
    ->stage('execute', fn($_) => CTGFnprog::pick(['id', 'nonexistent'])($GLOBALS['users']))
    ->assert('first row has only id', fn($r) => $r[0], ['id' => 1])
    ->start(null, $config);

// ── omit ────────────────────────────────────────────────────────

CTGTest::init('omit — removes specified fields')
    ->stage('execute', fn($_) => CTGFnprog::omit(['email', 'age'])($GLOBALS['users']))
    ->assert('first row lacks email and age', fn($r) => $r[0], ['id' => 1, 'name' => 'Alice', 'role' => 'admin', 'active' => true])
    ->start(null, $config);

CTGTest::init('omit — nonexistent field ignored')
    ->stage('execute', fn($_) => CTGFnprog::omit(['nonexistent'])($GLOBALS['users']))
    ->assert('rows unchanged', fn($r) => $r[0], $GLOBALS['users'][0])
    ->start(null, $config);

// ── keyBy ───────────────────────────────────────────────────────

CTGTest::init('keyBy — re-indexes by field')
    ->stage('execute', fn($_) => CTGFnprog::keyBy('id')($GLOBALS['users']))
    ->assert('key 1 is Alice', fn($r) => $r[1]['name'], 'Alice')
    ->assert('key 3 is Charlie', fn($r) => $r[3]['name'], 'Charlie')
    ->assert('has 5 entries', fn($r) => count($r), 5)
    ->start(null, $config);

CTGTest::init('keyBy — string keys')
    ->stage('data', fn($_) => [
        ['code' => 'US', 'name' => 'United States'],
        ['code' => 'UK', 'name' => 'United Kingdom'],
    ])
    ->stage('execute', fn($data) => CTGFnprog::keyBy('code')($data))
    ->assert('US key works', fn($r) => $r['US']['name'], 'United States')
    ->start(null, $config);

// ── groupBy ─────────────────────────────────────────────────────

CTGTest::init('groupBy — groups rows by field')
    ->stage('execute', fn($_) => CTGFnprog::groupBy('role')($GLOBALS['users']))
    ->assert('has admin group', fn($r) => count($r['admin']), 2)
    ->assert('has editor group', fn($r) => count($r['editor']), 2)
    ->assert('has viewer group', fn($r) => count($r['viewer']), 1)
    ->assert('admin contains Alice', fn($r) => $r['admin'][0]['name'], 'Alice')
    ->start(null, $config);

// ── sortBy ──────────────────────────────────────────────────────

CTGTest::init('sortBy — ascending by default')
    ->stage('execute', fn($_) => CTGFnprog::sortBy('name')($GLOBALS['users']))
    ->assert('first is Alice', fn($r) => $r[0]['name'], 'Alice')
    ->assert('last is Eve', fn($r) => $r[4]['name'], 'Eve')
    ->start(null, $config);

CTGTest::init('sortBy — descending')
    ->stage('execute', fn($_) => CTGFnprog::sortBy('age', 'DESC')($GLOBALS['users']))
    ->assert('first is oldest', fn($r) => $r[0]['name'], 'Charlie')
    ->assert('last is youngest', fn($r) => $r[4]['name'], 'Eve')
    ->start(null, $config);

// ── uniqueBy ────────────────────────────────────────────────────

CTGTest::init('uniqueBy — deduplicates by field')
    ->stage('data', fn($_) => [
        ['email' => 'alice@test.com', 'attempt' => 1],
        ['email' => 'bob@test.com', 'attempt' => 1],
        ['email' => 'alice@test.com', 'attempt' => 2],
        ['email' => 'bob@test.com', 'attempt' => 2],
    ])
    ->stage('execute', fn($data) => CTGFnprog::uniqueBy('email')($data))
    ->assert('keeps first occurrences', fn($r) => count($r), 2)
    ->assert('Alice attempt is 1', fn($r) => $r[0]['attempt'], 1)
    ->assert('Bob attempt is 1', fn($r) => $r[1]['attempt'], 1)
    ->start(null, $config);

// ── flatten ─────────────────────────────────────────────────────

CTGTest::init('flatten — one level of nesting')
    ->stage('execute', fn($_) => CTGFnprog::flatten()([['a', 'b'], ['c', 'd'], ['e']]))
    ->assert('flattened', fn($r) => $r, ['a', 'b', 'c', 'd', 'e'])
    ->start(null, $config);

CTGTest::init('flatten — only one level deep')
    ->stage('execute', fn($_) => CTGFnprog::flatten()([['a', ['b', 'c']], ['d']]))
    ->assert('inner array preserved', fn($r) => $r, ['a', ['b', 'c'], 'd'])
    ->start(null, $config);

CTGTest::init('flatten — empty arrays')
    ->stage('execute', fn($_) => CTGFnprog::flatten()([[], ['a'], []]))
    ->assert('skips empties', fn($r) => $r, ['a'])
    ->start(null, $config);

// ── where ───────────────────────────────────────────────────────

CTGTest::init('where — filters by field equality')
    ->stage('execute', fn($_) => CTGFnprog::where('role', 'admin')($GLOBALS['users']))
    ->assert('returns 2 admins', fn($r) => count($r), 2)
    ->assert('first is Alice', fn($r) => $r[0]['name'], 'Alice')
    ->assert('second is Charlie', fn($r) => $r[1]['name'], 'Charlie')
    ->start(null, $config);

CTGTest::init('where — no matches returns empty')
    ->stage('execute', fn($_) => CTGFnprog::where('role', 'superadmin')($GLOBALS['users']))
    ->assert('returns empty array', fn($r) => $r, [])
    ->start(null, $config);

// ── filter ──────────────────────────────────────────────────────

CTGTest::init('filter — keeps matching rows')
    ->stage('execute', fn($_) => CTGFnprog::filter(fn($r) => $r['active'])($GLOBALS['users']))
    ->assert('returns 3 active users', fn($r) => count($r), 3)
    ->assert('resets keys', fn($r) => array_keys($r), [0, 1, 2])
    ->start(null, $config);

CTGTest::init('filter — no matches returns empty')
    ->stage('execute', fn($_) => CTGFnprog::filter(fn($r) => $r['age'] > 100)($GLOBALS['users']))
    ->assert('returns empty', fn($r) => $r, [])
    ->start(null, $config);

// ── reject ──────────────────────────────────────────────────────

CTGTest::init('reject — removes matching rows')
    ->stage('execute', fn($_) => CTGFnprog::reject(fn($r) => $r['active'])($GLOBALS['users']))
    ->assert('returns 2 inactive users', fn($r) => count($r), 2)
    ->assert('first is Bob', fn($r) => $r[0]['name'], 'Bob')
    ->start(null, $config);

// ── take ────────────────────────────────────────────────────────

CTGTest::init('take — first N elements')
    ->stage('execute', fn($_) => CTGFnprog::take(2)($GLOBALS['users']))
    ->assert('returns 2 rows', fn($r) => count($r), 2)
    ->assert('first is Alice', fn($r) => $r[0]['name'], 'Alice')
    ->assert('second is Bob', fn($r) => $r[1]['name'], 'Bob')
    ->start(null, $config);

CTGTest::init('take — more than available')
    ->stage('execute', fn($_) => CTGFnprog::take(100)($GLOBALS['users']))
    ->assert('returns all 5', fn($r) => count($r), 5)
    ->start(null, $config);

CTGTest::init('take — zero')
    ->stage('execute', fn($_) => CTGFnprog::take(0)($GLOBALS['users']))
    ->assert('returns empty', fn($r) => $r, [])
    ->start(null, $config);

// ── skip ────────────────────────────────────────────────────────

CTGTest::init('skip — skips first N elements')
    ->stage('execute', fn($_) => CTGFnprog::skip(3)($GLOBALS['users']))
    ->assert('returns 2 rows', fn($r) => count($r), 2)
    ->assert('first is Diana', fn($r) => $r[0]['name'], 'Diana')
    ->start(null, $config);

CTGTest::init('skip — more than available')
    ->stage('execute', fn($_) => CTGFnprog::skip(100)($GLOBALS['users']))
    ->assert('returns empty', fn($r) => $r, [])
    ->start(null, $config);

// ── map ─────────────────────────────────────────────────────────

CTGTest::init('map — transforms each element')
    ->stage('execute', fn($_) => CTGFnprog::map(fn($r) => $r['name'])($GLOBALS['users']))
    ->assert('returns names', fn($r) => $r, ['Alice', 'Bob', 'Charlie', 'Diana', 'Eve'])
    ->start(null, $config);

CTGTest::init('map — with transformation')
    ->stage('data', fn($_) => [
        ['name' => 'Alice', 'email' => 'ALICE@TEST.COM'],
        ['name' => 'Bob', 'email' => 'BOB@TEST.COM'],
    ])
    ->stage('execute', fn($data) => CTGFnprog::map(fn($r) => [
        'name' => $r['name'],
        'email' => strtolower($r['email']),
    ])($data))
    ->assert('emails lowercased', fn($r) => $r[1]['email'], 'bob@test.com')
    ->start(null, $config);

// ── each ────────────────────────────────────────────────────────

CTGTest::init('each — returns original array unchanged')
    ->stage('execute', fn($_) => CTGFnprog::each(fn($r) => null)($GLOBALS['users']))
    ->assert('returns same data', fn($r) => $r, $GLOBALS['users'])
    ->start(null, $config);

CTGTest::init('each — side effect executes')
    ->stage('setup', fn($_) => ['log' => [], 'data' => [['x' => 1], ['x' => 2]]])
    ->stage('execute', fn($ctx) => [
        'log' => &$ctx['log'],
        'result' => CTGFnprog::each(function($r) use (&$ctx) {
            $ctx['log'][] = $r['x'];
        })($ctx['data']),
    ])
    ->assert('returns original data', fn($r) => $r['result'], [['x' => 1], ['x' => 2]])
    ->start(null, $config);

// ── rename ──────────────────────────────────────────────────────

CTGTest::init('rename — renames fields in each row')
    ->stage('data', fn($_) => [
        ['created_at' => '2024-01-01', 'email' => 'alice@test.com'],
        ['created_at' => '2024-02-01', 'email' => 'bob@test.com'],
    ])
    ->stage('execute', fn($data) => CTGFnprog::rename(['created_at' => 'createdAt', 'email' => 'emailAddress'])($data))
    ->assert('first row renamed', fn($r) => array_keys($r[0]), ['createdAt', 'emailAddress'])
    ->assert('values preserved', fn($r) => $r[0]['createdAt'], '2024-01-01')
    ->start(null, $config);

CTGTest::init('rename — unmapped fields preserved')
    ->stage('data', fn($_) => [['id' => 1, 'name' => 'Alice', 'old_key' => 'val']])
    ->stage('execute', fn($data) => CTGFnprog::rename(['old_key' => 'newKey'])($data))
    ->assert('id preserved', fn($r) => isset($r[0]['id']), true)
    ->assert('name preserved', fn($r) => isset($r[0]['name']), true)
    ->assert('old key gone', fn($r) => isset($r[0]['old_key']), false)
    ->assert('new key exists', fn($r) => $r[0]['newKey'], 'val')
    ->start(null, $config);

// ── withField ───────────────────────────────────────────────────

CTGTest::init('withField — adds computed field')
    ->stage('data', fn($_) => [
        ['total' => 100.00],
        ['total' => 50.00],
    ])
    ->stage('execute', fn($data) => CTGFnprog::withField('tax', fn($r) => $r['total'] * 0.08)($data))
    ->assert('first row has tax', fn($r) => $r[0]['tax'], 8.0)
    ->assert('second row has tax', fn($r) => $r[1]['tax'], 4.0)
    ->assert('original field preserved', fn($r) => $r[0]['total'], 100.00)
    ->start(null, $config);

CTGTest::init('withField — chained in pipeline')
    ->stage('data', fn($_) => [['total' => 100.00]])
    ->stage('execute', fn($data) => CTGFnprog::pipe([
        CTGFnprog::withField('tax', fn($r) => $r['total'] * 0.08),
        CTGFnprog::withField('grand_total', fn($r) => $r['total'] + $r['tax']),
    ])($data))
    ->assert('grand total computed', fn($r) => $r[0]['grand_total'], 108.0)
    ->start(null, $config);

// ── castField ───────────────────────────────────────────────────

CTGTest::init('castField — casts to int')
    ->stage('data', fn($_) => [['id' => '42', 'name' => 'Alice']])
    ->stage('execute', fn($data) => CTGFnprog::castField('id', 'int')($data))
    ->assert('id is int', fn($r) => $r[0]['id'], 42)
    ->assert('id is integer type', fn($r) => is_int($r[0]['id']), true)
    ->start(null, $config);

CTGTest::init('castField — casts to float')
    ->stage('data', fn($_) => [['price' => '19.99']])
    ->stage('execute', fn($data) => CTGFnprog::castField('price', 'float')($data))
    ->assert('price is float', fn($r) => $r[0]['price'], 19.99)
    ->start(null, $config);

CTGTest::init('castField — casts to bool')
    ->stage('data', fn($_) => [['active' => 1], ['active' => 0]])
    ->stage('execute', fn($data) => CTGFnprog::castField('active', 'bool')($data))
    ->assert('first is true', fn($r) => $r[0]['active'], true)
    ->assert('second is false', fn($r) => $r[1]['active'], false)
    ->start(null, $config);

CTGTest::init('castField — casts to string')
    ->stage('data', fn($_) => [['code' => 123]])
    ->stage('execute', fn($data) => CTGFnprog::castField('code', 'string')($data))
    ->assert('code is string', fn($r) => $r[0]['code'], '123')
    ->start(null, $config);

// ── chunk ───────────────────────────────────────────────────────

CTGTest::init('chunk — splits into groups')
    ->stage('execute', fn($_) => CTGFnprog::chunk(3)([1, 2, 3, 4, 5, 6, 7]))
    ->assert('first chunk', fn($r) => $r[0], [1, 2, 3])
    ->assert('second chunk', fn($r) => $r[1], [4, 5, 6])
    ->assert('remainder chunk', fn($r) => $r[2], [7])
    ->assert('3 chunks total', fn($r) => count($r), 3)
    ->start(null, $config);

CTGTest::init('chunk — exact division')
    ->stage('execute', fn($_) => CTGFnprog::chunk(2)([1, 2, 3, 4]))
    ->assert('2 chunks', fn($r) => count($r), 2)
    ->start(null, $config);

CTGTest::init('chunk — empty array')
    ->stage('execute', fn($_) => CTGFnprog::chunk(5)([]))
    ->assert('returns empty', fn($r) => $r, [])
    ->start(null, $config);

// ── zip ─────────────────────────────────────────────────────────

CTGTest::init('zip — combines arrays element-wise')
    ->stage('execute', fn($_) => CTGFnprog::zip(['a', 'b', 'c'])([1, 2, 3]))
    ->assert('first pair', fn($r) => $r[0], [1, 'a'])
    ->assert('second pair', fn($r) => $r[1], [2, 'b'])
    ->assert('third pair', fn($r) => $r[2], [3, 'c'])
    ->start(null, $config);

CTGTest::init('zip — unequal lengths uses shortest')
    ->stage('execute', fn($_) => CTGFnprog::zip(['a', 'b'])([1, 2, 3]))
    ->assert('only 2 pairs', fn($r) => count($r), 2)
    ->start(null, $config);

// Make test data available globally for test closures
$GLOBALS['users'] = $users;
