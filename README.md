# ctg-php-fnprog

`ctg-php-fnprog` is a functional programming utility library for PHP. It
provides composable, pipeline-friendly operations for collections, logic,
and value transformation. Every public method returns a callable — nothing
executes until the returned function is invoked. Designed as a foundational
dependency across the CTG library ecosystem.

**Key Features:**

* **Everything returns a callable** — methods are factories that produce
  functions, not functions that produce values
* **Pipeline-native** — every callable slots into `pipe` or `compose`
  without adapters
* **Pure by default** — no mutation, no side effects, no hidden state
* **39 operations** across five groups: composition, collections,
  aggregation, logic, and value
* **Zero dependencies** — pure PHP 8.1+, no extensions required

## Install

Add the GitHub repository to your `composer.json`:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/claymoretechgroup/ctg-php-fnprog" }
    ]
}
```

Then require the package:

```
composer require ctg/php-fnprog
```

## Examples

### Building a Pipeline

Chain operations together with `pipe`. Data flows left to right:

```php
use CTG\FnProg\CTGFnprog;

$getActiveEmails = CTGFnprog::pipe([
    CTGFnprog::filter(fn($r) => $r['active']),
    CTGFnprog::pluck('email'),
    CTGFnprog::map('strtolower'),
]);

$emails = $getActiveEmails($users);
```

### Transforming for an API Response

Pick, rename, compute, and sort — all composable:

```php
$formatResponse = CTGFnprog::pipe([
    CTGFnprog::pick(['id', 'name', 'email', 'created_at']),
    CTGFnprog::rename(['created_at' => 'createdAt']),
    CTGFnprog::withField('displayName', fn($r) => ucfirst($r['name'])),
    CTGFnprog::omit(['name']),
    CTGFnprog::sortBy('displayName'),
]);

$response = $formatResponse($users);
```

### Composing Predicates

Build complex filters from simple predicates with `all`, `either`, and `not`:

```php
$isEligible = CTGFnprog::all(
    fn($r) => $r['active'],
    fn($r) => $r['age'] >= 18,
    CTGFnprog::not(fn($r) => $r['banned'])
);

$eligible = CTGFnprog::filter($isEligible)($users);
```

### Aggregating Data

Aggregation methods are typically the last step in a pipeline:

```php
$orderStats = CTGFnprog::pipe([
    CTGFnprog::where('status', 'completed'),
    fn($orders) => [
        'count'   => CTGFnprog::count()($orders),
        'total'   => CTGFnprog::sum('total')($orders),
        'average' => CTGFnprog::avg('total')($orders),
        'largest' => CTGFnprog::max('total')($orders),
    ],
]);

$stats = $orderStats($orders);
```

### Pattern Matching with cond

Evaluate predicate/transform pairs in order, apply the first match:

```php
$httpStatus = CTGFnprog::cond([
    [fn($code) => $code >= 500, CTGFnprog::always('server_error')],
    [fn($code) => $code >= 400, CTGFnprog::always('client_error')],
    [fn($code) => $code >= 300, CTGFnprog::always('redirect')],
    [fn($code) => $code >= 200, CTGFnprog::always('success')],
    [CTGFnprog::always(true),   CTGFnprog::always('unknown')],
]);

$httpStatus(404); // 'client_error'
$httpStatus(200); // 'success'
```

### Conditional Logic in Pipelines

Apply transforms conditionally with `when` and `unless`:

```php
$process = CTGFnprog::pipe([
    CTGFnprog::when(
        fn($rows) => count($rows) > 1000,
        CTGFnprog::take(1000)
    ),
    CTGFnprog::sortBy('created_at', 'DESC'),
    CTGFnprog::unless(
        fn($rows) => count($rows) === 0,
        CTGFnprog::map(fn($r) => array_merge($r, ['processed' => true]))
    ),
]);
```

### Debugging with tap

Inspect values at any point in a pipeline without changing them:

```php
$pipeline = CTGFnprog::pipe([
    CTGFnprog::filter(fn($r) => $r['active']),
    CTGFnprog::tap(fn($rows) => error_log("After filter: " . count($rows))),
    CTGFnprog::groupBy('role'),
    CTGFnprog::tap(fn($groups) => error_log("Roles: " . implode(', ', array_keys($groups)))),
]);
```

### Composing Pipelines from Pipelines

Pipelines are just callables. Compose them freely:

```php
$clean = CTGFnprog::pipe([
    CTGFnprog::filter(fn($r) => $r['active']),
    CTGFnprog::omit(['password_hash', 'internal_notes']),
]);

$format = CTGFnprog::pipe([
    CTGFnprog::rename(['created_at' => 'createdAt']),
    CTGFnprog::sortBy('name'),
]);

$cleanAndFormat = CTGFnprog::pipe([$clean, $format]);
$result = $cleanAndFormat($users);
```

## Notice

`ctg-php-fnprog` is under active development. The core API is stable but
additional operations may be added.
