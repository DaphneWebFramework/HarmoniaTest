<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Services\CookieService;

use \Harmonia\Config;
use \Harmonia\Server;
use \TestToolkit\DataHelper;

#[CoversClass(CookieService::class)]
class CookieServiceTest extends TestCase
{
    private ?CookieService $originalCookieService = null;
    private ?Server $originalServer = null;
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalCookieService = CookieService::ReplaceInstance(
            $this->getMockBuilder(CookieService::class)
                ->onlyMethods(['_headers_sent', '_setcookie'])
                ->disableOriginalConstructor()
                ->getMock()
        );
        $this->originalServer = Server::ReplaceInstance($this->createMock(Server::class));
        $this->originalConfig = Config::ReplaceInstance($this->createMock(Config::class));
    }

    protected function tearDown(): void
    {
        CookieService::ReplaceInstance($this->originalCookieService);
        Server::ReplaceInstance($this->originalServer);
        Config::ReplaceInstance($this->originalConfig);
    }

    private function options($isSecure): array
    {
        return [
            'expires'  => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Strict'
        ];
    }

    #region SetCookie ----------------------------------------------------------

    function testSetCookieWhenHeadersSent()
    {
        $cookieService = CookieService::Instance();

        $cookieService->expects($this->once())
            ->method('_headers_sent')
            ->willReturn(true);
        $cookieService->expects($this->never())
            ->method('_setcookie');

        $this->assertFalse($cookieService->SetCookie('', ''));
    }

    #[DataProvider('setCookieDataProvider')]
    function testSetCookie($cookieValue, $isSecure, $returnValue)
    {
        $cookieService = CookieService::Instance();
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('IsSecure')
            ->willReturn($isSecure);
        $cookieService->expects($this->once())
            ->method('_headers_sent')
            ->willReturn(false);
        $cookieService->expects($this->once())
            ->method('_setcookie')
            ->with('cookie-name', $cookieValue, $this->options($isSecure))
            ->willReturn($returnValue);

        $this->assertSame(
            $returnValue,
            $cookieService->SetCookie('cookie-name', $cookieValue)
        );
    }

    #endregion SetCookie

    #region DeleteCookie -------------------------------------------------------

    function testDeleteCookieWhenHeadersSent()
    {
        $cookieService = CookieService::Instance();

        $cookieService->expects($this->once())
            ->method('_headers_sent')
            ->willReturn(true);
        $cookieService->expects($this->never())
            ->method('_setcookie');

        $this->assertFalse($cookieService->DeleteCookie(''));
    }

    #[DataProvider('deleteCookieDataProvider')]
    function testDeleteCookie($isSecure, $returnValue)
    {
        $cookieService = CookieService::Instance();
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('IsSecure')
            ->willReturn($isSecure);
        $cookieService->expects($this->once())
            ->method('_headers_sent')
            ->willReturn(false);
        $cookieService->expects($this->once())
            ->method('_setcookie')
            ->with('cookie-name', '', $this->options($isSecure))
            ->willReturn($returnValue);

        $this->assertSame(
            $returnValue,
            $cookieService->DeleteCookie('cookie-name')
        );
    }

    #endregion DeleteCookie

    #region GenerateCookieName -------------------------------------------------

    function testGenerateCookieNameWhenConfigAppNameIsNotSetOrEmpty()
    {
        $cookieService = CookieService::Instance();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('OptionOrDefault')
            ->with('AppName', '')
            ->willReturn('');

        $this->assertSame(
            'HARMONIA_INTEGRITY_TOKEN',
            $cookieService->GenerateCookieName('INTEGRITY_TOKEN')
        );
    }

    function testGenerateCookieNameWhenConfigAppNameIsSetAndNotEmpty()
    {
        $cookieService = CookieService::Instance();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('OptionOrDefault')
            ->with('AppName', '')
            ->willReturn('MyApp');

        $this->assertSame(
            'MYAPP_INTEGRITY_TOKEN',
            $cookieService->GenerateCookieName('INTEGRITY_TOKEN')
        );
    }

    function testGenerateCookieNameWithLowerCaseSuffix()
    {
        $cookieService = CookieService::Instance();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('OptionOrDefault')
            ->with('AppName', '')
            ->willReturn('MyApp');

        $this->assertSame(
            'MYAPP_INTEGRITY_TOKEN',
            $cookieService->GenerateCookieName('integrity_token')
        );
    }

    function testGenerateCookieNameWithEmptySuffix()
    {
        $cookieService = CookieService::Instance();
        $config = Config::Instance();

        $config->expects($this->never())
            ->method('OptionOrDefault');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Suffix cannot be empty.');
        $cookieService->GenerateCookieName('');
    }

    #endregion GenerateCookieName

    #region Data Providers -----------------------------------------------------

    static function setCookieDataProvider()
    {
        return DataHelper::Cartesian(
            ['test_value', ''], // cookie value
            [false, true],      // is secure
            [false, true]       // return value
        );
    }

    static function deleteCookieDataProvider()
    {
        return DataHelper::Cartesian(
            [false, true], // is secure
            [false, true]  // return value
        );
    }

    #endregion Data Providers
}
