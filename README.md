# HTTP Query Builder

The HTTP Query Builder is a simple, but efficient PHP function that takes an associative array and returns a URL encoded HTTP query string. The function validates the input data and returns an error message if the input is not an array. It's compatible with PHP 5.6 and onwards.

## Features

- Takes an associative array and converts it into a URL encoded HTTP query string.
- Checks if the input data is an array.
- If the input is not an array, it returns an error message.
- Supports PHP 5.6 and onwards.

## Requirements

- PHP 5.6 or higher.

## Installation

To use the HTTP Query Builder, simply include it in your PHP project:

```php
include_once 'build_http_query_from_array.php';
```

## Usage

First, prepare an associative array that needs to be converted into a URL encoded HTTP query string:

```php
$data = [
    'name' => 'John',
    'age' => 30,
    'city' => 'New York',
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

In this example, the function is called with an associative array as an argument. The returned query string is then printed. If there is an error, it will print the error message instead.

## Errors and Exceptions

The function will return an associative array with an 'error' flag. If there was no error during the function execution, 'error' will be `false` and 'query' will contain the constructed query string. If there was an error, 'error' will be `true` and 'message' will contain a string with an error message.

## Contributing

Contributions are welcome! Please feel free to fork this project and submit your enhancements via a pull request.

## License

The HTTP Query Builder is open-source software licensed under the GNU license.
