<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: cast_channel.proto

namespace GPBMetadata;

class CastChannel
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        $pool->internalAddGeneratedFile(
            '
�
cast_channel.protocast_channel"�
CastMessageC
protocol_version (2).cast_channel.CastMessage.ProtocolVersion
	source_id (	
destination_id (	
	namespace (	;
payload_type (2%.cast_channel.CastMessage.PayloadType
payload_utf8 (	H �
payload_binary (H�"!
ProtocolVersion

CASTV2_1_0 "%
PayloadType

STRING 

BINARYB
_payload_utf8B
_payload_binary"
AuthChallenge"U
AuthResponse
	signature (
client_auth_certificate (
	client_ca ("o
	AuthError5

error_type (2!.cast_channel.AuthError.ErrorType"+
	ErrorType
INTERNAL_ERROR 

NO_TLS"�
DeviceAuthMessage3
	challenge (2.cast_channel.AuthChallengeH �1
response (2.cast_channel.AuthResponseH�+
error (2.cast_channel.AuthErrorH�B

_challengeB
	_responseB
_errorbproto3'
        , true);

        static::$is_initialized = true;
    }
}

