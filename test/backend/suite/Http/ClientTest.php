<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Http\Client;

use \TestToolkit\AccessHelper as ah;

#[CoversClass(Client::class)]
class ClientTest extends TestCase
{
    private function systemUnderTest(string ...$mockedMethods): Client
    {
        return $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    #region __construct --------------------------------------------------------

    function testConstructThrowsIfTransportInitFails()
    {
        $sut = $this->systemUnderTest('_curl_init', '_curl_close');

        $sut->expects($this->once())
            ->method('_curl_init')
            ->willReturn(false);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to initialize transport layer.");

        $sut->__construct();
        $this->assertNull(ah::GetProperty($sut, 'curl'));

        $sut->expects($this->never())
            ->method('_curl_close');

        $sut->__destruct();
    }

    function testConstructSucceeds()
    {
        $sut = $this->systemUnderTest(
            '_curl_init',
            '_curl_close',
            'Clear',
            'clearResponse',
            'clearLastError'
        );
        // Since CurlHandle is declared as "final", we cannot mock it.
        // It also cannot be constructed, so we must use curl_init().
        $curl = \curl_init();

        $sut->expects($this->once())
            ->method('_curl_init')
            ->willReturn($curl);
        if (\PHP_VERSION_ID < 80500) {
            $sut->expects($this->once())
                ->method('_curl_close');
        }
        $sut->expects($this->once())
            ->method('Clear');
        $sut->expects($this->once())
            ->method('clearResponse');
        $sut->expects($this->once())
            ->method('clearLastError');

        $sut->__construct();
        $this->assertSame($curl, ah::GetProperty($sut, 'curl'));

        $sut->__destruct();
        $this->assertNull(ah::GetProperty($sut, 'curl'));

        if (\PHP_VERSION_ID < 80500) {
            \curl_close($curl);
        }
    }

    #endregion __construct

    #region Clear --------------------------------------------------------------

    function testClear()
    {
        $sut = $this->systemUnderTest();

        $sut->__construct();
        $request = ah::GetProperty($sut, 'request');
        $request->method  = 'POST';
        $request->url     = 'https://example.com';
        $request->headers = ['foo' => 'bar'];
        $request->body    = 'body';

        ah::CallMethod($sut, 'Clear');

        $this->assertSame('GET', $request->method);
        $this->assertSame('', $request->url);
        $this->assertSame([], $request->headers);
        $this->assertSame('', $request->body);
    }

    #endregion Clear

    #region Method_ ------------------------------------------------------------

    function testMethodReturnsSelf()
    {
        $sut = new Client();
        $this->assertSame($sut, $sut->Method_(''));
    }

    function testMethodSetsValueInUppercase()
    {
        $sut = new Client();
        $sut->Method_('pOsT');
        $this->assertSame(
            'POST',
            ah::GetProperty($sut, 'request')->method
        );
    }

    #endregion Method_

    #region Get ----------------------------------------------------------------

    function testGetReturnsSelf()
    {
        $sut = new Client();
        $this->assertSame($sut, $sut->Get());
    }

    function testGetSetsMethodToGet()
    {
        $sut = new Client();
        $sut->Get();
        $this->assertSame(
            'GET',
            ah::GetProperty($sut, 'request')->method
        );
    }

    #endregion Get

    #region Post ---------------------------------------------------------------

    function testPostReturnsSelf()
    {
        $sut = new Client();
        $this->assertSame($sut, $sut->Post());
    }

    function testPostSetsMethodToPost()
    {
        $sut = new Client();
        $sut->Post();
        $this->assertSame(
            'POST',
            ah::GetProperty($sut, 'request')->method
        );
    }

    #endregion Post

    #region Url ----------------------------------------------------------------

    function testUrlReturnsSelf()
    {
        $sut = new Client();
        $this->assertSame($sut, $sut->Url(''));
    }

    function testUrlSetsValue()
    {
        $sut = new Client();
        $url = 'https://example.com';
        $sut->Url($url);
        $this->assertSame($url, ah::GetProperty($sut, 'request')->url);
    }

    #endregion Url

    #region Headers ------------------------------------------------------------

    function testHeadersReturnsSelfWhenSetting()
    {
        $sut = new Client();
        $headers = ['Content-Type' => 'application/json'];
        $this->assertSame($sut, $sut->Headers($headers));
        $this->assertSame($headers, ah::GetProperty($sut, 'request')->headers);
    }

    function testHeadersReturnsResponseHeadersWhenGetting()
    {
        $sut = new Client();
        ah::GetProperty($sut, 'response')->headers = [
            'content-type' => 'application/json',
            'x-custom'     => 'abc'
        ];
        $headers = $sut->Headers();
        $this->assertIsArray($headers);
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertSame('application/json', $headers['content-type']);
        $this->assertArrayHasKey('x-custom', $headers);
        $this->assertSame('abc', $headers['x-custom']);
        $this->assertSame([], ah::GetProperty($sut, 'request')->headers);
    }

    #endregion Headers

    #region Body ---------------------------------------------------------------

    function testBodyReturnsSelfWhenSettingString()
    {
        $sut = new Client();
        $this->assertSame($sut, $sut->Body('body'));
        $this->assertSame('body', ah::GetProperty($sut, 'request')->body);
    }

    function testBodyReturnsSelfWhenSettingArray()
    {
        $sut = new Client();
        $this->assertSame($sut, $sut->Body(['foo' => 'bar']));
        $this->assertSame(['foo' => 'bar'], ah::GetProperty($sut, 'request')->body);
    }

    function testBodyReturnsResponseBodyWhenGetting()
    {
        $sut = new Client();
        ah::GetProperty($sut, 'response')->body = 'body';
        $this->assertSame('body', $sut->Body());
        $this->assertSame('', ah::GetProperty($sut, 'request')->body);
    }

    #endregion Body

    #region StatusCode ---------------------------------------------------------

    function testStatusCode()
    {
        $sut = new Client();
        ah::GetProperty($sut, 'response')->statusCode = 404;
        $this->assertSame(404, $sut->StatusCode());
    }

    #endregion StatusCode

    #region Send ---------------------------------------------------------------

    function testSendFailure()
    {
        $sut = $this->systemUnderTest(
            'reset',
            'applyRequestMethod',
            'applyRequestUrl',
            'applyRequestHeaders',
            'applyRequestBody',
            'attachResponseHeaderHandler',
            'execute',
            'updateResponseStatusCode',
            'updateResponseBody'
        );

        $sut->expects($this->once())->method('reset');
        $sut->expects($this->once())->method('applyRequestMethod');
        $sut->expects($this->once())->method('applyRequestUrl');
        $sut->expects($this->once())->method('applyRequestHeaders');
        $sut->expects($this->once())->method('applyRequestBody');
        $sut->expects($this->once())->method('attachResponseHeaderHandler');
        $sut->expects($this->once())->method('execute')->willReturn(false);
        $sut->expects($this->never())->method('updateResponseStatusCode');
        $sut->expects($this->never())->method('updateResponseBody');

        $this->assertFalse($sut->Send());
    }

    function testSendSuccess()
    {
        $sut = $this->systemUnderTest(
            'reset',
            'applyRequestMethod',
            'applyRequestUrl',
            'applyRequestHeaders',
            'applyRequestBody',
            'attachResponseHeaderHandler',
            'execute',
            'updateResponseStatusCode',
            'updateResponseBody'
        );

        $sut->expects($this->once())->method('reset');
        $sut->expects($this->once())->method('applyRequestMethod');
        $sut->expects($this->once())->method('applyRequestUrl');
        $sut->expects($this->once())->method('applyRequestHeaders');
        $sut->expects($this->once())->method('applyRequestBody');
        $sut->expects($this->once())->method('attachResponseHeaderHandler');
        $sut->expects($this->once())->method('execute')->willReturn('body');
        $sut->expects($this->once())->method('updateResponseStatusCode');
        $sut->expects($this->once())->method('updateResponseBody')->with('body');

        $this->assertTrue($sut->Send());
    }

    #endregion Send

    #region LastError ----------------------------------------------------------

    function testLastError()
    {
        $sut = new Client();
        ah::GetProperty($sut, 'lastError')->code = 123;
        ah::GetProperty($sut, 'lastError')->message = 'message';
        $lastError = $sut->LastError();
        $this->assertSame(123, $lastError->code);
        $this->assertSame('message', $lastError->message);
    }

    #endregion LastError

    #region clearResponse ------------------------------------------------------

    function testClearResponse()
    {
        $sut = $this->systemUnderTest();

        $sut->__construct();
        $response = ah::GetProperty($sut, 'response');
        $response->statusCode = 404;
        $response->headers    = ['foo' => 'bar'];
        $response->body       = 'body';

        ah::CallMethod($sut, 'clearResponse');

        $this->assertSame(0, $response->statusCode);
        $this->assertSame([], $response->headers);
        $this->assertSame('', $response->body);
    }

    #endregion clearResponse

    #region clearLastError -----------------------------------------------------

    function testClearLastError()
    {
        $sut = $this->systemUnderTest();

        $sut->__construct();
        $lastError = ah::GetProperty($sut, 'lastError');
        $lastError->code    = 123;
        $lastError->message = 'message';

        ah::CallMethod($sut, 'clearLastError');

        $this->assertSame(0, $lastError->code);
        $this->assertSame('', $lastError->message);
    }

    #endregion clearLastError

    #region reset --------------------------------------------------------------

    function testReset()
    {
        $sut = $this->systemUnderTest(
            '_curl_reset',
            '_curl_setopt',
            'clearResponse',
            'clearLastError'
        );

        $sut->expects($this->once())
            ->method('_curl_reset');
        $sut->expects($this->once())
            ->method('_curl_setopt')
            ->with(\CURLOPT_RETURNTRANSFER, true);
        $sut->expects($this->once())
            ->method('clearResponse');
        $sut->expects($this->once())
            ->method('clearLastError');

        ah::CallMethod($sut, 'reset');
    }

    #endregion reset

    #region applyRequestMethod -------------------------------------------------

    function testApplyRequestMethod()
    {
        $sut = $this->systemUnderTest('_curl_setopt');

        $sut->__construct();
        $request = ah::GetProperty($sut, 'request');
        $request->method = 'POST';

        $sut->expects($this->once())
            ->method('_curl_setopt')
            ->with(\CURLOPT_CUSTOMREQUEST, 'POST');

        ah::CallMethod($sut, 'applyRequestMethod');
    }

    #endregion applyRequestMethod

    #region applyRequestUrl ----------------------------------------------------

    function testApplyRequestUrl()
    {
        $sut = $this->systemUnderTest('_curl_setopt');

        $sut->__construct();
        $request = ah::GetProperty($sut, 'request');
        $request->url = 'http://example.com';

        $sut->expects($this->once())
            ->method('_curl_setopt')
            ->with(\CURLOPT_URL, 'http://example.com');

        ah::CallMethod($sut, 'applyRequestUrl');
    }

    #endregion applyRequestUrl

    #region applyRequestHeaders ------------------------------------------------

    function testApplyRequestHeadersWithEmptyHeaders()
    {
        $sut = $this->systemUnderTest('_curl_setopt');

        $sut->__construct();
        $request = ah::GetProperty($sut, 'request');
        $request->headers = [];

        $sut->expects($this->never())
            ->method('_curl_setopt');

        ah::CallMethod($sut, 'applyRequestHeaders');
    }

    function testApplyRequestHeadersWithFilledHeaders()
    {
        $sut = $this->systemUnderTest('_curl_setopt');

        $sut->__construct();
        $request = ah::GetProperty($sut, 'request');
        $request->headers = ['Foo' => 'Bar', 'Baz' => 'Qux'];

        $sut->expects($this->once())
            ->method('_curl_setopt')
            ->with(\CURLOPT_HTTPHEADER, ['Foo: Bar', 'Baz: Qux']);

        ah::CallMethod($sut, 'applyRequestHeaders');
    }

    #endregion applyRequestHeaders

    #region applyRequestBody ---------------------------------------------------

    function testApplyRequestBodyWithEmptyString()
    {
        $sut = $this->systemUnderTest('_curl_setopt');

        $sut->__construct();
        $request = ah::GetProperty($sut, 'request');
        $request->body = '';

        $sut->expects($this->never())
            ->method('_curl_setopt');

        ah::CallMethod($sut, 'applyRequestBody');
    }

    function testApplyRequestBodyWithFilledString()
    {
        $sut = $this->systemUnderTest('_curl_setopt');

        $sut->__construct();
        $request = ah::GetProperty($sut, 'request');
        $request->body = 'body';

        $sut->expects($this->once())
            ->method('_curl_setopt')
            ->with(\CURLOPT_POSTFIELDS, 'body');

        ah::CallMethod($sut, 'applyRequestBody');
    }

    function testApplyRequestBodyWithEmptyArray()
    {
        $sut = $this->systemUnderTest('_curl_setopt');

        $sut->__construct();
        $request = ah::GetProperty($sut, 'request');
        $request->body = [];

        $sut->expects($this->never())
            ->method('_curl_setopt');

        ah::CallMethod($sut, 'applyRequestBody');
    }

    function testApplyRequestBodyWithFilledArray()
    {
        $sut = $this->systemUnderTest('_curl_setopt');

        $sut->__construct();
        $request = ah::GetProperty($sut, 'request');
        $request->body = ['foo' => 'bar'];

        $sut->expects($this->once())
            ->method('_curl_setopt')
            ->with(\CURLOPT_POSTFIELDS, ['foo' => 'bar']);

        ah::CallMethod($sut, 'applyRequestBody');
    }

    #endregion applyRequestBody

    #region attachResponseHeaderHandler ----------------------------------------

    function testAttachResponseHeaderHandler()
    {
        $sut = $this->systemUnderTest('_curl_setopt');

        $sut->__construct();
        $response = ah::GetProperty($sut, 'response');

        $headerFunction = null;
        $sut->expects($this->once())
            ->method('_curl_setopt')
            ->with(
                \CURLOPT_HEADERFUNCTION,
                $this->callback(function($callable) use(&$headerFunction) {
                    $this->assertIsCallable($callable);
                    $headerFunction = $callable;
                    return true;
                })
            );

        ah::CallMethod($sut, 'attachResponseHeaderHandler');

        $this->assertSame(17, $headerFunction(null, "HTTP/1.1 200 OK\r\n"));
        $this->assertSame(10, $headerFunction(null, "Foo: Bar\r\n"));
        $this->assertSame(14, $headerFunction(null, " Baz :  Qux \r\n"));
        $this->assertSame(15, $headerFunction(null, "InvalidHeader\r\n"));
        $this->assertSame( 2, $headerFunction(null, "\r\n"));
        $this->assertSame(['foo' => 'Bar', 'baz' => 'Qux'], $response->headers);
    }

    #endregion attachResponseHeaderHandler

    #region execute ------------------------------------------------------------

    function testExecuteSuccess()
    {
        $sut = $this->systemUnderTest('_curl_exec');

        $sut->__construct();

        $sut->expects($this->once())
            ->method('_curl_exec')
            ->willReturn('body');

        $result = ah::CallMethod($sut, 'execute');

        $this->assertSame('body', $result);
    }

    function testExecuteFailure()
    {
        $sut = $this->systemUnderTest('_curl_exec', '_curl_errno', '_curl_error');

        $sut->__construct();

        $sut->expects($this->once())
            ->method('_curl_exec')
            ->willReturn(false);
        $sut->expects($this->once())
            ->method('_curl_errno')
            ->willReturn(123);
        $sut->expects($this->once())
            ->method('_curl_error')
            ->willReturn('message');

        $result = ah::CallMethod($sut, 'execute');

        $this->assertFalse($result);
        $lastError = ah::GetProperty($sut, 'lastError');
        $this->assertSame(123, $lastError->code);
        $this->assertSame('message', $lastError->message);
    }

    #endregion execute

    #region updateResponseStatusCode -------------------------------------------

    function testUpdateResponseStatusCode()
    {
        $sut = $this->systemUnderTest('_curl_getinfo');

        $sut->expects($this->once())
            ->method('_curl_getinfo')
            ->with(\CURLINFO_HTTP_CODE)
            ->willReturn(404);

        $sut->__construct();
        ah::CallMethod($sut, 'updateResponseStatusCode');

        $this->assertSame(404, ah::GetProperty($sut, 'response')->statusCode);
    }

    #endregion updateResponseStatusCode

    #region updateResponseBody -------------------------------------------------

    function testUpdateResponseBody()
    {
        $sut = $this->systemUnderTest();

        $sut->__construct();
        ah::CallMethod($sut, 'updateResponseBody', ['body']);

        $this->assertSame('body', ah::GetProperty($sut, 'response')->body);
    }

    #endregion updateResponseBody

    // function testIntegration()
    // {
    //     $client = new Client();
    //     $result = $client
    //         ->Method_('POST')
    //         ->Url('https://echo.free.beeceptor.com/')
    //         ->Body(\http_build_query(['id' => 42, 'name' => 'John Doe']))
    //         ->Send();
    //     $this->assertTrue($result);
    //     $this->assertSame(200, $client->StatusCode());
    //     echo PHP_EOL . PHP_EOL;
    //     echo $client->Body();
    //     echo PHP_EOL . PHP_EOL;
    // }
}
