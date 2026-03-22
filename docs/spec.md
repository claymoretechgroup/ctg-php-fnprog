# CTGFnprog — Implementation Plan

## Class Design Decision: Static Utility (Not Singleton)

**Choice: Pure static class — no constructor, no instances, no state.**

A singleton implies managed state and a lifecycle (instantiate once, reuse).
CTGFnprog has neither. Every method is a pure factory: it takes configuration
(a key name, a callable, a size) and returns a new callable. There is no
shared state between calls, no configuration to persist, no connection to
manage.

Making this a singleton would add ceremony (`::getInstance()`) for zero
benefit. A static class is the correct model — it's just a namespace for
pure factory functions.

- No `init()` factory method (nothing to instantiate)
- No instance properties (no state)
- No constructor (prevented with `private function __construct() {}`)
- All methods `public static`
- Class declared `final` (no reason to extend a stateless utility)

---

## Class: CTGFnprog

**Namespace:** `CTG\FnProg`
**File:** `src/CTGFnprog.php`

---

## Method Signatures

### Composition

```php
// :: [(MIXED -> MIXED)] -> (MIXED -> MIXED)
// Left-to-right function composition — data flows through functions in listed order
public static function pipe(array $fns): callable;

// :: [(MIXED -> MIXED)] -> (MIXED -> MIXED)
// Right-to-left function composition — traditional mathematical order
public static function compose(array $fns): callable;

// :: (MIXED -> MIXED), MIXED... -> (MIXED -> MIXED)
// Partially apply arguments to a function, returns function accepting remaining args
public static function partial(callable $fn, mixed ...$args): callable;

// :: (MIXED -> MIXED) -> (MIXED -> (MIXED -> MIXED))
// Automatically curry a function into a chain of single-argument functions
public static function curry(callable $fn): callable;
```

### Collections

All collection methods operate on arrays of associative arrays (rows) unless
otherwise noted. Each returns a callable that accepts an array.

```php
// :: STRING -> ([ARRAY] -> [MIXED])
// Extract a single field value from each row
public static function pluck(string $key): callable;

// :: [STRING] -> ([ARRAY] -> [ARRAY])
// Keep only the specified fields from each row
public static function pick(array $keys): callable;

// :: [STRING] -> ([ARRAY] -> [ARRAY])
// Remove the specified fields from each row
public static function omit(array $keys): callable;

// :: STRING -> ([ARRAY] -> ARRAY<STRING|INT, ARRAY>)
// Re-index array using a field's value as the key
public static function keyBy(string $key): callable;

// :: STRING -> ([ARRAY] -> ARRAY<STRING, [ARRAY]>)
// Group rows into buckets by a field value
public static function groupBy(string $key): callable;

// :: STRING, STRING -> ([ARRAY] -> [ARRAY])
// Sort rows by a field value, direction is 'ASC' or 'DESC'
public static function sortBy(string $key, string $dir = 'ASC'): callable;

// :: STRING -> ([ARRAY] -> [ARRAY])
// Deduplicate rows by a field value, keeps first occurrence
public static function uniqueBy(string $key): callable;

// :: VOID -> ([ARRAY] -> [MIXED])
// Flatten one level of nested arrays
public static function flatten(): callable;

// :: STRING, MIXED -> ([ARRAY] -> [ARRAY])
// Keep rows where a field strictly equals a value
public static function where(string $key, mixed $value): callable;

// :: (MIXED -> BOOL) -> ([ARRAY] -> [ARRAY])
// Keep rows where predicate returns true
public static function filter(callable $predicate): callable;

// :: (MIXED -> BOOL) -> ([ARRAY] -> [ARRAY])
// Remove rows where predicate returns true (inverse of filter)
public static function reject(callable $predicate): callable;

// :: INT -> ([ARRAY] -> [ARRAY])
// Take the first N elements
public static function take(int $n): callable;

// :: INT -> ([ARRAY] -> [ARRAY])
// Skip the first N elements
public static function skip(int $n): callable;

// :: (MIXED -> MIXED) -> ([ARRAY] -> [ARRAY])
// Apply a function to each element, return transformed array
public static function map(callable $fn): callable;

// :: (MIXED -> VOID) -> ([ARRAY] -> [ARRAY])
// Apply a function to each element for side effects, return original array unchanged
public static function each(callable $fn): callable;

// :: ARRAY<STRING, STRING> -> ([ARRAY] -> [ARRAY])
// Rename fields in each row using old=>new mapping
public static function rename(array $mapping): callable;

// :: STRING, (ARRAY -> MIXED) -> ([ARRAY] -> [ARRAY])
// Add a computed field to each row
public static function withField(string $name, callable $fn): callable;

// :: STRING, STRING -> ([ARRAY] -> [ARRAY])
// Cast a field to a PHP type ('int', 'float', 'bool', 'string')
public static function castField(string $key, string $type): callable;

// :: INT -> ([ARRAY] -> [[ARRAY]])
// Split array into groups of N elements
public static function chunk(int $size): callable;

// :: [ARRAY]... -> ([ARRAY] -> [[MIXED]])
// Combine multiple arrays element-wise
public static function zip(array ...$arrays): callable;
```

### Aggregation

Aggregation methods consume an array and return a single value. Typically
the last step in a pipeline.

```php
// :: STRING -> ([ARRAY] -> INT|FLOAT)
// Sum a numeric field across all rows
public static function sum(string $key): callable;

// :: STRING -> ([ARRAY] -> INT|FLOAT)
// Average a numeric field across all rows
public static function avg(string $key): callable;

// :: STRING -> ([ARRAY] -> INT|FLOAT)
// Find minimum value of a numeric field
public static function min(string $key): callable;

// :: STRING -> ([ARRAY] -> INT|FLOAT)
// Find maximum value of a numeric field
public static function max(string $key): callable;

// :: VOID -> ([ARRAY] -> INT)
// Count elements in the array
public static function count(): callable;

// :: (MIXED, MIXED -> MIXED), MIXED -> ([ARRAY] -> MIXED)
// General-purpose fold/reduce over the array
public static function reduce(callable $fn, mixed $initial): callable;

// :: (MIXED -> BOOL)|NULL -> ([ARRAY] -> MIXED|NULL)
// Get the first element, optionally matching a predicate. Returns null if no match.
public static function first(?callable $predicate = null): callable;

// :: (MIXED -> BOOL)|NULL -> ([ARRAY] -> MIXED|NULL)
// Get the last element, optionally matching a predicate. Returns null if no match.
public static function last(?callable $predicate = null): callable;
```

### Logic

Logic methods operate on values or predicates for conditional transforms
and predicate composition.

```php
// :: (MIXED -> BOOL), (MIXED -> MIXED) -> (MIXED -> MIXED)
// If predicate returns true, apply function. Otherwise pass value through unchanged.
public static function when(callable $predicate, callable $fn): callable;

// :: (MIXED -> BOOL), (MIXED -> MIXED) -> (MIXED -> MIXED)
// Apply function unless predicate returns true. Inverse of when.
public static function unless(callable $predicate, callable $fn): callable;

// :: (MIXED -> BOOL)... -> (MIXED -> BOOL)
// Compose predicates with OR logic — true if any predicate is true
public static function either(callable ...$predicates): callable;

// :: (MIXED -> BOOL)... -> (MIXED -> BOOL)
// Compose predicates with AND logic — true only if all predicates are true
public static function all(callable ...$predicates): callable;

// :: (MIXED -> BOOL) -> (MIXED -> BOOL)
// Negate a predicate
public static function not(callable $predicate): callable;

// :: [[(MIXED -> BOOL), (MIXED -> MIXED)]] -> (MIXED -> MIXED)
// Pattern matching — evaluate predicate/transform pairs, apply first match
public static function cond(array $pairs): callable;
```

### Value

Value methods operate on individual values rather than collections.

```php
// :: VOID -> (MIXED -> MIXED)
// Returns whatever it receives unchanged
public static function identity(): callable;

// :: MIXED -> (MIXED -> MIXED)
// Ignores input, returns the fixed value
public static function always(mixed $value): callable;

// :: (MIXED -> VOID) -> (MIXED -> MIXED)
// Run side effect without changing the value flowing through the pipeline
public static function tap(callable $fn): callable;

// :: MIXED -> (MIXED|NULL -> MIXED)
// If value is null, return the default instead
public static function defaultTo(mixed $default): callable;

// :: MIXED... -> MIXED|NULL
// Returns the first non-null argument
// NOTE: Unlike other methods, this does NOT return a callable — it returns a value directly
public static function coalesce(mixed ...$values): mixed;
```

---

## Project Structure

```
ctg-php-fnprog/
├── .gitignore
├── composer.json
├── plans/
│   └── implementation-plan.md
├── src/
│   └── CTGFnprog.php
├── tests/
│   ├── CompositionTest.php
│   ├── CollectionTest.php
│   ├── AggregationTest.php
│   ├── LogicTest.php
│   └── ValueTest.php
└── staging/                    # gitignored
    ├── docker-compose.yml
    ├── Makefile
    └── ...
```

---

## Testing Strategy

Tests will use the `ctg-php-test` pipeline framework:

- **Subject**: For most tests, the subject is a test dataset (array of rows)
  or a simple value
- **Stages**: Build callables from CTGFnprog factory methods
- **Asserts**: Verify the callable produces expected output

Example pattern:
```php
CTGTest::init('pipe')
    ->stage('build pipeline', fn($_) => CTGFnprog::pipe([
        CTGFnprog::filter(fn($r) => $r['active']),
        CTGFnprog::pluck('name'),
    ]))
    ->stage('execute', fn($pipeline) => $pipeline($testData))
    ->assert('returns active names', fn($result) => $result, ['Alice', 'Charlie'])
    ->start(null);
```

Tests organized by method group:
1. **CompositionTest** — pipe, compose, partial, curry
2. **CollectionTest** — pluck, pick, omit, keyBy, groupBy, sortBy, uniqueBy,
   flatten, where, filter, reject, take, skip, map, each, rename, withField,
   castField, chunk, zip
3. **AggregationTest** — sum, avg, min, max, count, reduce, first, last
4. **LogicTest** — when, unless, either, all, not, cond
5. **ValueTest** — identity, always, tap, defaultTo, coalesce

---

## Implementation Order

1. **Composition** (pipe, compose, partial, curry) — foundation everything else depends on
2. **Value** (identity, always, tap, defaultTo, coalesce) — simple, used by other groups
3. **Collections** (all 21 methods) — bulk of the library
4. **Aggregation** (sum, avg, min, max, count, reduce, first, last) — depends on collections conceptually
5. **Logic** (when, unless, either, all, not, cond) — predicate composition, ties everything together

---

## Notes

- `coalesce()` is the only method that breaks the "returns a callable" rule —
  it returns a value directly. This is intentional per the spec since it operates
  on its arguments, not on piped input.
- `filter`/`reject`/`map`/`each` reset array keys using `array_values()` to
  maintain clean sequential indexing.
- `sortBy` uses `usort` with spaceship operator for clean comparison.
- `curry` uses `ReflectionFunction` to determine parameter count.
