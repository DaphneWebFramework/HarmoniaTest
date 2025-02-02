<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\BackupGlobals;

use \Harmonia\Http\Request;

use \Harmonia\Core\CArray;
use \Harmonia\Core\CString;
use \Harmonia\Http\RequestMethod;
use \Harmonia\Server;

require_once 'StreamMockManager.php';

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

    #region QueryParams --------------------------------------------------------

    #[BackupGlobals(true)]
    function testQueryParams()
    {
        $_GET['key1'] = 'value1';
        $_GET['key2'] = 'value2';
        $request = Request::Instance();
        $this->assertEquals(
            ['key1' => 'value1', 'key2' => 'value2'],
            $request->QueryParams()->ToArray()
        );
    }

    #endregion QueryParams

    #region FormParams ---------------------------------------------------------

    #[BackupGlobals(true)]
    function testFormParams()
    {
        $_POST['key1'] = 'value1';
        $_POST['key2'] = 'value2';
        $request = Request::Instance();
        $this->assertEquals(
            ['key1' => 'value1', 'key2' => 'value2'],
            $request->FormParams()->ToArray()
        );
    }

    #endregion FormParams

    #region Files --------------------------------------------------------------

    #[BackupGlobals(true)]
    function testFiles()
    {
        $_FILES['key1'] = ['name' => 'file1.txt', 'type' => 'text/plain'];
        $_FILES['key2'] = ['name' => 'file2.txt', 'type' => 'text/plain'];
        $request = Request::Instance();
        $this->assertEquals(
            ['key1' => ['name' => 'file1.txt', 'type' => 'text/plain'],
             'key2' => ['name' => 'file2.txt', 'type' => 'text/plain']],
            $request->Files()->ToArray()
        );
    }

    #endregion Files

    #region Cookies ------------------------------------------------------------

    #[BackupGlobals(true)]
    function testCookies()
    {
        $_COOKIE['key1'] = 'value1';
        $_COOKIE['key2'] = 'value2';
        $request = Request::Instance();
        $this->assertEquals(
            ['key1' => 'value1', 'key2' => 'value2'],
            $request->Cookies()->ToArray()
        );
    }

    #endregion Cookies

    #region Headers ------------------------------------------------------------

    #[BackupGlobals(true)]
    function testHeaders()
    {
        $request = Request::Instance();
        $serverMock = Server::Instance();
        $serverMock->method('RequestHeaders')
            ->willReturn(new CArray([
                'accept' => 'text/html',
                'accept-encoding' => 'gzip, deflate',
                'accept-language' => 'en-US',
            ]));
        $this->assertEquals(
            [ 'accept' => 'text/html',
              'accept-encoding' => 'gzip, deflate',
              'accept-language' => 'en-US' ],
            $request->Headers()->ToArray()
        );
    }

    #endregion Headers

    #region Body ---------------------------------------------------------------

    function testBodyWhenStreamCannotBeOpened()
    {
        $request = Request::Instance();
        $streamMockManager = StreamMockManager::Create();
        $this->assertTrue($streamMockManager->Write('Hello from request body!'));
        $streamMockManager->SimulateOpenError();
        // Suppress the error message: file_get_contents(php://input): Failed to
        // open stream: "StreamMock::stream_open" call failed
        $this->assertNull(@$request->Body());
    }

    function testBodyWhenStreamCannotBeRead()
    {
        $request = Request::Instance();
        $streamMockManager = StreamMockManager::Create();
        $this->assertTrue($streamMockManager->Write('Hello from request body!'));
        $streamMockManager->SimulateReadError();
        // Suppress the error message: file_get_contents(php://input): Failed to
        // open stream: "StreamMock::stream_open" call failed
        $this->assertTrue($request->Body()->IsEmpty());
    }

    function testBodyWithWorkingStream()
    {
        $request = Request::Instance();
        $data = 'Hello from request body!';
        $streamMockManager = StreamMockManager::Create();
        $this->assertTrue($streamMockManager->Write($data));
        $this->assertEquals($data, $request->Body());
        // Multiple calls should return the same data
        $this->assertEquals($data, $request->Body());
    }

    #endregion Body

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
