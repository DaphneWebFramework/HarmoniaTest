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
use \TestToolkit\AccessHelper;

require_once 'StreamMockManager.php';

#[CoversClass(Request::class)]
class RequestTest extends TestCase
{
    private ?Server $originalServer = null;

    protected function setUp(): void
    {
        $this->originalServer =
            Server::ReplaceInstance($this->createMock(Server::class));
    }

    protected function tearDown(): void
    {
        Server::ReplaceInstance($this->originalServer);
    }

    private function systemUnderTest(string ...$mockedMethods): Request
    {
        $sut = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
        return AccessHelper::CallConstructor($sut);
    }

    #region Method_ ------------------------------------------------------------

    #[DataProvider('methodDataProvider')]
    public function testMethod(?RequestMethod $expected, ?CString $serverRequestMethod)
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('RequestMethod')
            ->willReturn($serverRequestMethod);

        $this->assertSame($expected, $sut->Method_());
    }

    #endregion Method_

    #region Path ---------------------------------------------------------------

    #[DataProvider('pathDataProvider')]
    public function testPath(?CString $expected, ?CString $serverRequestUri)
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('RequestUri')
            ->willReturn($serverRequestUri);

        $this->assertEquals($expected, $sut->Path());
    }

    #endregion Path

    #region QueryParams --------------------------------------------------------

    #[BackupGlobals(true)]
    function testQueryParams()
    {
        $sut = $this->systemUnderTest();
        $_GET['key1'] = 'value1';
        $_GET['key2'] = 'value2';

        $this->assertEquals(
            ['key1' => 'value1', 'key2' => 'value2'],
            $sut->QueryParams()->ToArray()
        );
    }

    #endregion QueryParams

    #region FormParams ---------------------------------------------------------

    #[BackupGlobals(true)]
    function testFormParams()
    {
        $sut = $this->systemUnderTest();
        $_POST['key1'] = 'value1';
        $_POST['key2'] = 'value2';

        $this->assertEquals(
            ['key1' => 'value1', 'key2' => 'value2'],
            $sut->FormParams()->ToArray()
        );
    }

    #endregion FormParams

    #region Files --------------------------------------------------------------

    #[BackupGlobals(true)]
    function testFiles()
    {
        $sut = $this->systemUnderTest();
        $_FILES['key1'] = ['name' => 'file1.txt', 'type' => 'text/plain'];
        $_FILES['key2'] = ['name' => 'file2.txt', 'type' => 'text/plain'];

        $this->assertEquals(
            ['key1' => ['name' => 'file1.txt', 'type' => 'text/plain'],
             'key2' => ['name' => 'file2.txt', 'type' => 'text/plain']],
            $sut->Files()->ToArray()
        );
    }

    #endregion Files

    #region Cookies ------------------------------------------------------------

    #[BackupGlobals(true)]
    function testCookies()
    {
        $sut = $this->systemUnderTest();
        $_COOKIE['key1'] = 'value1';
        $_COOKIE['key2'] = 'value2';

        $this->assertEquals(
            ['key1' => 'value1', 'key2' => 'value2'],
            $sut->Cookies()->ToArray()
        );
    }

    #endregion Cookies

    #region Headers ------------------------------------------------------------

    #[BackupGlobals(true)]
    function testHeaders()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('RequestHeaders')
            ->willReturn(new CArray([
                'accept' => 'text/html',
                'accept-encoding' => 'gzip, deflate',
                'accept-language' => 'en-US',
            ]));

        $this->assertEquals(
            [ 'accept' => 'text/html',
              'accept-encoding' => 'gzip, deflate',
              'accept-language' => 'en-US' ],
            $sut->Headers()->ToArray()
        );
    }

    #endregion Headers

    #region Body ---------------------------------------------------------------

    function testBodyWhenStreamCannotBeOpened()
    {
        $sut = $this->systemUnderTest();
        $smm = StreamMockManager::Create();

        $this->assertTrue($smm->Write('Hello from request body!'));
        $smm->SimulateOpenError();
        // Suppress the error message: file_get_contents(php://input): Failed to
        // open stream: "StreamMock::stream_open" call failed
        $this->assertNull(@$sut->Body());
    }

    function testBodyWhenStreamCannotBeRead()
    {
        $sut = $this->systemUnderTest();
        $smm = StreamMockManager::Create();

        $this->assertTrue($smm->Write('Hello from request body!'));
        $smm->SimulateReadError();
        $this->assertSame('', $sut->Body());
    }

    function testBodyWithWorkingStream()
    {
        $sut = $this->systemUnderTest();
        $smm = StreamMockManager::Create();
        $data = 'Hello from request body!';

        $this->assertTrue($smm->Write($data));
        $this->assertSame($data, $sut->Body());
        // Multiple calls should return the same data
        $this->assertSame($data, $sut->Body());
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
