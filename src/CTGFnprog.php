<?php
declare(strict_types=1);

namespace CTG\FnProg;

// Functional programming primitives — composable, pipeline-friendly operations
final class CTGFnprog {

    // Prevent instantiation — pure static utility class
    private function __construct() {}

    /**
     *
     * Composition
     *
     */

    // :: [(MIXED -> MIXED)] -> (MIXED -> MIXED)
    // Left-to-right function composition — data flows through functions in listed order
    public static function pipe(array $fns): callable {
        return function(mixed $value) use ($fns): mixed {
            $result = $value;
            foreach ($fns as $fn) {
                $result = $fn($result);
            }
            return $result;
        };
    }

    // :: [(MIXED -> MIXED)] -> (MIXED -> MIXED)
    // Right-to-left function composition — traditional mathematical order
    public static function compose(array $fns): callable {
        return self::pipe(array_reverse($fns));
    }

    // :: (MIXED -> MIXED), MIXED... -> (MIXED -> MIXED)
    // Partially apply arguments to a function, returns function accepting remaining args
    public static function partial(callable $fn, mixed ...$args): callable {
        return function(mixed ...$rest) use ($fn, $args): mixed {
            return $fn(...$args, ...$rest);
        };
    }

    // :: (MIXED -> MIXED) -> (MIXED -> (MIXED -> MIXED))
    // Automatically curry a function into a chain of single-argument functions
    public static function curry(callable $fn): callable {
        $arity = (new \ReflectionFunction(\Closure::fromCallable($fn)))->getNumberOfParameters();
        if ($arity <= 1) {
            return $fn;
        }
        $accumulate = function(array $collected) use ($fn, $arity, &$accumulate): callable {
            return function(mixed ...$args) use ($fn, $arity, $collected, $accumulate): mixed {
                $all = array_merge($collected, $args);
                if (count($all) >= $arity) {
                    return $fn(...$all);
                }
                return $accumulate($all);
            };
        };
        return $accumulate([]);
    }

    /**
     *
     * Collections
     *
     */

    // :: STRING -> ([ARRAY] -> [MIXED])
    // Extract a single field value from each row
    public static function pluck(string $key): callable {
        return function(array $rows) use ($key): array {
            return array_map(fn($row) => $row[$key] ?? null, $rows);
        };
    }

    // :: [STRING] -> ([ARRAY] -> [ARRAY])
    // Keep only the specified fields from each row
    public static function pick(array $keys): callable {
        return function(array $rows) use ($keys): array {
            return array_map(function($row) use ($keys) {
                $result = [];
                foreach ($keys as $key) {
                    if (array_key_exists($key, $row)) {
                        $result[$key] = $row[$key];
                    }
                }
                return $result;
            }, $rows);
        };
    }

    // :: [STRING] -> ([ARRAY] -> [ARRAY])
    // Remove the specified fields from each row
    public static function omit(array $keys): callable {
        return function(array $rows) use ($keys): array {
            return array_map(function($row) use ($keys) {
                foreach ($keys as $key) {
                    unset($row[$key]);
                }
                return $row;
            }, $rows);
        };
    }

    // :: STRING -> ([ARRAY] -> ARRAY<STRING|INT, ARRAY>)
    // Re-index array using a field's value as the key
    public static function keyBy(string $key): callable {
        return function(array $rows) use ($key): array {
            $result = [];
            foreach ($rows as $row) {
                $result[$row[$key] ?? ''] = $row;
            }
            return $result;
        };
    }

    // :: STRING -> ([ARRAY] -> ARRAY<STRING, [ARRAY]>)
    // Group rows into buckets by a field value
    public static function groupBy(string $key): callable {
        return function(array $rows) use ($key): array {
            $result = [];
            foreach ($rows as $row) {
                $result[$row[$key] ?? ''][] = $row;
            }
            return $result;
        };
    }

    // :: STRING, STRING -> ([ARRAY] -> [ARRAY])
    // Sort rows by a field value, direction is 'ASC' or 'DESC'
    public static function sortBy(string $key, string $dir = 'ASC'): callable {
        return function(array $rows) use ($key, $dir): array {
            usort($rows, function($a, $b) use ($key, $dir) {
                $cmp = ($a[$key] ?? null) <=> ($b[$key] ?? null);
                return strtoupper($dir) === 'DESC' ? -$cmp : $cmp;
            });
            return $rows;
        };
    }

    // :: STRING -> ([ARRAY] -> [ARRAY])
    // Deduplicate rows by a field value, keeps first occurrence
    public static function uniqueBy(string $key): callable {
        return function(array $rows) use ($key): array {
            $seen = [];
            $result = [];
            foreach ($rows as $row) {
                $val = $row[$key] ?? null;
                if (!in_array($val, $seen, true)) {
                    $seen[] = $val;
                    $result[] = $row;
                }
            }
            return $result;
        };
    }

    // :: VOID -> ([ARRAY] -> [MIXED])
    // Flatten one level of nested arrays
    public static function flatten(): callable {
        return function(array $arrays): array {
            $result = [];
            foreach ($arrays as $item) {
                if (is_array($item)) {
                    foreach ($item as $sub) {
                        $result[] = $sub;
                    }
                } else {
                    $result[] = $item;
                }
            }
            return $result;
        };
    }

    // :: STRING, MIXED -> ([ARRAY] -> [ARRAY])
    // Keep rows where a field strictly equals a value
    public static function where(string $key, mixed $value): callable {
        return function(array $rows) use ($key, $value): array {
            return array_values(array_filter($rows, fn($row) => ($row[$key] ?? null) === $value));
        };
    }

    // :: (MIXED -> BOOL) -> ([ARRAY] -> [ARRAY])
    // Keep rows where predicate returns true
    public static function filter(callable $predicate): callable {
        return function(array $rows) use ($predicate): array {
            return array_values(array_filter($rows, $predicate));
        };
    }

    // :: (MIXED -> BOOL) -> ([ARRAY] -> [ARRAY])
    // Remove rows where predicate returns true (inverse of filter)
    public static function reject(callable $predicate): callable {
        return function(array $rows) use ($predicate): array {
            return array_values(array_filter($rows, fn($row) => !$predicate($row)));
        };
    }

    // :: INT -> ([ARRAY] -> [ARRAY])
    // Take the first N elements
    public static function take(int $n): callable {
        return function(array $rows) use ($n): array {
            return array_slice($rows, 0, max(0, $n));
        };
    }

    // :: INT -> ([ARRAY] -> [ARRAY])
    // Skip the first N elements
    public static function skip(int $n): callable {
        return function(array $rows) use ($n): array {
            return array_values(array_slice($rows, max(0, $n)));
        };
    }

    // :: (MIXED -> MIXED) -> ([ARRAY] -> [ARRAY])
    // Apply a function to each element, return transformed array
    public static function map(callable $fn): callable {
        return function(array $rows) use ($fn): array {
            return array_map($fn, $rows);
        };
    }

    // :: (MIXED -> VOID) -> ([ARRAY] -> [ARRAY])
    // Apply a function to each element for side effects, return original array unchanged
    public static function each(callable $fn): callable {
        return function(array $rows) use ($fn): array {
            foreach ($rows as $row) {
                $fn($row);
            }
            return $rows;
        };
    }

    // :: ARRAY<STRING, STRING> -> ([ARRAY] -> [ARRAY])
    // Rename fields in each row using old=>new mapping
    public static function rename(array $mapping): callable {
        return function(array $rows) use ($mapping): array {
            return array_map(function($row) use ($mapping) {
                $result = [];
                foreach ($row as $key => $value) {
                    $newKey = $mapping[$key] ?? $key;
                    $result[$newKey] = $value;
                }
                return $result;
            }, $rows);
        };
    }

    // :: STRING, (ARRAY -> MIXED) -> ([ARRAY] -> [ARRAY])
    // Add a computed field to each row
    public static function withField(string $name, callable $fn): callable {
        return function(array $rows) use ($name, $fn): array {
            return array_map(function($row) use ($name, $fn) {
                $row[$name] = $fn($row);
                return $row;
            }, $rows);
        };
    }

    // :: STRING, STRING -> ([ARRAY] -> [ARRAY])
    // Cast a field to a PHP type ('int', 'float', 'bool', 'string')
    public static function castField(string $key, string $type): callable {
        return function(array $rows) use ($key, $type): array {
            return array_map(function($row) use ($key, $type) {
                if (!array_key_exists($key, $row)) {
                    return $row;
                }
                $row[$key] = match($type) {
                    'int', 'integer' => (int) $row[$key],
                    'float', 'double' => (float) $row[$key],
                    'bool', 'boolean' => (bool) $row[$key],
                    'string' => (string) $row[$key],
                    default => $row[$key],
                };
                return $row;
            }, $rows);
        };
    }

    // :: INT -> ([ARRAY] -> [[ARRAY]])
    // Split array into groups of N elements
    public static function chunk(int $size): callable {
        return function(array $rows) use ($size): array {
            if ($size <= 0) {
                return [];
            }
            return array_chunk($rows, $size);
        };
    }

    // :: [ARRAY]... -> ([ARRAY] -> [[MIXED]])
    // Combine multiple arrays element-wise
    public static function zip(array ...$arrays): callable {
        return function(array $first) use ($arrays): array {
            $all = array_map('array_values', array_merge([$first], $arrays));
            $len = min(array_map('count', $all));
            $result = [];
            for ($i = 0; $i < $len; $i++) {
                $tuple = [];
                foreach ($all as $arr) {
                    $tuple[] = $arr[$i];
                }
                $result[] = $tuple;
            }
            return $result;
        };
    }

    /**
     *
     * Aggregation
     *
     */

    // :: STRING -> ([ARRAY] -> INT|FLOAT)
    // Sum a numeric field across all rows
    public static function sum(string $key): callable {
        return function(array $rows) use ($key): int|float {
            return array_sum(array_column($rows, $key));
        };
    }

    // :: STRING -> ([ARRAY] -> INT|FLOAT)
    // Average a numeric field across all rows
    public static function avg(string $key): callable {
        return function(array $rows) use ($key): int|float {
            if (empty($rows)) {
                return 0;
            }
            $values = array_column($rows, $key);
            return array_sum($values) / count($values);
        };
    }

    // :: STRING -> ([ARRAY] -> INT|FLOAT|NULL)
    // Find minimum value of a numeric field
    public static function min(string $key): callable {
        return function(array $rows) use ($key): int|float|null {
            if (empty($rows)) {
                return null;
            }
            return min(array_column($rows, $key));
        };
    }

    // :: STRING -> ([ARRAY] -> INT|FLOAT|NULL)
    // Find maximum value of a numeric field
    public static function max(string $key): callable {
        return function(array $rows) use ($key): int|float|null {
            if (empty($rows)) {
                return null;
            }
            return max(array_column($rows, $key));
        };
    }

    // :: VOID -> ([ARRAY] -> INT)
    // Count elements in the array
    public static function count(): callable {
        return function(array $rows): int {
            return \count($rows);
        };
    }

    // :: (MIXED, MIXED -> MIXED), MIXED -> ([ARRAY] -> MIXED)
    // General-purpose fold/reduce over the array
    public static function reduce(callable $fn, mixed $initial): callable {
        return function(array $rows) use ($fn, $initial): mixed {
            return array_reduce($rows, $fn, $initial);
        };
    }

    // :: (MIXED -> BOOL)|NULL -> ([ARRAY] -> MIXED|NULL)
    // Get the first element, optionally matching a predicate. Returns null if no match.
    public static function first(?callable $predicate = null): callable {
        return function(array $rows) use ($predicate): mixed {
            if ($predicate === null) {
                return empty($rows) ? null : $rows[array_key_first($rows)];
            }
            foreach ($rows as $row) {
                if ($predicate($row)) {
                    return $row;
                }
            }
            return null;
        };
    }

    // :: (MIXED -> BOOL)|NULL -> ([ARRAY] -> MIXED|NULL)
    // Get the last element, optionally matching a predicate. Returns null if no match.
    public static function last(?callable $predicate = null): callable {
        return function(array $rows) use ($predicate): mixed {
            if ($predicate === null) {
                return empty($rows) ? null : $rows[array_key_last($rows)];
            }
            $match = null;
            foreach ($rows as $row) {
                if ($predicate($row)) {
                    $match = $row;
                }
            }
            return $match;
        };
    }

    /**
     *
     * Logic
     *
     */

    // :: (MIXED -> BOOL), (MIXED -> MIXED) -> (MIXED -> MIXED)
    // If predicate returns true, apply function. Otherwise pass value through unchanged.
    public static function when(callable $predicate, callable $fn): callable {
        return function(mixed $value) use ($predicate, $fn): mixed {
            return $predicate($value) ? $fn($value) : $value;
        };
    }

    // :: (MIXED -> BOOL), (MIXED -> MIXED) -> (MIXED -> MIXED)
    // Apply function unless predicate returns true. Inverse of when.
    public static function unless(callable $predicate, callable $fn): callable {
        return function(mixed $value) use ($predicate, $fn): mixed {
            return $predicate($value) ? $value : $fn($value);
        };
    }

    // :: (MIXED -> BOOL)... -> (MIXED -> BOOL)
    // Compose predicates with OR logic — true if any predicate is true
    public static function either(callable ...$predicates): callable {
        return function(mixed $value) use ($predicates): bool {
            foreach ($predicates as $pred) {
                if ($pred($value)) {
                    return true;
                }
            }
            return false;
        };
    }

    // :: (MIXED -> BOOL)... -> (MIXED -> BOOL)
    // Compose predicates with AND logic — true only if all predicates are true
    public static function all(callable ...$predicates): callable {
        return function(mixed $value) use ($predicates): bool {
            foreach ($predicates as $pred) {
                if (!$pred($value)) {
                    return false;
                }
            }
            return true;
        };
    }

    // :: (MIXED -> BOOL) -> (MIXED -> BOOL)
    // Negate a predicate
    public static function not(callable $predicate): callable {
        return function(mixed $value) use ($predicate): bool {
            return !$predicate($value);
        };
    }

    // :: [[(MIXED -> BOOL), (MIXED -> MIXED)]] -> (MIXED -> MIXED)
    // Pattern matching — evaluate predicate/transform pairs, apply first match
    public static function cond(array $pairs): callable {
        return function(mixed $value) use ($pairs): mixed {
            foreach ($pairs as [$predicate, $transform]) {
                if ($predicate($value)) {
                    return $transform($value);
                }
            }
            return null;
        };
    }

    /**
     *
     * Value
     *
     */

    // :: VOID -> (MIXED -> MIXED)
    // Returns whatever it receives unchanged
    public static function identity(): callable {
        return function(mixed $value): mixed {
            return $value;
        };
    }

    // :: MIXED -> (MIXED -> MIXED)
    // Ignores input, returns the fixed value
    public static function always(mixed $value): callable {
        return function(mixed $_ = null) use ($value): mixed {
            return $value;
        };
    }

    // :: (MIXED -> VOID) -> (MIXED -> MIXED)
    // Run side effect without changing the value flowing through the pipeline
    public static function tap(callable $fn): callable {
        return function(mixed $value) use ($fn): mixed {
            $fn($value);
            return $value;
        };
    }

    // :: MIXED -> (MIXED|NULL -> MIXED)
    // If value is null, return the default instead
    public static function defaultTo(mixed $default): callable {
        return function(mixed $value) use ($default): mixed {
            return $value === null ? $default : $value;
        };
    }

    // :: MIXED... -> MIXED|NULL
    // Returns the first non-null argument
    // NOTE: Unlike other methods, this does NOT return a callable — it returns a value directly
    public static function coalesce(mixed ...$values): mixed {
        foreach ($values as $val) {
            if ($val !== null) {
                return $val;
            }
        }
        return null;
    }
}
