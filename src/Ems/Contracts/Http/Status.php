<?php
/**
 *  * Created by mtils on 15.01.2022 at 10:07.
 **/

namespace Ems\Contracts\Http;

class Status
{
    public const CONTINUE = 100;
    public const SWITCHING_PROTOCOLS = 101;
    public const PROCESSING = 102;            // RFC2518
    public const EARLY_HINTS = 103;           // RFC8297
    public const OK = 200;
    public const CREATED = 201;
    public const ACCEPTED = 202;
    public const NON_AUTHORITATIVE_INFORMATION = 203;
    public const NO_CONTENT = 204;
    public const RESET_CONTENT = 205;
    public const PARTIAL_CONTENT = 206;
    public const MULTI_STATUS = 207;          // RFC4918
    public const ALREADY_REPORTED = 208;      // RFC5842
    public const IM_USED = 226;               // RFC3229
    public const MULTIPLE_CHOICES = 300;
    public const MOVED_PERMANENTLY = 301;
    public const FOUND = 302;
    public const SEE_OTHER = 303;
    public const NOT_MODIFIED = 304;
    public const USE_PROXY = 305;
    public const RESERVED = 306;
    public const TEMPORARY_REDIRECT = 307;
    public const PERMANENTLY_REDIRECT = 308;  // RFC7238
    public const BAD_REQUEST = 400;
    public const UNAUTHORIZED = 401;
    public const PAYMENT_REQUIRED = 402;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const METHOD_NOT_ALLOWED = 405;
    public const NOT_ACCEPTABLE = 406;
    public const PROXY_AUTHENTICATION_REQUIRED = 407;
    public const REQUEST_TIMEOUT = 408;
    public const CONFLICT = 409;
    public const GONE = 410;
    public const LENGTH_REQUIRED = 411;
    public const PRECONDITION_FAILED = 412;
    public const REQUEST_ENTITY_TOO_LARGE = 413;
    public const REQUEST_URI_TOO_LONG = 414;
    public const UNSUPPORTED_MEDIA_TYPE = 415;
    public const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const EXPECTATION_FAILED = 417;
    public const I_AM_A_TEAPOT = 418;                                               // RFC2324
    public const MISDIRECTED_REQUEST = 421;                                         // RFC7540
    public const UNPROCESSABLE_ENTITY = 422;                                        // RFC4918
    public const LOCKED = 423;                                                      // RFC4918
    public const FAILED_DEPENDENCY = 424;                                           // RFC4918


    private static $phrases = [
        self::CONTINUE => 'Continue',
        self::SWITCHING_PROTOCOLS => 'Switching Protocols',
        self::PROCESSING => 'Processing',
        self::EARLY_HINTS => 'Early Hints',
        self::OK => 'OK',
        self::CREATED => 'Created',
        self::ACCEPTED => 'Accepted',
        self::NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information',
        self::NO_CONTENT => 'No Content',
        self::RESET_CONTENT => 'Reset Content',
        self::PARTIAL_CONTENT => 'Partial Content',
        self::MULTI_STATUS => '',
        self::ALREADY_REPORTED => '',
        self::IM_USED => 'IM Used',
        self::MULTIPLE_CHOICES => 'Multiple Choices',
        self::MOVED_PERMANENTLY => 'Moved Permanently',
        self::FOUND => 'Moved Temporarily',
        self::SEE_OTHER => 303,
        self::NOT_MODIFIED => 304,
        self::USE_PROXY => 305,
        self::RESERVED => 306,
        self::TEMPORARY_REDIRECT => 307,
        self::PERMANENTLY_REDIRECT => 308,
        self::BAD_REQUEST => 400,
        self::UNAUTHORIZED => 401,
        self::PAYMENT_REQUIRED => 402,
        self::FORBIDDEN => 403,
        self::NOT_FOUND => 404,
        self::METHOD_NOT_ALLOWED => 405,
        self::NOT_ACCEPTABLE => 406,
        self::PROXY_AUTHENTICATION_REQUIRED => 407,
        self::REQUEST_TIMEOUT => 408,
        self::CONFLICT => 409,
        self::GONE => 410,
        self::LENGTH_REQUIRED => 411,
        self::PRECONDITION_FAILED => 412,
        self::REQUEST_ENTITY_TOO_LARGE => 413,
        self::REQUEST_URI_TOO_LONG => 414,
        self::UNSUPPORTED_MEDIA_TYPE => 415,
        self::REQUESTED_RANGE_NOT_SATISFIABLE => 416,
        self::EXPECTATION_FAILED => 417,
        self::I_AM_A_TEAPOT => 418,
        self::MISDIRECTED_REQUEST => 421,
        self::UNPROCESSABLE_ENTITY => 422,
        self::LOCKED => 423,
        self::FAILED_DEPENDENCY => 424
    ];

    public static function code(string $phrase) : int
    {

    }

    public static function phrase(int $code) : string
    {

    }

    public static function exists(int $status)
    {

    }
}