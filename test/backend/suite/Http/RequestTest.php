<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

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

    function testMethodWithNullServerRequestMethod()
    {
        $request = Request::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('RequestMethod')
            ->willReturn(null);
        $this->assertNull($request->Method());
    }

    function testMethodWithInvalidServerRequestMethod()
    {
        $request = Request::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('RequestMethod')
            ->willReturn(new CString('invalid_method'));
        $this->assertNull($request->Method());
    }

    function testMethodWithNonUppercaseServerRequestMethod()
    {
        $request = Request::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('RequestMethod')
            ->willReturn(new CString('OpTiOnS'));
        $this->assertSame(RequestMethod::OPTIONS, $request->Method());
    }

    function testMethodWithUppercaseServerRequestMethod()
    {
        $request = Request::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('RequestMethod')
            ->willReturn(new CString('POST'));
        $this->assertSame(RequestMethod::POST, $request->Method());
    }



    #endregion Method
}
