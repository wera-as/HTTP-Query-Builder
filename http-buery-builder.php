<?php

/**
 * This function builds an HTTP query from an associative array
 *
 * @author WERA AS
 * @version 1.1.1
 *
 * @param array $query_data An associative array from which the HTTP query will be built.
 * 
 * @return array An associative array containing the error status and either the error message or the constructed HTTP query.
 * 
 * @example
 * 
 * $data = [
 *     'name' => 'John',
 *     'age' => 30,
 *     'city' => 'New York',
 * ];
 * 
 * $result = build_http_query_from_array($data);
 * 
 * if ($result['error']) {
 *     echo 'Error: ' . $result['message'];
 * } else {
 *     echo 'Query: ' . $result['query'];
 * }
 * 
 * This example shows how to use the function. It prepares an associative array,
 * calls the function with this array, and prints the returned query string or error message.
 */
function build_http_query_from_array($query_data)
{
    // Check if input is an array
    if (!is_array($query_data)) {
        return [
            'error' => true,
            'message' => 'Invalid input. Expected an array.',
        ];
    }

    $query = [];

    foreach ($query_data as $name => $value) {
        $value = (array) $value;

        array_walk_recursive($value, function ($value) use (&$query, $name) {
            $query[] = urlencode($name) . '=' . urlencode($value);
        });
    }

    // If no error occurred, return the constructed query string
    return [
        'error' => false,
        'query' => implode("&", $query),
    ];
}
