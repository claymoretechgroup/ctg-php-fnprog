# CTGFnprog

Static utility class providing composable, pipeline-friendly functional
programming operations. Every public method is a factory that returns a
callable. No instances, no state, no constructor.

### Properties

CTGFnprog has no properties. It is a pure static class with a private
constructor to prevent instantiation.

---

## Composition

### CTGFnprog.pipe :: [(MIXED -> MIXED)] -> (MIXED -> MIXED)

Returns a callable that applies an array of functions left to right. The
output of each function becomes the input of the next. If the array is
empty, the returned callable passes its input through unchanged. This is
the primary composition tool.

```php
$process = CTGFnprog::pipe([
    CTGFnprog::filter(fn($r) => $r['active']),
    CTGFnprog::pluck('email'),
    CTGFnprog::map('strtolower'),
]);

$emails = $process($users);
```

### CTGFnprog.compose :: [(MIXED -> MIXED)] -> (MIXED -> MIXED)

Returns a callable that applies an array of functions right to left.
Traditional mathematical composition — `compose([f, g, h])` applies `h`
first, then `g`, then `f`. Implemented as `pipe(array_reverse($fns))`.

```php
$process = CTGFnprog::compose([
    fn($x) => $x - 3,
    fn($x) => $x * 2,
    fn($x) => $x + 1,
]);

$process(5); // 9 — applies (5+1)*2-3
```

### CTGFnprog.partial :: (MIXED -> MIXED), MIXED... -> (MIXED -> MIXED)

Partially applies arguments to a function. Returns a new callable that
accepts the remaining arguments. The pre-filled arguments are prepended
to any arguments passed to the returned callable.

```php
$double = CTGFnprog::partial(fn($x, $y) => $x * $y, 2);
$double(5); // 10
```

### CTGFnprog.curry :: (MIXED -> MIXED) -> (MIXED -> (MIXED -> MIXED))

Automatically curries a function. Returns a chain of callables that
accept one or more arguments until all parameters are satisfied, then
executes the original function. Uses `ReflectionFunction` to determine
arity. Functions with zero or one parameter are returned as-is.

```php
$add = CTGFnprog::curry(fn($a, $b, $c) => $a + $b + $c);
$add(1)(2)(3);  // 6
$add(1, 2)(3);  // 6
$add(1)(2, 3);  // 6
```

---

## Collections

All collection methods operate on arrays of associative arrays (rows).
Each returns a callable that accepts an array.

### CTGFnprog.pluck :: STRING -> ([ARRAY] -> [MIXED])

Returns a callable that extracts a single field value from each row.

```php
CTGFnprog::pluck('email')($users);
// ['alice@test.com', 'bob@test.com']
```

### CTGFnprog.pick :: [STRING] -> ([ARRAY] -> [ARRAY])

Returns a callable that keeps only the specified fields from each row.
Fields that do not exist in a row are silently omitted.

```php
CTGFnprog::pick(['id', 'email'])($users);
// [['id' => 1, 'email' => 'alice@...'], ['id' => 2, 'email' => 'bob@...']]
```

### CTGFnprog.omit :: [STRING] -> ([ARRAY] -> [ARRAY])

Returns a callable that removes the specified fields from each row.
Fields that do not exist in a row are silently ignored.

```php
CTGFnprog::omit(['password_hash', 'internal_notes'])($users);
```

### CTGFnprog.keyBy :: STRING -> ([ARRAY] -> ARRAY<STRING|INT, ARRAY>)

Returns a callable that re-indexes the array using a field's value as the
key. If multiple rows share the same key value, the last one wins.

```php
CTGFnprog::keyBy('id')($users);
// [1 => ['id' => 1, 'name' => 'Alice'], 2 => ['id' => 2, 'name' => 'Bob']]
```

### CTGFnprog.groupBy :: STRING -> ([ARRAY] -> ARRAY<STRING, [ARRAY]>)

Returns a callable that groups rows into buckets by a field value. Each
bucket is an array of rows that share the same value for the given field.

```php
CTGFnprog::groupBy('role')($users);
// ['admin' => [...], 'editor' => [...], 'viewer' => [...]]
```

### CTGFnprog.sortBy :: STRING, STRING -> ([ARRAY] -> [ARRAY])

Returns a callable that sorts rows by a field value. Direction defaults
to `'ASC'`. Uses the spaceship operator for comparison, so it works with
strings, integers, and floats.

```php
CTGFnprog::sortBy('name')($users);
CTGFnprog::sortBy('created_at', 'DESC')($users);
```

### CTGFnprog.uniqueBy :: STRING -> ([ARRAY] -> [ARRAY])

Returns a callable that deduplicates rows by a field value. Keeps the
first occurrence of each unique value.

```php
CTGFnprog::uniqueBy('email')($users);
```

### CTGFnprog.flatten :: VOID -> ([ARRAY] -> [MIXED])

Returns a callable that flattens one level of nested arrays. Non-array
elements are kept as-is. Only flattens one level deep.

```php
CTGFnprog::flatten()([['a', 'b'], ['c', 'd']]);
// ['a', 'b', 'c', 'd']
```

### CTGFnprog.where :: STRING, MIXED -> ([ARRAY] -> [ARRAY])

Returns a callable that keeps rows where a field strictly equals (`===`)
a value. Result keys are re-indexed.

```php
CTGFnprog::where('role', 'admin')($users);
```

### CTGFnprog.filter :: (MIXED -> BOOL) -> ([ARRAY] -> [ARRAY])

Returns a callable that keeps rows where the predicate returns true.
Result keys are re-indexed.

```php
CTGFnprog::filter(fn($r) => $r['age'] >= 18)($users);
```

### CTGFnprog.reject :: (MIXED -> BOOL) -> ([ARRAY] -> [ARRAY])

Returns a callable that removes rows where the predicate returns true.
Inverse of `filter`. Result keys are re-indexed.

```php
CTGFnprog::reject(fn($r) => $r['banned'])($users);
```

### CTGFnprog.take :: INT -> ([ARRAY] -> [ARRAY])

Returns a callable that takes the first N elements. If N exceeds the
array length, returns the entire array.

```php
CTGFnprog::take(5)($users);
```

### CTGFnprog.skip :: INT -> ([ARRAY] -> [ARRAY])

Returns a callable that skips the first N elements. Result keys are
re-indexed.

```php
CTGFnprog::skip(10)($users);
```

### CTGFnprog.map :: (MIXED -> MIXED) -> ([ARRAY] -> [ARRAY])

Returns a callable that applies a function to each element and returns
the array of results.

```php
CTGFnprog::map(fn($r) => ['name' => $r['name'], 'email' => strtolower($r['email'])])($users);
```

### CTGFnprog.each :: (MIXED -> VOID) -> ([ARRAY] -> [ARRAY])

Returns a callable that applies a function to each element for side
effects, then returns the original array unchanged.

```php
CTGFnprog::each(fn($r) => sendWelcomeEmail($r['email']))($newUsers);
```

### CTGFnprog.rename :: ARRAY<STRING, STRING> -> ([ARRAY] -> [ARRAY])

Returns a callable that renames fields in each row using an old-to-new
mapping. Fields not in the mapping are preserved with their original key.

```php
CTGFnprog::rename(['created_at' => 'createdAt', 'email' => 'emailAddress'])($users);
```

### CTGFnprog.withField :: STRING, (ARRAY -> MIXED) -> ([ARRAY] -> [ARRAY])

Returns a callable that adds a computed field to each row. The callable
receives the row and its return value is set as the new field. If the
field already exists, it is overwritten.

```php
CTGFnprog::withField('tax', fn($r) => $r['total'] * 0.08)($orders);
```

### CTGFnprog.castField :: STRING, STRING -> ([ARRAY] -> [ARRAY])

Returns a callable that casts a field to a PHP type. Supported types:
`'int'`, `'float'`, `'bool'`, `'string'` (and their aliases `'integer'`,
`'double'`, `'boolean'`). Unrecognized types leave the value unchanged.

```php
CTGFnprog::castField('id', 'int')($rows);
CTGFnprog::castField('price', 'float')($rows);
CTGFnprog::castField('active', 'bool')($rows);
```

### CTGFnprog.chunk :: INT -> ([ARRAY] -> [[ARRAY]])

Returns a callable that splits an array into groups of N elements.
The last group may contain fewer than N elements.

```php
CTGFnprog::chunk(3)([1,2,3,4,5,6,7]);
// [[1,2,3], [4,5,6], [7]]
```

### CTGFnprog.zip :: [ARRAY]... -> ([ARRAY] -> [[MIXED]])

Returns a callable that combines the piped array with the given arrays
element-wise. The piped array elements come first in each tuple. Length
is determined by the shortest array.

```php
CTGFnprog::zip(['a','b','c'])([1,2,3]);
// [[1,'a'], [2,'b'], [3,'c']]
```

---

## Aggregation

Aggregation methods consume an array and return a single value. They are
typically the last step in a pipeline.

### CTGFnprog.sum :: STRING -> ([ARRAY] -> INT|FLOAT)

Returns a callable that sums a numeric field across all rows. Returns 0
for an empty array.

```php
CTGFnprog::sum('total')($orders); // 1547.50
```

### CTGFnprog.avg :: STRING -> ([ARRAY] -> INT|FLOAT)

Returns a callable that averages a numeric field across all rows. Returns
0 for an empty array.

```php
CTGFnprog::avg('total')($orders); // 51.58
```

### CTGFnprog.min :: STRING -> ([ARRAY] -> INT|FLOAT|NULL)

Returns a callable that finds the minimum value of a numeric field.
Returns null for an empty array.

```php
CTGFnprog::min('total')($orders); // 5.99
```

### CTGFnprog.max :: STRING -> ([ARRAY] -> INT|FLOAT|NULL)

Returns a callable that finds the maximum value of a numeric field.
Returns null for an empty array.

```php
CTGFnprog::max('total')($orders); // 299.00
```

### CTGFnprog.count :: VOID -> ([ARRAY] -> INT)

Returns a callable that counts the elements in the array.

```php
CTGFnprog::count()($orders); // 30
```

### CTGFnprog.reduce :: (MIXED, MIXED -> MIXED), MIXED -> ([ARRAY] -> MIXED)

Returns a callable that performs a general-purpose fold over the array.
The callable receives the accumulator and the current row, and returns
the new accumulator. The second argument is the initial accumulator
value. Returns the initial value for an empty array.

```php
CTGFnprog::reduce(function($acc, $row) {
    $acc['count']++;
    $acc['total'] += $row['total'];
    return $acc;
}, ['count' => 0, 'total' => 0])($orders);
```

### CTGFnprog.first :: (MIXED -> BOOL)|NULL -> ([ARRAY] -> MIXED|NULL)

Returns a callable that gets the first element of the array. If a
predicate is provided, returns the first element matching the predicate.
Returns null if the array is empty or no match is found.

```php
CTGFnprog::first()($users);
CTGFnprog::first(fn($r) => $r['role'] === 'admin')($users);
```

### CTGFnprog.last :: (MIXED -> BOOL)|NULL -> ([ARRAY] -> MIXED|NULL)

Returns a callable that gets the last element of the array. If a
predicate is provided, returns the last element matching the predicate.
Returns null if the array is empty or no match is found.

```php
CTGFnprog::last()($users);
CTGFnprog::last(fn($r) => $r['active'])($users);
```

---

## Logic

Logic methods operate on values or predicates for conditional transforms
and predicate composition.

### CTGFnprog.when :: (MIXED -> BOOL), (MIXED -> MIXED) -> (MIXED -> MIXED)

Returns a callable that conditionally applies a function. If the
predicate returns true, the function is applied. Otherwise, the value
passes through unchanged.

```php
CTGFnprog::when(
    fn($rows) => count($rows) > 100,
    CTGFnprog::take(100)
)($users);
```

### CTGFnprog.unless :: (MIXED -> BOOL), (MIXED -> MIXED) -> (MIXED -> MIXED)

Returns a callable that applies the function unless the predicate returns
true. Inverse of `when`.

```php
CTGFnprog::unless(
    fn($rows) => count($rows) === 0,
    CTGFnprog::sortBy('name')
)($users);
```

### CTGFnprog.either :: (MIXED -> BOOL)... -> (MIXED -> BOOL)

Returns a predicate that is true if any of the given predicates are true.
Short-circuits on the first true result.

```php
$isAdminOrEditor = CTGFnprog::either(
    fn($r) => $r['role'] === 'admin',
    fn($r) => $r['role'] === 'editor'
);

CTGFnprog::filter($isAdminOrEditor)($users);
```

### CTGFnprog.all :: (MIXED -> BOOL)... -> (MIXED -> BOOL)

Returns a predicate that is true only if all given predicates are true.
Short-circuits on the first false result.

```php
$isActiveAdmin = CTGFnprog::all(
    fn($r) => $r['active'],
    fn($r) => $r['role'] === 'admin'
);

CTGFnprog::filter($isActiveAdmin)($users);
```

### CTGFnprog.not :: (MIXED -> BOOL) -> (MIXED -> BOOL)

Returns a predicate that negates the given predicate.

```php
$isNotBanned = CTGFnprog::not(fn($r) => $r['banned']);
CTGFnprog::filter($isNotBanned)($users);
```

### CTGFnprog.cond :: [[(MIXED -> BOOL), (MIXED -> MIXED)]] -> (MIXED -> MIXED)

Returns a callable that evaluates an array of `[predicate, transform]`
pairs in order and applies the first matching transform. Returns null if
no predicate matches. Use `always(true)` as the last predicate for a
default case.

```php
$categorize = CTGFnprog::cond([
    [fn($n) => $n >= 90, CTGFnprog::always('A')],
    [fn($n) => $n >= 80, CTGFnprog::always('B')],
    [fn($n) => $n >= 70, CTGFnprog::always('C')],
    [CTGFnprog::always(true), CTGFnprog::always('F')],
]);

$categorize(85); // 'B'
```

---

## Value

Value methods operate on individual values rather than collections.

### CTGFnprog.identity :: VOID -> (MIXED -> MIXED)

Returns a callable that returns whatever it receives unchanged. Useful as
a default transform or placeholder in conditional logic.

```php
CTGFnprog::identity()(42); // 42
```

### CTGFnprog.always :: MIXED -> (MIXED -> MIXED)

Returns a callable that ignores its input and returns a fixed value.
Useful for defaults and as the final case in `cond`. The returned
callable accepts zero or one argument.

```php
CTGFnprog::always('default')('anything'); // 'default'
CTGFnprog::always([])();                  // []
```

### CTGFnprog.tap :: (MIXED -> VOID) -> (MIXED -> MIXED)

Returns a callable that runs a side effect without changing the value
flowing through the pipeline. The callable receives the value, but `tap`
returns the original value regardless of what the callable returns.

```php
$pipeline = CTGFnprog::pipe([
    CTGFnprog::filter(fn($r) => $r['active']),
    CTGFnprog::tap(fn($rows) => error_log("Active: " . count($rows))),
    CTGFnprog::pluck('email'),
]);
```

### CTGFnprog.defaultTo :: MIXED -> (MIXED|NULL -> MIXED)

Returns a callable that substitutes a default when the value is null.
Only replaces `null` — falsy values like `false`, `0`, `''`, and `[]`
pass through unchanged.

```php
CTGFnprog::defaultTo('N/A')(null);    // 'N/A'
CTGFnprog::defaultTo('N/A')('hello'); // 'hello'
CTGFnprog::defaultTo('N/A')(false);   // false
```

### CTGFnprog.coalesce :: MIXED... -> MIXED|NULL

Returns the first non-null argument. Unlike every other method in this
class, `coalesce` does not return a callable — it returns a value
directly. Only `null` is considered empty; falsy values like `false`, `0`,
and `''` are returned.

```php
CTGFnprog::coalesce(null, null, 'fallback'); // 'fallback'
CTGFnprog::coalesce(null, false, 'x');       // false
```
