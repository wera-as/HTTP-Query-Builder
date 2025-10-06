<?php

declare(strict_types=1);

/**
 * Build an HTTP query string from an (optionally nested) array with security guards.
 *
 * Security / robustness upgrades:
 * - RFC 3986 encoding by default (rawurlencode, spaces => %20) to avoid ambiguity.
 * - Key sorting for deterministic output (can disable).
 * - Depth and pair-count guards to prevent runaway recursion and memory abuse.
 * - Strict typing + input validation; rejects resources/unsupported objects.
 * - Safe delimiter whitelist (& or ;) with graceful fallback.
 * - Consistent scalar normalization (bools, nulls, floats, DateTimeInterface).
 *
 * Back-compat: keeps the original signature. New $options param is optional.
 *
 * @param array  $query_data             Input data.
 * @param string $parent_key             (Internal) Parent key for recursion.
 * @param string $delimiter              Delimiter between pairs. Only "&" and ";" allowed.
 * @param bool   $preserveNumericIndexes Preserve numeric indexes (e.g., preferences[0]=x) instead of [].
 * @param array  $options                Optional behavior tweaks:
 *   - 'encode'            => 'rfc3986'|'rfc1738' (default 'rfc3986')
 *   - 'bool_format'       => 'int'|'word'|'string'   (default 'int') // int: 1/0, word: true/false, string: "true"/"false"
 *   - 'null_format'       => 'omit'|'empty'|'string' (default 'omit') // omit: skip pairs, empty: key=, string: "null"
 *   - 'datetime_format'   => string DateTime format (default DATE_ATOM)
 *   - 'max_depth'         => int (default 50)
 *   - 'max_pairs'         => int (default 10000)
 *   - 'sort_keys'         => bool (default true)
 *
 * @return array ['error' => bool, 'message' => string|null, 'query' => string|null]
 *
 * @example
 * $result = build_http_query_from_array($data);
 * if ($result['error']) { echo $result['message']; } else { echo $result['query']; }
 *
 * @author  WERA AS
 * @version 2.0.0
 */

function build_http_query_from_array(
    array $query_data,
    string $parent_key = '',
    string $delimiter = '&',
    bool $preserveNumericIndexes = false,
    array $options = []
): array {

    $opts = array_merge([
        'encode'          => 'rfc3986',
        'bool_format'     => 'int',
        'null_format'     => 'omit',
        'datetime_format' => DATE_ATOM,
        'max_depth'       => 50,
        'max_pairs'       => 10000,
        'sort_keys'       => true,
    ], $options);

    if (!in_array($delimiter, ['&', ';'], true)) {
        $delimiter = '&';
    }
    if (!in_array($opts['encode'], ['rfc3986', 'rfc1738'], true)) {
        $opts['encode'] = 'rfc3986';
    }
    if (!in_array($opts['bool_format'], ['int', 'word', 'string'], true)) {
        $opts['bool_format'] = 'int';
    }
    if (!in_array($opts['null_format'], ['omit', 'empty', 'string'], true)) {
        $opts['null_format'] = 'omit';
    }
    $maxDepth = max(1, (int)$opts['max_depth']);
    $maxPairs = max(1, (int)$opts['max_pairs']);
    $sortKeys = (bool)$opts['sort_keys'];

    $encodeKey = function (string $s) use ($opts): string {
        return $opts['encode'] === 'rfc1738' ? urlencode($s) : rawurlencode($s);
    };
    $encodeVal = $encodeKey;

    $normalizeScalar = function ($v) use ($opts): ?string {
        if (is_bool($v)) {
            return match ($opts['bool_format']) {
                'word'   => $v ? 'true' : 'false',
                'string' => $v ? 'true' : 'false',
                default  => $v ? '1' : '0',
            };
        }
        if ($v === null) {
            return match ($opts['null_format']) {
                'empty'  => '',
                'string' => 'null',
                'omit'   => null,
            };
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format($opts['datetime_format']);
        }
        if (is_int($v)) {
            return (string)$v;
        }
        if (is_float($v)) {
            $s = number_format($v, 14, '.', '');
            $s = rtrim(rtrim($s, '0'), '.');
            return $s === '' ? '0' : $s;
        }
        if (is_string($v)) {
            return $v;
        }
        if (is_resource($v) || is_object($v)) {
            return null;
        }
        return (string)$v;
    };

    $pairs = [];
    $pairCount = 0;

    $build = function ($data, string $parent, int $depth) use (
        &$build,
        &$pairs,
        &$pairCount,
        $maxDepth,
        $maxPairs,
        $sortKeys,
        $preserveNumericIndexes,
        $encodeKey,
        $encodeVal,
        $normalizeScalar
    ): ?array {
        if ($depth > $maxDepth) {
            return ['error' => true, 'message' => "Max depth of {$maxDepth} exceeded."];
        }

        if (!is_array($data)) {
            return ['error' => true, 'message' => 'Invalid input. Expected an array at current level.'];
        }

        if ($sortKeys) {
            uksort($data, static function ($a, $b) {
                return strcmp((string)$a, (string)$b);
            });
        }

        foreach ($data as $key => $value) {
            $keyStr = (string)$key;
            $encodedKey = $parent === ''
                ? $encodeKey($keyStr)
                : (
                    is_int($key) && !$preserveNumericIndexes
                    ? $parent . '[]'
                    : $parent . '[' . $encodeKey($keyStr) . ']'
                );

            if (is_array($value)) {
                $res = $build($value, $encodedKey, $depth + 1);
                if ($res !== null && $res['error'] ?? false) {
                    return $res;
                }
            } else {
                $normalized = $normalizeScalar($value);

                if ($normalized === null) {
                    if (is_resource($value) || is_object($value)) {
                        return ['error' => true, 'message' => "Invalid type in query data at key '{$keyStr}'."];
                    }
                    continue;
                }

                $pairCount++;
                if ($pairCount > $maxPairs) {
                    return ['error' => true, 'message' => "Max pair count of {$maxPairs} exceeded."];
                }

                $pairs[] = $encodedKey . '=' . $encodeVal($normalized);
            }
        }

        return null;
    };

    $err = $build($query_data, $parent_key, 1);
    if (is_array($err) && ($err['error'] ?? false)) {
        return ['error' => true, 'message' => $err['message'] ?? 'Unknown error', 'query' => null];
    }

    return ['error' => false, 'message' => null, 'query' => implode($delimiter, $pairs)];
}
