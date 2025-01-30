<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Http\Request;

use \Harmonia\Core\CString;
use \Harmonia\Http\RequestMethod;
use \Harmonia\Server;

#[CoversClass(Request::class)]
class RequestTest extends TestCase
{
    private readonly ?Request $originalRequest;
    private readonly ?Server $originalServer;

    protected function setUp(): void
    {
        $this->originalRequest = Request::ReplaceInstance(null);
        $this->originalServer = Server::ReplaceInstance($this->createMock(Server::class));
    }

    protected function tearDown(): void
    {
        Request::ReplaceInstance($this->originalRequest);
        Server::ReplaceInstance($this->originalServer);
    }

    #region Method -------------------------------------------------------------

    #[DataProvider('methodDataProvider')]
    public function testMethod(?RequestMethod $expected, ?CString $serverRequestMethod)
    {
        $request = Request::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('RequestMethod')
            ->willReturn($serverRequestMethod);
        $this->assertSame($expected, $request->Method());
    }

    #endregion Method

    #region Path ---------------------------------------------------------------

    #[DataProvider('pathDataProvider')]
    public function testPath(?CString $expected, ?CString $serverRequestUri)
    {
        $request = Request::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('RequestUri')
            ->willReturn($serverRequestUri);
        $this->assertEquals($expected, $request->Path());
    }

    #endregion Path

    #region Data Providers -----------------------------------------------------

    static function methodDataProvider()
    {
        return [
            [null,                   null],
            [null,                   new CString('invalid-method')],
            [RequestMethod::OPTIONS, new CString('OpTiOnS')],
            [RequestMethod::POST,    new CString('POST')],
        ];
    }

    static function pathDataProvider()
    {
        return [
            [null,                             null],
            [null,                             new CString('')],
            [null,                             new CString('?query')],
            [null,                             new CString('#fragment')],
            [null,                             new CString('?query#fragment')],
            [new CString('/path/to/resource'), new CString('/path/to/resource?query')],
            [new CString('/path/to/resource'), new CString('/path/to/resource?query#fragment')],
            [new CString('/path/to/resource'), new CString('/path/to/resource/?query')],
            [new CString('/path/to/resource'), new CString('/path/to/resource/?query#fragment')],
            [new CString('/path/to/resource'), new CString('/path/to/resource')],
            [new CString('/path/to/resource'), new CString('/path/to/resource/')],
        ];
    }

    #endregion Data Providers
}
