<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\BackupGlobals;

use \Harmonia\Session;

use \Harmonia\Config;
use \Harmonia\Server;
use \Harmonia\Services\CookieService;
use \TestToolkit\AccessHelper;

#[CoversClass(Session::class)]
class SessionTest extends TestCase
{
    private ?Session $originalSession = null;
    private ?Server $originalServer = null;
    private ?Config $originalConfig = null;
    private ?CookieService $originalCookieService = null;

    protected function setUp(): void
    {
        $this->originalSession = Session::ReplaceInstance(
            $this->getMockBuilder(Session::class)
                ->onlyMethods(['_ini_set', '_session_set_cookie_params',
                               '_session_status', '_session_name',
                               '_session_start', '_session_regenerate_id',
                               '_session_write_close', '_session_unset',
                               '_session_destroy'])
                ->disableOriginalConstructor()
                ->getMock()
        );
        $this->originalServer = Server::ReplaceInstance(
            $this->createMock(Server::class));
        $this->originalConfig = Config::ReplaceInstance(
            $this->createMock(Config::class));
        $this->originalCookieService = CookieService::ReplaceInstance(
            $this->createMock(CookieService::class));
    }

    protected function tearDown(): void
    {
        Session::ReplaceInstance($this->originalSession);
        Server::ReplaceInstance($this->originalServer);
        Config::ReplaceInstance($this->originalConfig);
        CookieService::ReplaceInstance($this->originalCookieService);
    }

    #region __construct --------------------------------------------------------

    function testConstructWhenStatusIsDisabled()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Session support is disabled.');

        AccessHelper::CallConstructor($session);
    }

    function testConstructWhenStatusIsActive()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Session is already active.');

        AccessHelper::CallConstructor($session);
    }

    function testConstructSetsIniOptions()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $invokedCount = $this->exactly(5);
        $session->expects($invokedCount)
            ->method('_ini_set')
            ->willReturnCallback(function($option, $value) use($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame('session.use_strict_mode', $option);
                    $this->assertSame('1', $value);
                    break;
                case 2:
                    $this->assertSame('session.use_cookies', $option);
                    $this->assertSame('1', $value);
                    break;
                case 3:
                    $this->assertSame('session.use_only_cookies', $option);
                    $this->assertSame('1', $value);
                    break;
                case 4:
                    $this->assertSame('session.use_trans_sid', $option);
                    $this->assertSame('0', $value);
                    break;
                case 5:
                    $this->assertSame('session.cache_limiter', $option);
                    $this->assertSame('nocache', $value);
                    break;
                }
            });

        AccessHelper::CallConstructor($session);
    }

    function testConstructSetsCookieParamsWhenServerIsSecure()
    {
        $server = Server::Instance();
        $server->expects($this->once())
            ->method('IsSecure')
            ->willReturn(true);
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->once())
            ->method('_session_set_cookie_params')
            ->with(['lifetime' => 0,
                    'path' => '/',
                    'domain' => '',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict']);

        AccessHelper::CallConstructor($session);
    }

    function testConstructSetsCookieParamsWhenServerIsNotSecure()
    {
        $server = Server::Instance();
        $server->expects($this->once())
            ->method('IsSecure')
            ->willReturn(false);
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->once())
            ->method('_session_set_cookie_params')
            ->with(['lifetime' => 0,
                    'path' => '/',
                    'domain' => '',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Strict']);

        AccessHelper::CallConstructor($session);
    }

    function testConstructSetsSessionNameWhenConfigAppNameIsNotSetOrEmpty()
    {
        $config = Config::Instance();
        $config->expects($this->once())
            ->method('OptionOrDefault')
            ->with('AppName', '')
            ->willReturn('');
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->once())
            ->method('_session_name')
            ->with('Harmonia_SID');

        AccessHelper::CallConstructor($session);
    }

    function testConstructSetsSessionNameWhenConfigAppNameIsNotEmpty()
    {
        $config = Config::Instance();
        $config->expects($this->once())
            ->method('OptionOrDefault')
            ->with('AppName', '')
            ->willReturn('MyCoolApp');
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->once())
            ->method('_session_name')
            ->with('MyCoolApp_SID');

        AccessHelper::CallConstructor($session);
    }

    #endregion __construct

    #region Start --------------------------------------------------------------

    function testStartDoesNothingWhenStatusIsDisabled()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);
        $session->expects($this->never())
            ->method('_session_start');
        $session->expects($this->never())
            ->method('_session_regenerate_id');

        $this->assertSame($session, $session->Start());
    }

    function testStartDoesNothingWhenStatusIsActive()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $session->expects($this->never())
            ->method('_session_start');
        $session->expects($this->never())
            ->method('_session_regenerate_id');

        $this->assertSame($session, $session->Start());
    }

    function testStartWhenStatusIsNone()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->once())
            ->method('_session_start');
        $session->expects($this->once())
            ->method('_session_regenerate_id');

        $this->assertSame($session, $session->Start());
    }

    #endregion Start

    #region Close --------------------------------------------------------------

    function testCloseDoesNothingWhenStatusIsDisabled()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);
        $session->expects($this->never())
            ->method('_session_write_close');

        $this->assertSame($session, $session->Close());
    }

    function testCloseDoesNothingWhenStatusIsNone()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->never())
            ->method('_session_write_close');

        $this->assertSame($session, $session->Close());
    }

    function testCloseWhenStatusIsActive()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $session->expects($this->once())
            ->method('_session_write_close');

        $this->assertSame($session, $session->Close());
    }

    #endregion Close

    #region Set ----------------------------------------------------------------

    #[BackupGlobals(true)]
    function testSetDoesNothingWhenStatusIsDisabled()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);

        $this->assertSame($session, $session->Set('key1', 'value1'));

        $this->assertFalse(isset($_SESSION));
    }

    #[BackupGlobals(true)]
    function testSetDoesNothingWhenStatusIsNone()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);

        $this->assertSame($session, $session->Set('key1', 'value1'));

        $this->assertFalse(isset($_SESSION));
    }

    #[BackupGlobals(true)]
    function testSetWhenStatusIsActive()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $this->assertSame($session, $session->Set('key1', 'value1'));

        $this->assertSame('value1', $_SESSION['key1']);
    }

    #endregion Set

    #region Get ----------------------------------------------------------------

    #[BackupGlobals(true)]
    function testGetReturnsNullWhenStatusIsDisabled()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);

        $_SESSION['key1'] = 'value1';

        $this->assertNull($session->Get('key1'));
    }

    #[BackupGlobals(true)]
    function testGetReturnsDefaultValueWhenStatusIsDisabled()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);

        $_SESSION['key1'] = 'value1';

        $this->assertSame('default1', $session->Get('key1', 'default1'));
    }

    #[BackupGlobals(true)]
    function testGetReturnsNullWhenStatusIsNone()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);

        $_SESSION['key1'] = 'value1';

        $this->assertNull($session->Get('key1'));
    }

    #[BackupGlobals(true)]
    function testGetReturnsDefaultValueWhenStatusIsNone()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);

        $_SESSION['key1'] = 'value1';

        $this->assertSame('default1', $session->Get('key1', 'default1'));
    }

    #[BackupGlobals(true)]
    function testGetReturnsNullWhenStatusIsActiveButSuperglobalDoesNotExist()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $this->assertNull($session->Get('key1'));
    }

    #[BackupGlobals(true)]
    function testGetReturnsDefaultValueWhenStatusIsActiveButSuperglobalDoesNotExist()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $this->assertSame('default1', $session->Get('key1', 'default1'));
    }

    #[BackupGlobals(true)]
    function testGetReturnsNullWhenStatusIsActiveAndKeyDoesNotExist()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $_SESSION = [];

        $this->assertNull($session->Get('key1'));
    }

    #[BackupGlobals(true)]
    function testGetReturnsDefaultValueWhenStatusIsActiveButKeyDoesNotExist()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $_SESSION = [];

        $this->assertSame('default1', $session->Get('key1', 'default1'));
    }

    #[BackupGlobals(true)]
    function testGetReturnsValueWhenStatusIsActiveAndKeyExists()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $_SESSION['key1'] = 'value1';

        $this->assertSame('value1', $session->Get('key1'));
    }

    #endregion Get

    #region Remove -------------------------------------------------------------

    #[BackupGlobals(true)]
    function testRemoveDoesNothingWhenStatusIsDisabled()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);

        $_SESSION['key1'] = 'value1';

        $this->assertSame($session, $session->Remove('key1'));

        $this->assertSame('value1', $_SESSION['key1']);
    }

    #[BackupGlobals(true)]
    function testRemoveDoesNothingWhenStatusIsNone()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);

        $_SESSION['key1'] = 'value1';

        $this->assertSame($session, $session->Remove('key1'));

        $this->assertSame('value1', $_SESSION['key1']);
    }

    #[BackupGlobals(true)]
    function testRemoveWhenStatusIsActiveButSuperglobalDoesNotExist()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $this->assertSame($session, $session->Remove('key1'));
    }

    #[BackupGlobals(true)]
    function testRemoveWhenStatusIsActiveButKeyDoesNotExist()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $_SESSION = [];

        $this->assertSame($session, $session->Remove('key1'));

        $this->assertArrayNotHasKey('key1', $_SESSION);
    }

    #[BackupGlobals(true)]
    function testRemoveWhenStatusIsActiveAndKeyExists()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);

        $_SESSION['key1'] = 'value1';

        $this->assertSame($session, $session->Remove('key1'));

        $this->assertArrayNotHasKey('key1', $_SESSION);
    }

    #endregion Remove

    #region Clear --------------------------------------------------------------

    #[BackupGlobals(true)]
    function testClearDoesNothingWhenStatusIsDisabled()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);
        $session->expects($this->never())
            ->method('_session_unset');

        $this->assertSame($session, $session->Clear());
    }

    #[BackupGlobals(true)]
    function testClearDoesNothingWhenStatusIsNone()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->never())
            ->method('_session_unset');

        $this->assertSame($session, $session->Clear());
    }

    function testClearWhenStatusIsActive()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $session->expects($this->once())
            ->method('_session_unset');

        $this->assertSame($session, $session->Clear());
    }

    #endregion Clear

    #region Destroy ------------------------------------------------------------

    #[BackupGlobals(true)]
    function testDestroyDoesNothingWhenStatusIsDisabled()
    {
        $cookieService = CookieService::Instance();
        $cookieService->expects($this->never())
            ->method('DeleteCookie');

        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);
        $session->expects($this->never())
            ->method('_session_name');
        $session->expects($this->never())
            ->method('_session_unset');
        $session->expects($this->never())
            ->method('_session_destroy');

        $this->assertSame($session, $session->Destroy());
    }

    #[BackupGlobals(true)]
    function testDestroyDoesNothingWhenStatusIsNone()
    {
        $cookieService = CookieService::Instance();
        $cookieService->expects($this->never())
            ->method('DeleteCookie');

        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->never())
            ->method('_session_name');
        $session->expects($this->never())
            ->method('_session_unset');
        $session->expects($this->never())
            ->method('_session_destroy');

        $this->assertSame($session, $session->Destroy());
    }

    function testDestroyWhenStatusIsActiveButCookieCannotBeDeleted()
    {
        $cookieService = CookieService::Instance();
        $cookieService->expects($this->once())
            ->method('DeleteCookie')
            ->with('Harmonia_SID')
            ->willReturn(false);

        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $session->expects($this->once())
            ->method('_session_name')
            ->willReturn('Harmonia_SID');
        $session->expects($this->never())
            ->method('_session_unset');
        $session->expects($this->never())
            ->method('_session_destroy');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to delete session cookie.');

        $this->assertSame($session, $session->Destroy());
    }

    function testDestroyWhenStatusIsActiveAndCookieIsDeleted()
    {
        $cookieService = CookieService::Instance();
        $cookieService->expects($this->once())
            ->method('DeleteCookie')
            ->with('Harmonia_SID')
            ->willReturn(true);

        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $session->expects($this->once())
            ->method('_session_name')
            ->willReturn('Harmonia_SID');
        $session->expects($this->once())
            ->method('_session_unset');
        $session->expects($this->once())
            ->method('_session_destroy');

        $this->assertSame($session, $session->Destroy());
    }

    #endregion Destroy
}
