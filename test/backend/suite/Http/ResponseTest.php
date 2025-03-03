<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Http\Response;

use \Harmonia\Core\CArray;
use \Harmonia\Http\StatusCode;
use \Harmonia\Services\CookieService;
use \TestToolkit\AccessHelper;

#[CoversClass(Response::class)]
class ResponseTest extends TestCase
{
    private ?CookieService $originalCookieService = null;

    protected function setUp(): void
    {
        $this->originalCookieService = CookieService::ReplaceInstance(
            $this->createMock(CookieService::class));
    }

    protected function tearDown(): void
    {
        CookieService::ReplaceInstance($this->originalCookieService);
    }

    #region __construct --------------------------------------------------------

    public function testConstruct()
    {
        $response = new Response();
        $this->assertSame(StatusCode::OK, AccessHelper::GetProperty($response, 'statusCode'));
        $this->assertNull(AccessHelper::GetProperty($response, 'headers'));
        $this->assertNull(AccessHelper::GetProperty($response, 'cookies'));
        $this->assertNull(AccessHelper::GetProperty($response, 'body'));
    }

    #endregion __construct

    #region SetStatusCode ---------------------------------------------------------

    public function testSetStatusCode()
    {
        $response = new Response();
        $this->assertSame($response, $response->SetStatusCode(StatusCode::ImATeapot));
        $this->assertSame(StatusCode::ImATeapot, AccessHelper::GetProperty($response, 'statusCode'));
    }

    #endregion SetStatusCode

    #region SetHeader ----------------------------------------------------------

    public function testSetHeader()
    {
        $response = new Response();
        $this->assertSame($response, $response->SetHeader('Content-Type', 'text/plain'));
        $headers = AccessHelper::GetProperty($response, 'headers');
        $this->assertInstanceof(CArray::class, $headers);
        $this->assertSame('text/plain', $headers->Get('Content-Type'));
    }

    #endregion SetHeader

    #region SetCookie ----------------------------------------------------------

    public function testSetCookie()
    {
        $response = new Response();
        $this->assertSame($response, $response->SetCookie('name', 'value'));
        $cookies = AccessHelper::GetProperty($response, 'cookies');
        $this->assertInstanceof(CArray::class, $cookies);
        $this->assertSame('value', $cookies->Get('name'));
    }

    #endregion SetCookie

    #region DeleteCookie -------------------------------------------------------

    public function testDeleteCookie()
    {
        $response = new Response();
        $this->assertSame($response, $response->DeleteCookie('name'));
        $cookies = AccessHelper::GetProperty($response, 'cookies');
        $this->assertInstanceof(CArray::class, $cookies);
        $this->assertSame('', $cookies->Get('name'));
    }

    #endregion DeleteCookie

    #region SetBody ------------------------------------------------------------

    public function testSetBodyWithString()
    {
        $response = new Response();
        $this->assertSame($response, $response->SetBody('Hello, World!'));
        $this->assertSame('Hello, World!', AccessHelper::GetProperty($response, 'body'));
    }

    public function testSetBodyWithStringable()
    {
        $response = new Response();
        $response->SetBody(new class {
            public function __toString() {
                return 'I am a Stringable object.';
            }
        });
        $this->assertSame('I am a Stringable object.', AccessHelper::GetProperty($response, 'body'));
    }

    #endregion SetBody

    #region Send ---------------------------------------------------------------

    public function testSendWhenHeadersCannotBeSent()
    {
        $cookieService = CookieService::Instance();
        $cookieService->expects($this->never())
            ->method('SetCookie');

        $response = $this->getMockBuilder(Response::class)
            ->onlyMethods(['canSendHeaders', 'sendStatusCode', 'sendHeader',
                           'sendBody'])
            ->getMock();
        $response->expects($this->once())
            ->method('canSendHeaders')
            ->willReturn(false);
        $response->expects($this->never())
            ->method('sendStatusCode');
        $response->expects($this->never())
            ->method('sendHeader');
        $response->expects($this->never())
            ->method('sendBody');

        $response->Send();
    }

    public function testSendWhenHeadersCanBeSentWithNoHeadersNoCookiesNoBody()
    {
        $cookieService = CookieService::Instance();
        $cookieService->expects($this->never())
            ->method('SetCookie');

        $response = $this->getMockBuilder(Response::class)
            ->onlyMethods(['canSendHeaders', 'sendStatusCode', 'sendHeader',
                           'sendBody'])
            ->getMock();
        $response->expects($this->once())
            ->method('canSendHeaders')
            ->willReturn(true);
        $response->expects($this->once())
            ->method('sendStatusCode');
        $response->expects($this->never())
            ->method('sendHeader');
        $response->expects($this->never())
            ->method('sendBody');

        $response->Send();
    }

    public function testSendWhenHeadersCanBeSentWithHeadersNoCookiesNoBody()
    {
        $cookieService = CookieService::Instance();
        $cookieService->expects($this->never())
            ->method('SetCookie');

        $response = $this->getMockBuilder(Response::class)
            ->onlyMethods(['canSendHeaders', 'sendStatusCode', 'sendHeader',
                           'sendBody'])
            ->getMock();
        $response->expects($this->once())
            ->method('canSendHeaders')
            ->willReturn(true);
        $response->expects($this->once())
            ->method('sendStatusCode');
        $response->expects($invokedCount = $this->exactly(2))
            ->method('sendHeader')
            ->willReturnCallback(function($name, $value) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('Content-Type', $name);
                    $this->assertSame('text/plain', $value);
                    break;
                case 2:
                    $this->assertSame('Cache-Control', $name);
                    $this->assertSame('no-cache', $value);
                    break;
                }
            });
        $response->expects($this->never())
            ->method('sendBody');

        $response
            ->SetHeader('Content-Type', 'text/plain')
            ->SetHeader('Cache-Control', 'no-cache')
            ->Send();
    }

    public function testSendWhenHeadersCanBeSentWithCookiesNoHeadersNoBody()
    {
        $cookieService = CookieService::Instance();
        $cookieService->expects($invokedCount = $this->exactly(2))
            ->method('SetCookie')
            ->willReturnCallback(function($name, $value) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('USERNAME', $name);
                    $this->assertSame('John Doe', $value);
                    break;
                case 2:
                    $this->assertSame('SESSIONID', $name);
                    $this->assertSame('', $value);
                    break;
                }
                return true;
            });

        $response = $this->getMockBuilder(Response::class)
            ->onlyMethods(['canSendHeaders', 'sendStatusCode', 'sendHeader',
                           'sendBody'])
            ->getMock();
        $response->expects($this->once())
            ->method('canSendHeaders')
            ->willReturn(true);
        $response->expects($this->once())
            ->method('sendStatusCode');
        $response->expects($this->never())
            ->method('sendHeader');
        $response->expects($this->never())
            ->method('sendBody');
        $response
            ->SetCookie('USERNAME', 'John Doe')
            ->DeleteCookie('SESSIONID')
            ->Send();
    }

    public function testSendWhenHeadersCanBeSentWithBodyNoHeadersNoCookies()
    {
        $cookieService = CookieService::Instance();
        $cookieService->expects($this->never())
            ->method('SetCookie');

        $response = $this->getMockBuilder(Response::class)
            ->onlyMethods(['canSendHeaders', 'sendStatusCode', 'sendHeader',
                           'sendBody'])
            ->getMock();
        $response->expects($this->once())
            ->method('canSendHeaders')
            ->willReturn(true);
        $response->expects($this->once())
            ->method('sendStatusCode');
        $response->expects($this->never())
            ->method('sendHeader');
        $response->expects($this->once())
            ->method('sendBody');
        $response
            ->SetBody('Hello, World!')
            ->Send();
    }

    public function testSendWhenHeadersCanBeSentWithHeadersCookiesBody()
    {
        $cookieService = CookieService::Instance();
        $cookieService->expects($invokedCount = $this->exactly(2))
            ->method('SetCookie')
            ->willReturnCallback(function($name, $value) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('USERNAME', $name);
                    $this->assertSame('John Doe', $value);
                    break;
                case 2:
                    $this->assertSame('SESSIONID', $name);
                    $this->assertSame('', $value);
                    break;
                }
                return true;
            });

        $response = $this->getMockBuilder(Response::class)
            ->onlyMethods(['canSendHeaders', 'sendStatusCode', 'sendHeader',
                           'sendBody'])
            ->getMock();
        $response->expects($this->once())
            ->method('canSendHeaders')
            ->willReturn(true);
        $response->expects($this->once())
            ->method('sendStatusCode');
        $response->expects($invokedCount = $this->exactly(2))
            ->method('sendHeader')
            ->willReturnCallback(function($name, $value) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('Content-Type', $name);
                    $this->assertSame('text/plain', $value);
                    break;
                case 2:
                    $this->assertSame('Cache-Control', $name);
                    $this->assertSame('no-cache', $value);
                    break;
                }
            });
        $response->expects($this->once())
            ->method('sendBody');
        $response
            ->SetStatusCode(StatusCode::MovedPermanently)
            ->SetHeader('Content-Type', 'text/plain')
            ->SetHeader('Cache-Control', 'no-cache')
            ->SetCookie('USERNAME', 'John Doe')
            ->DeleteCookie('SESSIONID')
            ->SetBody('Hello, World!')
            ->Send();
    }

    #endregion Send

    #region Redirect -----------------------------------------------------------

    public function testRedirectWithExitScript()
    {
        $response = $this->getMockBuilder(Response::class)
            ->onlyMethods(['SetStatusCode', 'SetHeader', 'Send', 'exitScript'])
            ->getMock();
        $response->expects($this->once())
            ->method('SetStatusCode')
            ->with(StatusCode::Found)
            ->willReturn($response);
        $response->expects($this->once())
            ->method('SetHeader')
            ->with('Location', 'https://example.com')
            ->willReturn($response);
        $response->expects($this->once())
            ->method('Send');
        $response->expects($this->once())
            ->method('exitScript');
        $response->Redirect('https://example.com', true);
    }

    public function testRedirectWithoutExitScript()
    {
        $response = $this->getMockBuilder(Response::class)
            ->onlyMethods(['SetStatusCode', 'SetHeader', 'Send', 'exitScript'])
            ->getMock();
        $response->expects($this->once())
            ->method('SetStatusCode')
            ->with(StatusCode::Found)
            ->willReturn($response);
        $response->expects($this->once())
            ->method('SetHeader')
            ->with('Location', 'https://example.com')
            ->willReturn($response);
        $response->expects($this->once())
            ->method('Send');
        $response->expects($this->never())
            ->method('exitScript');
        $response->Redirect('https://example.com', false);
    }

    #endregion Redirect
}
