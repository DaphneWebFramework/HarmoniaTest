<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\BackupGlobals;

use \Harmonia\Session;

use \Harmonia\Server;
use \Harmonia\Config;
use \TestToolkit\AccessHelper;

#[CoversClass(Session::class)]
class SessionTest extends TestCase
{
    private ?Session $originalSession = null;
    private ?Server $originalServer = null;
    private ?Config $originalConfig = null;
    private static int $phpUnitMajorVersion = 0;

    /**
     * Determines the PHPUnit major version and stores it for conditional logic
     * in test setup.
     */
    public static function setUpBeforeClass(): void
    {
        $phpUnitVersion = \PHPUnit\Runner\Version::id(); // e.g., "11.5.6"
        self::$phpUnitMajorVersion = (int)\explode('.', $phpUnitVersion)[0];
    }

    protected function setUp(): void
    {
        $this->originalServer = Server::ReplaceInstance($this->createMock(Server::class));
        $this->originalConfig = Config::ReplaceInstance($this->createMock(Config::class));
        $this->originalSession = Session::ReplaceInstance($this->createSessionMock());
    }

    protected function tearDown(): void
    {
        Server::ReplaceInstance($this->originalServer);
        Config::ReplaceInstance($this->originalConfig);
        Session::ReplaceInstance($this->originalSession);
    }

    /**
     * Prepares a mock instance of `Session`, handling PHPUnit version
     * differences to maintain compatibility and code coverage.
     *
     * PHPUnit 10:
     * o Requires `disableOriginalConstructor()` to prevent a fatal error when
     *   mocking a class with a protected constructor: "Error: Call to protected
     *   Harmonia\Session::__construct() from scope PHPUnit\Framework\MockObject
     *   \Generator\Generator"
     * o However, this reduces the coverage, since the constructor is never
     *   executed.
     * o To address this, `AccessHelper::CallNonPublicConstructor($session)`
     *   is used to manually invoke the protected constructor.
     *
     * PHPUnit 11:
     * o Allows `getMock()` to successfully call a protected constructor.
     * o As a result, full coverage is naturally achieved without additional
     *   intervention.
     */
    private function createSessionMock(): Session
    {
        $sessionMockBuilder = $this->getMockBuilder(Session::class)
            ->onlyMethods(['_ini_set', '_session_set_cookie_params',
                           '_session_status', '_session_name', '_session_start',
                           '_session_regenerate_id', '_session_write_close',
                           '_session_unset', '_session_destroy']);
        if (self::$phpUnitMajorVersion < 11) {
            $sessionMockBuilder->disableOriginalConstructor();
        }
        $session = $sessionMockBuilder->getMock();
        if (self::$phpUnitMajorVersion < 11) {
            AccessHelper::CallNonPublicConstructor($session);
        }
        return $session;
    }

    #region IsStarted ----------------------------------------------------------

    function testIsStartedWhenStatusIsDisabled()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);
        $this->assertFalse($session->IsStarted());
    }

    function testIsStartedWhenStatusIsNone()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $this->assertFalse($session->IsStarted());
    }

    function testIsStartedWhenStatusIsActive()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $this->assertTrue($session->IsStarted());
    }

    #endregion IsStarted

    #region Name ---------------------------------------------------------------

    function testName()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_name')
            ->with(null)
            ->willReturn('name');
        $this->assertEquals('name', $session->Name());
    }

    #endregion Name

    #region Start --------------------------------------------------------------

    function testStart()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_start');
        $session->expects($this->once())
            ->method('_session_regenerate_id');
        $session->Start();
    }

    #endregion Start

    #region Close --------------------------------------------------------------

    function testClose()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_write_close');
        $session->Close();
    }

    #endregion Close

    #region Set ----------------------------------------------------------------

    #[BackupGlobals(true)]
    function testSet()
    {
        $session = Session::Instance();
        $session->Set('key1', 'value1');
        $this->assertEquals('value1', $_SESSION['key1']);
    }

    #endregion Set

    #region Get ----------------------------------------------------------------

    #[BackupGlobals(true)]
    function testGetWhenSuperglobalIsNotSet()
    {
        $session = Session::Instance();
        $this->assertNull($session->Get('key1'));
    }

    #[BackupGlobals(true)]
    function testGetWhenKeyDoesNotExist()
    {
        $session = Session::Instance();
        $_SESSION = [];
        $this->assertNull($session->Get('key1'));
    }

    #[BackupGlobals(true)]
    function testGetWhenWhenSuperglobalIsNotSetWithDefaultValue()
    {
        $session = Session::Instance();
        $this->assertEquals('default1', $session->Get('key1', 'default1'));
    }

    #[BackupGlobals(true)]
    function testGetWhenKeyDoesNotExistWithDefaultValue()
    {
        $session = Session::Instance();
        $_SESSION = [];
        $this->assertEquals('default1', $session->Get('key1', 'default1'));
    }

    #[BackupGlobals(true)]
    function testGetWhenKeyExists()
    {
        $_SESSION['key1'] = 'value1';
        $session = Session::Instance();
        $this->assertEquals('value1', $session->Get('key1'));
    }

    #endregion Get

    #region Remove -------------------------------------------------------------

    #[BackupGlobals(true)]
    function testRemoveWhenSuperglobalIsNotSet()
    {
        $session = Session::Instance();
        $session->Remove('key1');
        $this->assertTrue(true);
    }


    #[BackupGlobals(true)]
    function testRemoveWhenKeyDoesNotExist()
    {
        $session = Session::Instance();
        $_SESSION = [];
        $session->Remove('key1');
        $this->assertArrayNotHasKey('key1', $_SESSION);
    }

    #[BackupGlobals(true)]
    function testRemoveWhenKeyExists()
    {
        $session = Session::Instance();
        $_SESSION['key1'] = 'value1';
        $session->Remove('key1');
        $this->assertArrayNotHasKey('key1', $_SESSION);
    }

    #endregion Remove

    #region Clear --------------------------------------------------------------

    #[BackupGlobals(true)]
    function testClearWhenStatusIsDisabled()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);
        $session->expects($this->never())
            ->method('_session_unset');
        $_SESSION['key1'] = 'value1';
        $_SESSION['key2'] = 'value2';
        $session->Clear();
        $this->assertEmpty($_SESSION);
    }

    #[BackupGlobals(true)]
    function testClearWhenStatusIsNone()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->never())
            ->method('_session_unset');
        $_SESSION['key1'] = 'value1';
        $_SESSION['key2'] = 'value2';
        $session->Clear();
        $this->assertEmpty($_SESSION);
    }

    function testClearWhenStatusIsActive()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $session->expects($this->once())
            ->method('_session_unset');
        $session->Clear();
    }

    #endregion Clear

    #region Destroy ------------------------------------------------------------

    #[BackupGlobals(true)]
    function testDestroyWhenStatusIsDisabled()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_DISABLED);
        $session->expects($this->never())
            ->method('_session_unset');
        $session->expects($this->once())
            ->method('_session_destroy');
        $_SESSION['key1'] = 'value1';
        $_SESSION['key2'] = 'value2';
        $session->Destroy();
        $this->assertEmpty($_SESSION);
    }

    #[BackupGlobals(true)]
    function testDestroyWhenStatusIsNone()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_NONE);
        $session->expects($this->never())
            ->method('_session_unset');
        $session->expects($this->once())
            ->method('_session_destroy');
        $_SESSION['key1'] = 'value1';
        $_SESSION['key2'] = 'value2';
        $session->Destroy();
        $this->assertEmpty($_SESSION);
    }

    function testDestroyWhenStatusIsActive()
    {
        $session = Session::Instance();
        $session->expects($this->once())
            ->method('_session_status')
            ->willReturn(\PHP_SESSION_ACTIVE);
        $session->expects($this->once())
            ->method('_session_unset');
        $session->expects($this->once())
            ->method('_session_destroy');
        $session->Destroy();
    }

    #endregion Destroy
}
