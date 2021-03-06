<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: cast_channel.proto

namespace Cast_channel\AuthError;

use UnexpectedValueException;

/**
 * Protobuf type <code>cast_channel.AuthError.ErrorType</code>
 */
class ErrorType
{
    /**
     * Generated from protobuf enum <code>INTERNAL_ERROR = 0;</code>
     */
    const INTERNAL_ERROR = 0;
    /**
     * The underlying connection is not TLS
     *
     * Generated from protobuf enum <code>NO_TLS = 1;</code>
     */
    const NO_TLS = 1;

    private static $valueToName = [
        self::INTERNAL_ERROR => 'INTERNAL_ERROR',
        self::NO_TLS => 'NO_TLS',
    ];

    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no name defined for value %s', __CLASS__, $value));
        }
        return self::$valueToName[$value];
    }


    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no value defined for name %s', __CLASS__, $name));
        }
        return constant($const);
    }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ErrorType::class, \Cast_channel\AuthError_ErrorType::class);

