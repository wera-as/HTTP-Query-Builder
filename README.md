# HTTP Query Builder ![GitHub](https://img.shields.io/badge/version-v2.0.0-blue)

The HTTP Query Builder is a robust PHP utility that constructs a URL-encoded HTTP query string from an associative (optionally nested) array.

**New in v2.0.0**
- RFC 3986 encoding by default (`%20` for spaces) with optional RFC 1738 (`+`) mode.
- Deterministic output (key sorting) for stable signatures/caching.
- Safety guards: maximum recursion depth and maximum pair count.
- Flexible scalar handling: `bool`, `null`, `float`, and `DateTimeInterface` (format configurable).
- Delimiter whitelist (`&` or `;`) to avoid injection via custom delimiters.
- Optionally preserve numeric indexes (`items[0]=x`) or collapse to `items[]=x`.
- Clear, structured error messages while retaining the original return shape.



## Features

- Converts associative arrays (including nested arrays) into a URL-encoded HTTP query string.
- **Encoding modes:** RFC 3986 (default) or RFC 1738.
- **Deterministic ordering:** Optional key sorting for stable output across runs.
- **Security limits:** Guard against deeply nested or massive payloads (`max_depth`, `max_pairs`).
- **Type handling:** Booleans, `null`, floats (locale-independent), `DateTimeInterface`.
- **Delimiter control:** Use `&` (default) or `;`.
- **Numeric indexes:** Preserve or collapse numeric keys.
- Enhanced input validation with clear error reporting.

## Requirements

- **PHP 8.0 or higher.**

## Installation

Include the function in your project:

```php
include_once 'build_http_query_from_array.php';
````

## Function Signature (v2.0.0)

```php
/**
 * build_http_query_from_array(
 *   array  $query_data,
 *   string $parent_key = '',
 *   string $delimiter = '&',                 // '&' or ';'
 *   bool   $preserveNumericIndexes = false,  // keep numeric keys as [0], [1], ...
 *   array  $options = []                     // behavior tweaks
 * ): array
 *
 * $options = [
 *   'encode'          => 'rfc3986'|'rfc1738',   // default: 'rfc3986'
 *   'bool_format'     => 'int'|'word'|'string', // default: 'int' (1/0)
 *   'null_format'     => 'omit'|'empty'|'string', // default: 'omit'
 *   'datetime_format' => string,                // default: DATE_ATOM
 *   'max_depth'       => int,                   // default: 50
 *   'max_pairs'       => int,                   // default: 10000
 *   'sort_keys'       => bool,                  // default: true
 * ];
 *
 * @return array ['error'=>bool, 'message'=>?string, 'query'=>?string]
 */
```



## Usage Examples (with results)

### 1) Basic usage (RFC 3986, sorted keys)

```php
$data = [
    'user' => [
        'name'    => 'John',
        'details' => [
            'age'  => 30,
            'city' => 'New York'
        ]
    ],
    'preferences' => ['movies', 'books'],
    'newsletter'  => true
];

$result = build_http_query_from_array($data);

echo $result['error'] ? 'Error: ' . $result['message'] : $result['query'];
```

**Result:**

```
newsletter=1&preferences%5B0%5D=movies&preferences%5B1%5D=books&user%5Bdetails%5D%5Bage%5D=30&user%5Bdetails%5D%5Bcity%5D=New%20York&user%5Bname%5D=John
```

> Notes: Keys are sorted for deterministic output; spaces encoded as `%20` (RFC 3986).



### 2) RFC 1738 encoding (`+` for spaces)

```php
$data = ['q' => 'New York pizza', 'page' => 1];

$res = build_http_query_from_array($data, '', '&', false, [
    'encode'    => 'rfc1738',
    'sort_keys' => true,
]);

echo $res['query'];
```

**Result:**

```
page=1&q=New+York+pizza
```



### 3) Preserve numeric indexes vs. bracket arrays

```php
$data = ['tags' => ['php', 'curl', 'http']];

$res1 = build_http_query_from_array($data, '', '&', true);  // preserve indexes
$res2 = build_http_query_from_array($data, '', '&', false); // collapse to []

echo "Preserve: {$res1['query']}\n";
echo "Collapse: {$res2['query']}\n";
```

**Result:**

```
Preserve: tags%5B0%5D=php&tags%5B1%5D=curl&tags%5B2%5D=http
Collapse: tags%5B%5D=php&tags%5B%5D=curl&tags%5B%5D=http
```



### 4) DateTimeInterface, float normalization, and booleans

```php
$data = [
    'when'   => new DateTimeImmutable('2025-10-06T09:00:00+02:00'),
    'price'  => 1234.5,
    'active' => false,
];

$res = build_http_query_from_array($data, '', '&', false, [
    'datetime_format' => DATE_ATOM,   // 2025-10-06T09:00:00+02:00
    'bool_format'     => 'word',      // true/false
]);

echo $res['query'];
```

**Result:**

```
active=false&price=1234.5&when=2025-10-06T09%3A00%3A00%2B02%3A00
```



### 5) `null` handling

```php
$data = ['a' => null, 'b' => 1, 'c' => null];

$resOmit  = build_http_query_from_array($data, '', '&', false, ['null_format' => 'omit']);
$resEmpty = build_http_query_from_array($data, '', '&', false, ['null_format' => 'empty']);
$resText  = build_http_query_from_array($data, '', '&', false, ['null_format' => 'string']);

echo $resOmit['query']  . "\n";
echo $resEmpty['query'] . "\n";
echo $resText['query']  . "\n";
```

**Result:**

```
b=1
a=&b=1&c=
a=null&b=1&c=null
```



### 6) Custom delimiter (`;`) and disabled sorting

```php
$data = ['b' => 2, 'a' => 1];

$res = build_http_query_from_array($data, '', ';', false, [
    'sort_keys' => false,
]);

echo $res['query'];
```

**Result:**

```
b=2;a=1
```



### 7) Deterministic output for signing

```php
$data = ['z' => 'last', 'a' => 'first', 'm' => 'mid'];

$res = build_http_query_from_array($data, '', '&', false, [
    'sort_keys' => true,
]);

$query = $res['query'];        // a=first&m=mid&z=last
$signature = hash_hmac('sha256', $query, 'secret');
```

**Result (query):**

```
a=first&m=mid&z=last
```



### 8) Guard rails: depth & pair limits (error example)

```php
$data = ['a' => ['b' => ['c' => ['d' => ['e' => 'x']]]]]; // depth = 5

$res = build_http_query_from_array($data, '', '&', false, [
    'max_depth' => 3,
]);

if ($res['error']) {
    echo 'Error: ' . $res['message'];
}
```

**Result:**

```
Error: Max depth of 3 exceeded.
```



## Errors and Exceptions

The function returns an associative array with an `error` flag. If no error occurs, `error` is `false` and `query` contains the constructed query string. In case of an error, `error` is `true` and `message` contains a human-readable description.



## Contributing

Contributions to enhance functionality or documentation are welcome! Please fork this project and submit your improvements via pull request.



## License

The HTTP Query Builder is open-source software, licensed under the GNU license.

![License](https://img.shields.io/github/license/wera-as/http-query-builder)

