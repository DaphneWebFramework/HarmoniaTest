<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Http\StatusCode;

#[CoversClass(StatusCode::class)]
class StatusCodeTest extends TestCase
{
    #[DataProvider('caseDataProvider')]
    function testCase(StatusCode $statusCode, int $code)
    {
        $this->assertSame($statusCode, StatusCode::from($code));
    }

    #region Data Providers -----------------------------------------------------

    static function caseDataProvider()
    {
        return [
            // 1xx - Informational
            'Continue_'                     => [StatusCode::Continue_                    , 100],
            'SwitchingProtocols'            => [StatusCode::SwitchingProtocols           , 101],
            'Processing'                    => [StatusCode::Processing                   , 102],
            'EarlyHints'                    => [StatusCode::EarlyHints                   , 103],

            // 2xx - Successful
            'OK'                            => [StatusCode::OK                           , 200],
            'Created'                       => [StatusCode::Created                      , 201],
            'Accepted'                      => [StatusCode::Accepted                     , 202],
            'NonAuthoritativeInformation'   => [StatusCode::NonAuthoritativeInformation  , 203],
            'NoContent'                     => [StatusCode::NoContent                    , 204],
            'ResetContent'                  => [StatusCode::ResetContent                 , 205],
            'PartialContent'                => [StatusCode::PartialContent               , 206],
            'MultiStatus'                   => [StatusCode::MultiStatus                  , 207],
            'AlreadyReported'               => [StatusCode::AlreadyReported              , 208],
            'IMUsed'                        => [StatusCode::IMUsed                       , 226],

            // 3xx - Redirection
            'MultipleChoices'               => [StatusCode::MultipleChoices              , 300],
            'MovedPermanently'              => [StatusCode::MovedPermanently             , 301],
            'Found'                         => [StatusCode::Found                        , 302],
            'SeeOther'                      => [StatusCode::SeeOther                     , 303],
            'NotModified'                   => [StatusCode::NotModified                  , 304],
            'UseProxy'                      => [StatusCode::UseProxy                     , 305],
            'TemporaryRedirect'             => [StatusCode::TemporaryRedirect            , 307],
            'PermanentRedirect'             => [StatusCode::PermanentRedirect            , 308],

            // 4xx - Client Error
            'BadRequest'                    => [StatusCode::BadRequest                   , 400],
            'Unauthorized'                  => [StatusCode::Unauthorized                 , 401],
            'PaymentRequired'               => [StatusCode::PaymentRequired              , 402],
            'Forbidden'                     => [StatusCode::Forbidden                    , 403],
            'NotFound'                      => [StatusCode::NotFound                     , 404],
            'MethodNotAllowed'              => [StatusCode::MethodNotAllowed             , 405],
            'NotAcceptable'                 => [StatusCode::NotAcceptable                , 406],
            'ProxyAuthenticationRequired'   => [StatusCode::ProxyAuthenticationRequired  , 407],
            'RequestTimeout'                => [StatusCode::RequestTimeout               , 408],
            'Conflict'                      => [StatusCode::Conflict                     , 409],
            'Gone'                          => [StatusCode::Gone                         , 410],
            'LengthRequired'                => [StatusCode::LengthRequired               , 411],
            'PreconditionFailed'            => [StatusCode::PreconditionFailed           , 412],
            'PayloadTooLarge'               => [StatusCode::PayloadTooLarge              , 413],
            'URITooLong'                    => [StatusCode::URITooLong                   , 414],
            'UnsupportedMediaType'          => [StatusCode::UnsupportedMediaType         , 415],
            'RangeNotSatisfiable'           => [StatusCode::RangeNotSatisfiable          , 416],
            'ExpectationFailed'             => [StatusCode::ExpectationFailed            , 417],
            'ImATeapot'                     => [StatusCode::ImATeapot                    , 418],
            'UnprocessableEntity'           => [StatusCode::UnprocessableEntity          , 422],
            'Locked'                        => [StatusCode::Locked                       , 423],
            'FailedDependency'              => [StatusCode::FailedDependency             , 424],
            'UpgradeRequired'               => [StatusCode::UpgradeRequired              , 426],
            'PreconditionRequired'          => [StatusCode::PreconditionRequired         , 428],
            'TooManyRequests'               => [StatusCode::TooManyRequests              , 429],
            'RequestHeaderFieldsTooLarge'   => [StatusCode::RequestHeaderFieldsTooLarge  , 431],
            'UnavailableForLegalReasons'    => [StatusCode::UnavailableForLegalReasons   , 451],

            // 5xx - Server Error
            'InternalServerError'           => [StatusCode::InternalServerError          , 500],
            'NotImplemented'                => [StatusCode::NotImplemented               , 501],
            'BadGateway'                    => [StatusCode::BadGateway                   , 502],
            'ServiceUnavailable'            => [StatusCode::ServiceUnavailable           , 503],
            'GatewayTimeout'                => [StatusCode::GatewayTimeout               , 504],
            'HTTPVersionNotSupported'       => [StatusCode::HTTPVersionNotSupported      , 505],
            'VariantAlsoNegotiates'         => [StatusCode::VariantAlsoNegotiates        , 506],
            'InsufficientStorage'           => [StatusCode::InsufficientStorage          , 507],
            'LoopDetected'                  => [StatusCode::LoopDetected                 , 508],
            'NotExtended'                   => [StatusCode::NotExtended                  , 510],
            'NetworkAuthenticationRequired' => [StatusCode::NetworkAuthenticationRequired, 511]
        ];
    }

    #endregion Data Providers
}
