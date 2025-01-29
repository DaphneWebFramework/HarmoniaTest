<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Http\RequestMethod;

#[CoversClass(RequestMethod::class)]
class RequestMethodTest extends TestCase
{
    #[DataProvider('caseDataProvider')]
    function testCase(RequestMethod $requestMethod, string $str)
    {
        $this->assertSame($requestMethod, RequestMethod::from($str));
    }

    #region Data Providers -----------------------------------------------------

    static function caseDataProvider()
    {
        return [
            'GET'     => [RequestMethod::GET, 'GET'],
            'POST'    => [RequestMethod::POST, 'POST'],
            'PUT'     => [RequestMethod::PUT, 'PUT'],
            'DELETE'  => [RequestMethod::DELETE, 'DELETE'],
            'PATCH'   => [RequestMethod::PATCH, 'PATCH'],
            'OPTIONS' => [RequestMethod::OPTIONS, 'OPTIONS'],
            'HEAD'    => [RequestMethod::HEAD, 'HEAD'],
            'CONNECT' => [RequestMethod::CONNECT, 'CONNECT'],
            'TRACE'   => [RequestMethod::TRACE, 'TRACE'],
        ];
    }

    #endregion Data Providers
}
