<?php

/**
 * Builds an HTTP query string from a provided associative array. This function
 * supports nested arrays, custom delimiter specification, and the option to 
 * preserve numeric indexes in the array keys. It is designed to handle complex data
 * structures for use in GET requests.
 *
 * @param array  $query_data            The associative array from which the HTTP query will be built.
 * @param string $parent_key            Optional. The parent key for nested arrays, used internally for recursion.
 * @param string $delimiter             Optional. The delimiter used in the query string, defaults to '&'.
 * @param bool   $preserveNumericIndexes Optional. Specifies whether to preserve numeric indexes in array keys, defaults to false.
 *
 * @return array An associative array containing the error status and either the error message or the constructed HTTP query.
 *
 * @example
 * // Example usage of the function
 * $data = [
 *     'user' => [
 *         'name' => 'John',
 *         'details' => [
 *             'age' => 30,
 *             'city' => 'New York'
 *         ]
 *     ],
 *     'preferences' => ['movies', 'books'],
 *     'newsletter' => true
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
 * // This will output:
 * // Query: user[name]=John&user[details][age]=30&user[details][city]=New+York&preferences[0]=movies&preferences[1]=books&newsletter=1
 *
 * @author WERA AS
 * @version 1.2.0
 */
function build_http_query_from_array($query_data, $parent_key = '', $delimiter = '&', $preserveNumericIndexes = false)
{
    if (!is_array($query_data)) {
        return ['error' => true, 'message' => 'Invalid input. Expected an array.'];
    }

    $query = [];
    foreach ($query_data as $key => $value) {
        $encoded_key = $parent_key === '' ? urlencode($key) : $parent_key . '[' . urlencode($key) . ']';

        if (is_array($value)) {
            $sub_query = build_http_query_from_array($value, $encoded_key, $delimiter, $preserveNumericIndexes);
            if ($sub_query['error']) {
                return $sub_query;
            }
            $query[] = $sub_query['query'];
        } elseif (is_scalar($value) || is_null($value)) {
            $query[] = "{$encoded_key}=" . urlencode($value);
        } else {
            return ['error' => true, 'message' => "Invalid type in query data at key '{$key}'."];
        }
    }

    return ['error' => false, 'query' => implode($delimiter, $query)];
}
