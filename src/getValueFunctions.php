<?php
/**
 * Created by PhpStorm.
 * User: jasondent
 * Date: 13/09/15
 * Time: 12:59
 */

namespace Revinate\GetterSetter;

use Revinate\GetterSetter\util as util;

/**
 * @param array|object    $doc
 * @param string|string[] $fieldPath
 * @param mixed           $default
 * @param string          $pathSeparator
 * @return mixed|null
 * @throws UnableToGetFieldException
 */
function get($doc, $fieldPath, $default = null, $pathSeparator = '.') {
    $path = is_array($fieldPath) || ($fieldPath instanceof \ArrayAccess)
        ? $fieldPath
        : explode($pathSeparator, $fieldPath);
    return getValueByArrayPath($doc, $path, $default);
}

/**
 * @param array|object $doc
 * @param string       $fieldName
 * @param null|mixed   $default
 * @return mixed|null
 * @throws UnableToGetFieldException
 */
function getValue($doc, $fieldName, $default = null) {
    if ($doc instanceof GetSetInterface) {
        return $doc->getValue($fieldName, $default);
    }
    if (is_array($doc) || $doc instanceof \ArrayAccess) {
        return (isset($doc[$fieldName]) || array_key_exists($fieldName, $doc)) ? $doc[$fieldName] : $default;
    }
    if (is_object($doc)) {
        if (isset($doc->{$fieldName})) {
            return $doc->{$fieldName};
        }
        // Does the property exist?
        $fields = get_object_vars($doc);
        if (array_key_exists($fieldName, $fields)) {
            return $fields[$fieldName];
        }

        // Try using getters
        $fieldNameCamel = util\toCamelCase($fieldName);
        $getters = array(
            $fieldName, 'get'.$fieldNameCamel, 'is'.$fieldNameCamel, 'has'.$fieldNameCamel, $fieldNameCamel
        );
        foreach ($getters as $methodName) {
            if (method_exists($doc, $methodName)) {
                return $doc->{$methodName}();
            }
        }

        // Try the magic methods __get when __isset doesn't exist
        if (method_exists($doc, '__get') && ! method_exists($doc, '__isset')) {
            return $doc->{$fieldName};
        }

        return $default;
    }

    // Special case for null, we want to return the default in this case.
    if (is_null($doc)) {
        return $default;
    }

    throw new UnableToGetFieldException("Unable to get field: '$fieldName'");
}

/**
 * @param array|object $doc
 * @param string[]     $fieldPath
 * @param mixed        $default
 * @return mixed|null
 * @throws UnableToGetFieldException
 */
function getValueByArrayPath($doc, $fieldPath, $default = null) {
    $notFound = (object) array();
    $value = $doc;

    foreach ($fieldPath as $fieldName) {
        $value = getValue($value, $fieldName, $notFound);
    }

    if ($value === $notFound) {
        $value = $default;
    }
    return $value;
}