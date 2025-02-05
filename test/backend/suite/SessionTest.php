<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\BackupGlobals;

use \Harmonia\Session;

#[CoversClass(Session::class)]
class SessionTest extends TestCase
{
    private ?Session $originalSession = null;

    protected function setUp(): void
    {
        $session = $this->getMockBuilder(Session::class)
            ->onlyMethods(['_session_status', '_session_name', '_session_start',
                '_session_write_close', '_session_unset', '_session_destroy'])
            ->getMock();
        $this->originalSession = Session::ReplaceInstance($session);
    }

    protected function tearDown(): void
    {
        Session::ReplaceInstance($this->originalSession);
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
