# HTTP Query Builder ![GitHub](https://img.shields.io/badge/version-v1.2.0-blue)
The HTTP Query Builder is a sophisticated PHP function that constructs a URL-encoded HTTP query string from an associative array. This function now supports nested arrays, custom delimiter options, and the ability to preserve numeric indexes in array keys. It validates the input data and returns an error message if the input is not an array. Compatible with PHP 5.6 and onwards.

## Features

- Converts an associative array, including nested arrays, into a URL-encoded HTTP query string.
- Custom delimiter option for query strings.
- Ability to preserve numeric indexes in array keys.
- Enhanced input validation with detailed error reporting.
- Fully compatible with PHP 5.6 and later versions.

## Requirements

- PHP 5.6 or higher.

## Installation

To use the HTTP Query Builder, include it in your PHP project:

```php
include_once 'build_http_query_from_array.php';
```

## Usage

First, prepare an associative array that needs to be converted into a URL encoded HTTP query string:

```php
$data = [
    'user' => [
        'name' => 'John',
        'details' => [
            'age' => 30,
            'city' => 'New York'
        ]
    ],
    'preferences' => ['movies', 'books'],
    'newsletter' => true
];
```

Then, call the `build_http_query_from_array` function with this array:

```php
$result = build_http_query_from_array($data);

if ($result['error']) {
    echo 'Error: ' . $result['message'];
} else {
    echo 'Query: ' . $result['query'];
}
```

In this example, the function takes a complex associative array and outputs a URL-encoded query string. If an error occurs, it prints an error message.

## Errors and Exceptions

The function returns an associative array with an 'error' flag. If no error occurs during execution, 'error' is false, and 'query' contains the constructed query string. In case of an error, 'error' is true, and 'message' contains an error description.

## Contributing

Contributions to enhance functionality or documentation are welcome! Please fork this project and submit your contributions via pull requests.

## License

The HTTP Query Builder is open-source software, licensed under the GNU license.
![License](https://img.shields.io/github/license/wera-as/http-query-builder)
