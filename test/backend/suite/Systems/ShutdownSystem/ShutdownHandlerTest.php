<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ShutdownSystem\ShutdownHandler;

use \Harmonia\Core\CSequentialArray;
use \Harmonia\Systems\ShutdownSystem\IShutdownListener;
use \TestToolkit\AccessHelper;

#[CoversClass(ShutdownHandler::class)]
class ShutdownHandlerTest extends TestCase
{
    private function systemUnderTest(string ...$mockedMethods): ShutdownHandler
    {
        return $this->getMockBuilder(ShutdownHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    #region __construct --------------------------------------------------------

    function testConstructor()
    {
        $sut = $this->systemUnderTest(
            '_ini_set',
            '_register_shutdown_function'
        );

        $sut->expects($this->once())
            ->method('_ini_set')
            ->with('display_errors', 0);
        $sut->expects($this->once())
            ->method('_register_shutdown_function')
            ->with([$sut, 'OnShutdown']);

        AccessHelper::CallConstructor($sut);

        $listeners = AccessHelper::GetMockProperty(
            ShutdownHandler::class,
            $sut,
            'listeners'
        );
        $this->assertInstanceOf(CSequentialArray::class, $listeners);
        $this->assertCount(0, $listeners);
    }

    #endregion __construct

    #region AddListener --------------------------------------------------------

    function testAddListener()
    {
        $sut = $this->systemUnderTest(
            '_ini_set',
            '_register_shutdown_function'
        );
        $listener = $this->createStub(IShutdownListener::class);

        AccessHelper::CallConstructor($sut);
        $sut->AddListener($listener);

        $listeners = AccessHelper::GetMockProperty(
            ShutdownHandler::class,
            $sut,
            'listeners'
        );
        $this->assertCount(1, $listeners);
        $this->assertSame($listener, $listeners[0]);
    }

    #endregion AddListener

    #region OnShutdown ---------------------------------------------------------

    function testOnShutdownWithNoError()
    {
        $sut = $this->systemUnderTest(
            '_ini_set',
            '_register_shutdown_function',
            '_error_get_last'
        );
        $listener = $this->createMock(IShutdownListener::class);

        $sut->expects($this->once())
            ->method('_error_get_last')
            ->willReturn(null);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with(null);

        AccessHelper::CallConstructor($sut);
        $sut->AddListener($listener);
        $sut->OnShutdown();
    }

    function testOnShutdownWithErrorHavingUnknownErrorType()
    {
        $sut = $this->systemUnderTest(
            '_ini_set',
            '_register_shutdown_function',
            '_error_get_last'
        );
        $listener = $this->createMock(IShutdownListener::class);

        $sut->expects($this->once())
            ->method('_error_get_last')
            ->willReturn([
                'type' => 999,
                'message' => 'Something went wrong',
                'file' => 'file.php',
                'line' => 123
            ]);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with("Something went wrong in 'file.php' on line 123.");

        AccessHelper::CallConstructor($sut);
        $sut->AddListener($listener);
        $sut->OnShutdown();
    }

    function testOnShutdownWithErrorHavingUnknownFile()
    {
        $sut = $this->systemUnderTest(
            '_ini_set',
            '_register_shutdown_function',
            '_error_get_last'
        );
        $listener = $this->createMock(IShutdownListener::class);

        $sut->expects($this->once())
            ->method('_error_get_last')
            ->willReturn([
                'type' => 8,
                'message' => 'Something went wrong',
                'file' => 'Unknown',
                'line' => 123
            ]);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with("E_NOTICE: Something went wrong on line 123.");

        AccessHelper::CallConstructor($sut);
        $sut->AddListener($listener);
        $sut->OnShutdown();
    }

    function testOnShutdownWithErrorHavingZeroLineNumber()
    {
        $sut = $this->systemUnderTest(
            '_ini_set',
            '_register_shutdown_function',
            '_error_get_last'
        );
        $listener = $this->createMock(IShutdownListener::class);

        $sut->expects($this->once())
            ->method('_error_get_last')
            ->willReturn([
                'type' => 8,
                'message' => 'Something went wrong',
                'file' => 'file.php',
                'line' => 0
            ]);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with("E_NOTICE: Something went wrong in 'file.php'.");

        AccessHelper::CallConstructor($sut);
        $sut->AddListener($listener);
        $sut->OnShutdown();
    }

    function testOnShutdownWithErrorHavingAllDetails()
    {
        $sut = $this->systemUnderTest(
            '_ini_set',
            '_register_shutdown_function',
            '_error_get_last'
        );
        $listener = $this->createMock(IShutdownListener::class);

        $sut->expects($this->once())
            ->method('_error_get_last')
            ->willReturn([
                'type' => 8,
                'message' => 'Something went wrong',
                'file' => 'file.php',
                'line' => 123
            ]);
        $listener->expects($this->once())
            ->method('OnShutdown')
            ->with("E_NOTICE: Something went wrong in 'file.php' on line 123.");

        AccessHelper::CallConstructor($sut);
        $sut->AddListener($listener);
        $sut->OnShutdown();
    }

    #endregion OnShutdown
}
