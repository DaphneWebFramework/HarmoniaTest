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

    private function systemUnderTest(string ...$mockedMethods): Response
    {
        return $this->getMockBuilder(Response::class)
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    #region __construct --------------------------------------------------------

    function testConstruct()
    {
        $sut = new Response();
        $this->assertSame(StatusCode::OK, AccessHelper::GetProperty($sut, 'statusCode'));
        $this->assertNull(AccessHelper::GetProperty($sut, 'headers'));
        $this->assertNull(AccessHelper::GetProperty($sut, 'cookies'));
        $this->assertNull(AccessHelper::GetProperty($sut, 'body'));
    }

    #endregion __construct

    #region SetStatusCode ---------------------------------------------------------

    function testSetStatusCode()
    {
        $sut = new Response();
        $this->assertSame($sut, $sut->SetStatusCode(StatusCode::ImATeapot));
        $this->assertSame(StatusCode::ImATeapot, AccessHelper::GetProperty($sut, 'statusCode'));
    }

    #endregion SetStatusCode

    #region SetHeader ----------------------------------------------------------

    function testSetHeader()
    {
        $sut = new Response();
        $this->assertSame($sut, $sut->SetHeader('Content-Type', 'text/plain'));
        $headers = AccessHelper::GetProperty($sut, 'headers');
        $this->assertInstanceof(CArray::class, $headers);
        $this->assertSame('text/plain', $headers->Get('Content-Type'));
    }

    #endregion SetHeader

    #region SetCookie ----------------------------------------------------------

    function testSetCookie()
    {
        $sut = new Response();
        $this->assertSame($sut, $sut->SetCookie('name', 'value'));
        $cookies = AccessHelper::GetProperty($sut, 'cookies');
        $this->assertInstanceof(CArray::class, $cookies);
        $this->assertSame('value', $cookies->Get('name'));
    }

    #endregion SetCookie

    #region DeleteCookie -------------------------------------------------------

    function testDeleteCookie()
    {
        $sut = new Response();
        $this->assertSame($sut, $sut->DeleteCookie('name'));
        $cookies = AccessHelper::GetProperty($sut, 'cookies');
        $this->assertInstanceof(CArray::class, $cookies);
        $this->assertSame('', $cookies->Get('name'));
    }

    #endregion DeleteCookie

    #region SetBody ------------------------------------------------------------

    function testSetBodyWithString()
    {
        $sut = new Response();
        $this->assertSame($sut, $sut->SetBody('Hello, World!'));
        $this->assertSame(
            'Hello, World!',
            AccessHelper::GetProperty($sut, 'body')
        );
    }

    function testSetBodyWithStringable()
    {
        $sut = new Response();
        $sut->SetBody(new class implements \Stringable {
            function __toString() {
                return 'I am a Stringable object.';
            }
        });
        $this->assertSame(
            'I am a Stringable object.',
            AccessHelper::GetProperty($sut, 'body')
        );
    }

    #endregion SetBody

    #region Send ---------------------------------------------------------------

    function testSendWhenHeadersCannotBeSent()
    {
        $sut = $this->systemUnderTest(
            'canSendHeaders',
            'sendStatusCode',
            'sendHeader',
            'sendBody'
        );
        $cookieService = CookieService::Instance();

        $sut->expects($this->once())
            ->method('canSendHeaders')
            ->willReturn(false);
        $sut->expects($this->never())
            ->method('sendStatusCode');
        $sut->expects($this->never())
            ->method('sendHeader');
        $cookieService->expects($this->never())
            ->method('SetCookie');
        $sut->expects($this->never())
            ->method('sendBody');

        $sut->Send();
    }

    function testSendWhenHeadersCanBeSentWithNoHeadersNoCookiesNoBody()
    {
        $sut = $this->systemUnderTest(
            'canSendHeaders',
            'sendStatusCode',
            'sendHeader',
            'sendBody'
        );
        $cookieService = CookieService::Instance();

        $sut->expects($this->once())
            ->method('canSendHeaders')
            ->willReturn(true);
        $sut->expects($this->once())
            ->method('sendStatusCode');
        $sut->expects($this->never())
            ->method('sendHeader');
        $cookieService->expects($this->never())
            ->method('SetCookie');
        $sut->expects($this->never())
            ->method('sendBody');

        $sut->Send();
    }

    function testSendWhenHeadersCanBeSentWithHeadersNoCookiesNoBody()
    {
        $sut = $this->systemUnderTest(
            'canSendHeaders',
            'sendStatusCode',
            'sendHeader',
            'sendBody'
        );
        $cookieService = CookieService::Instance();

        $sut->expects($this->once())
            ->method('canSendHeaders')
            ->willReturn(true);
        $sut->expects($this->once())
            ->method('sendStatusCode');
        $sut->expects($invokedCount = $this->exactly(2))
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
        $cookieService->expects($this->never())
            ->method('SetCookie');
        $sut->expects($this->never())
            ->method('sendBody');

        $sut->SetHeader('Content-Type', 'text/plain')
            ->SetHeader('Cache-Control', 'no-cache')
            ->Send();
    }

    function testSendWhenHeadersCanBeSentWithCookiesNoHeadersNoBody()
    {
        $sut = $this->systemUnderTest(
            'canSendHeaders',
            'sendStatusCode',
            'sendHeader',
            'sendBody'
        );
        $cookieService = CookieService::Instance();

        $sut->expects($this->once())
            ->method('canSendHeaders')
            ->willReturn(true);
        $sut->expects($this->once())
            ->method('sendStatusCode');
        $sut->expects($this->never())
            ->method('sendHeader');
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
        $sut->expects($this->never())
            ->method('sendBody');

        $sut->SetCookie('USERNAME', 'John Doe')
            ->DeleteCookie('SESSIONID')
            ->Send();
    }

    function testSendWhenHeadersCanBeSentWithBodyNoHeadersNoCookies()
    {
        $sut = $this->systemUnderTest(
            'canSendHeaders',
            'sendStatusCode',
            'sendHeader',
            'sendBody'
        );
        $cookieService = CookieService::Instance();

        $sut->expects($this->once())
            ->method('canSendHeaders')
            ->willReturn(true);
        $sut->expects($this->once())
            ->method('sendStatusCode');
        $sut->expects($this->never())
            ->method('sendHeader');
        $cookieService->expects($this->never())
            ->method('SetCookie');
        $sut->expects($this->once())
            ->method('sendBody');

        $sut->SetBody('Hello, World!')
            ->Send();
    }

    function testSendWhenHeadersCanBeSentWithHeadersCookiesBody()
    {
        $sut = $this->systemUnderTest(
            'canSendHeaders',
            'sendStatusCode',
            'sendHeader',
            'sendBody'
        );
        $cookieService = CookieService::Instance();

        $sut->expects($this->once())
            ->method('canSendHeaders')
            ->willReturn(true);
        $sut->expects($this->once())
            ->method('sendStatusCode');
        $sut->expects($invokedCount = $this->exactly(2))
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
        $sut->expects($this->once())
            ->method('sendBody');

        $sut->SetStatusCode(StatusCode::MovedPermanently)
            ->SetHeader('Content-Type', 'text/plain')
            ->SetHeader('Cache-Control', 'no-cache')
            ->SetCookie('USERNAME', 'John Doe')
            ->DeleteCookie('SESSIONID')
            ->SetBody('Hello, World!')
            ->Send();
    }

    #endregion Send

    #region Redirect -----------------------------------------------------------

    function testRedirectWithStringableUrl()
    {
        $sut = $this->systemUnderTest(
            'SetStatusCode',
            'SetHeader',
            'Send',
            'exitScript'
        );
        $sut->expects($this->once())
            ->method('SetStatusCode')
            ->with(StatusCode::Found)
            ->willReturn($sut);
        $sut->expects($this->once())
            ->method('SetHeader')
            ->with('Location', 'https://example.com')
            ->willReturn($sut);
        $sut->expects($this->once())
            ->method('Send');
        $sut->expects($this->once())
            ->method('exitScript');

        $sut->Redirect(new class implements \Stringable {
            function __toString() {
                return 'https://example.com';
            }
        });
    }

    function testRedirectWithExitScript()
    {
        $sut = $this->systemUnderTest(
            'SetStatusCode',
            'SetHeader',
            'Send',
            'exitScript'
        );
        $sut->expects($this->once())
            ->method('SetStatusCode')
            ->with(StatusCode::Found)
            ->willReturn($sut);
        $sut->expects($this->once())
            ->method('SetHeader')
            ->with('Location', 'https://example.com')
            ->willReturn($sut);
        $sut->expects($this->once())
            ->method('Send');
        $sut->expects($this->once())
            ->method('exitScript');

        $sut->Redirect('https://example.com', true);
    }

    function testRedirectWithoutExitScript()
    {
        $sut = $this->systemUnderTest(
            'SetStatusCode',
            'SetHeader',
            'Send',
            'exitScript'
        );

        $sut->expects($this->once())
            ->method('SetStatusCode')
            ->with(StatusCode::Found)
            ->willReturn($sut);
        $sut->expects($this->once())
            ->method('SetHeader')
            ->with('Location', 'https://example.com')
            ->willReturn($sut);
        $sut->expects($this->once())
            ->method('Send');
        $sut->expects($this->never())
            ->method('exitScript');

        $sut->Redirect('https://example.com', false);
    }

    #endregion Redirect
}
