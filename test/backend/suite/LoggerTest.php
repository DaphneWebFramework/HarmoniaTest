<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Logger;

use \Harmonia\Config;
use \Harmonia\Core\CFile;
use \Harmonia\Core\CFileSystem;
use \Harmonia\Core\CPath;
use \Harmonia\Resource;
use \Harmonia\Server;
use \TestToolkit\AccessHelper;
use \TestToolkit\DataHelper;

#[CoversClass(Logger::class)]
class LoggerTest extends TestCase
{
    private ?Config $originalConfig = null;
    private ?Resource $originalResource = null;
    private ?CFileSystem $originalFileSystem = null;
    private ?Server $originalServer = null;

    protected function setUp(): void
    {
        $this->originalConfig =
            Config::ReplaceInstance($this->createMock(Config::class));
        $this->originalResource =
            Resource::ReplaceInstance($this->createMock(Resource::class));
        $this->originalFileSystem =
            CFileSystem::ReplaceInstance($this->createMock(CFileSystem::class));
        $this->originalServer =
            Server::ReplaceInstance($this->createMock(Server::class));
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalConfig);
        Resource::ReplaceInstance($this->originalResource);
        CFileSystem::ReplaceInstance($this->originalFileSystem);
        Server::ReplaceInstance($this->originalServer);
    }

    private function systemUnderTest(string ...$mockedMethods): Logger
    {
        return $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    #region __construct --------------------------------------------------------

    function testConstructor()
    {
        $sut = $this->systemUnderTest('buildFilePath', 'ensureDirectoryExists');
        $config = Config::Instance();

        $config->expects($this->exactly(2))
            ->method('OptionOrDefault')
            ->willReturnMap([
                ['LogFile', 'default.log', 'path/to/file'],
                ['LogLevel', 3, 1],
            ]);
        $sut->expects($this->once())
            ->method('buildFilePath')
            ->with('path/to/file')
            ->willReturn(new CPath('app/root/path/to/file'));
        $sut->expects($this->once())
            ->method('ensureDirectoryExists');

        AccessHelper::CallConstructor($sut);

        $this->assertEquals(
            new CPath('app/root/path/to/file'),
            AccessHelper::GetMockProperty(Logger::class, $sut, 'filePath')
        );
        $this->assertEquals(
            1,
            AccessHelper::GetMockProperty(Logger::class, $sut, 'level')
        );
    }

    #endregion __construct

    #region Info ---------------------------------------------------------------

    #[DataProvider('levelsBelowAllDataProvider')]
    function testInfoDoesNothingWhenLogLevelBelow($level)
    {
        $sut = $this->systemUnderTest('writeEntry');

        $sut->expects($this->never())
            ->method('writeEntry');

        AccessHelper::SetMockProperty(Logger::class, $sut, 'level', $level);
        $sut->Info('');
    }

    function testInfoLogsMessageWhenEnabled()
    {
        $sut = $this->systemUnderTest('writeEntry', 'formatEntry', 'resolveMessage');

        $sut->expects($this->once())
            ->method('resolveMessage')
            ->with('Test message')
            ->willReturn('Test message');
        $sut->expects($this->once())
            ->method('formatEntry')
            ->with('INFO', 'Test message')
            ->willReturn('[timestamp | localhost] INFO: Test message');
        $sut->expects($this->once())
            ->method('writeEntry')
            ->with('[timestamp | localhost] INFO: Test message');

        AccessHelper::SetMockProperty(Logger::class, $sut, 'level', 3); // LEVEL_ALL
        $sut->Info('Test message');
    }

    #endregion Info

    #region Warning ------------------------------------------------------------

    #[DataProvider('levelsBelowWarningsDataProvider')]
    function testWarningDoesNothingWhenLogLevelBelow($level)
    {
        $sut = $this->systemUnderTest('writeEntry');

        $sut->expects($this->never())
            ->method('writeEntry');

        AccessHelper::SetMockProperty(Logger::class, $sut, 'level', $level);
        $sut->Warning('');
    }

    function testWarningLogsMessageWhenEnabled()
    {
        $sut = $this->systemUnderTest('writeEntry', 'formatEntry', 'resolveMessage');

        $sut->expects($this->once())
            ->method('resolveMessage')
            ->with('Test message')
            ->willReturn('Test message');
        $sut->expects($this->once())
            ->method('formatEntry')
            ->with('WARNING', 'Test message')
            ->willReturn('[timestamp | localhost] WARNING: Test message');
        $sut->expects($this->once())
            ->method('writeEntry')
            ->with('[timestamp | localhost] WARNING: Test message');

        AccessHelper::SetMockProperty(Logger::class, $sut, 'level', 2); // LEVEL_WARNINGS
        $sut->Warning('Test message');
    }

    #endregion Warning

    #region Error --------------------------------------------------------------

    #[DataProvider('levelsBelowErrorsDataProvider')]
    function testErrorDoesNothingWhenLogLevelBelow($level)
    {
        $sut = $this->systemUnderTest('writeEntry');

        $sut->expects($this->never())
            ->method('writeEntry');

        AccessHelper::SetMockProperty(Logger::class, $sut, 'level', $level);
        $sut->Error('');
    }

    function testErrorLogsMessageWhenEnabled()
    {
        $sut = $this->systemUnderTest('writeEntry', 'formatEntry', 'resolveMessage');

        $sut->expects($this->once())
            ->method('resolveMessage')
            ->with('Test message')
            ->willReturn('Test message');
        $sut->expects($this->once())
            ->method('formatEntry')
            ->with('ERROR', 'Test message')
            ->willReturn('[timestamp | localhost] ERROR: Test message');
        $sut->expects($this->once())
            ->method('writeEntry')
            ->with('[timestamp | localhost] ERROR: Test message');

        AccessHelper::SetMockProperty(Logger::class, $sut, 'level', 1); // LEVEL_ERRORS
        $sut->Error('Test message');
    }

    #endregion Error

    #region buildFilePath ------------------------------------------------------

    function testBuildFilePathWithAbsolutePath()
    {
        $sut = $this->systemUnderTest('isAbsolutePath');

        $sut->expects($this->once())
            ->method('isAbsolutePath')
            ->with('path/to/file')
            ->willReturn(true);

        $builtPath = AccessHelper::CallMethod($sut, 'buildFilePath', ['path/to/file']);
        $this->assertEquals(new CPath('path/to/file'), $builtPath);
    }

    function testBuildFilePathWithRelativePath()
    {
        $sut = $this->systemUnderTest('isAbsolutePath');
        $resource = Resource::Instance();

        $sut->expects($this->once())
            ->method('isAbsolutePath')
            ->with('path/to/file')
            ->willReturn(false);
        $resource->expects($this->once())
            ->method('AppPath')
            ->willReturn(new CPath('/app/root'));

        $builtPath = AccessHelper::CallMethod($sut, 'buildFilePath', ['path/to/file']);
        $builtPath->ReplaceInPlace('\\', '/');
        $this->assertEquals(new CPath('/app/root/path/to/file'), $builtPath);
    }

    #endregion buildFilePath

    #region isAbsolutePath -----------------------------------------------------

    #[DataProvider('isAbsolutePathDataProvider')]
    function testIsAbsolutePath(bool $expected, string $path)
    {
        $sut = $this->systemUnderTest();

        $this->assertSame(
            $expected,
            AccessHelper::CallMethod($sut, 'isAbsolutePath', [$path])
        );
    }

    #endregion isAbsolutePath

    #region ensureDirectoryExists ----------------------------------------------

    function testEnsureDirectoryExistsDoesNothingIfDirectoryExists()
    {
        $sut = $this->systemUnderTest();
        $filePath = $this->createMock(CPath::class);
        $dirPath = $this->createMock(CPath::class);
        $fileSystem = CFileSystem::Instance();

        $filePath->expects($this->once())
            ->method('Apply')
            ->with('dirname')
            ->willReturn($dirPath);
        $dirPath->expects($this->once())
            ->method('IsDirectory')
            ->willReturn(true);
        $fileSystem->expects($this->never())
            ->method('CreateDirectory');

        AccessHelper::SetMockProperty(Logger::class, $sut, 'filePath', $filePath);
        AccessHelper::CallMethod($sut, 'ensureDirectoryExists');
    }

    #[DataProviderExternal(DataHelper::class, 'BooleanProvider')]
    function testEnsureDirectoryExistsCreatesDirectoryIfMissing($createDirectoryResult)
    {
        $sut = $this->systemUnderTest();
        $filePath = $this->createMock(CPath::class);
        $dirPath = $this->createMock(CPath::class);
        $fileSystem = CFileSystem::Instance();

        $filePath->expects($this->once())
            ->method('Apply')
            ->with('dirname')
            ->willReturn($dirPath);
        $dirPath->expects($this->once())
            ->method('IsDirectory')
            ->willReturn(false);
        $fileSystem->expects($this->once())
            ->method('CreateDirectory')
            ->with($dirPath)
            ->willReturn($createDirectoryResult); // silenty ignored

        AccessHelper::SetMockProperty(Logger::class, $sut, 'filePath', $filePath);
        AccessHelper::CallMethod($sut, 'ensureDirectoryExists');
    }

    #endregion ensureDirectoryExists

    #region resolveMessage -----------------------------------------------------

    function testResolveMessageWithString()
    {
        $sut = $this->systemUnderTest();

        $this->assertSame(
            'Test message',
            AccessHelper::CallMethod($sut, 'resolveMessage', ['Test message'])
        );
    }

    function testResolveMessageWithCallable()
    {
        $sut = $this->systemUnderTest();

        $this->assertSame(
            'Test message',
            AccessHelper::CallMethod(
                $sut, 'resolveMessage', [function() { return 'Test message'; }])
        );
    }

    #endregion resolveMessage

    #region formatEntry --------------------------------------------------------

    function testFormatEntry()
    {
        $sut = $this->systemUnderTest();
        $server = Server::Instance();

        $server->expects($this->once())
            ->method('ClientAddress')
            ->willReturn('192.168.33.1');

        $this->assertMatchesRegularExpression(
            '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \| 192\.168\.33\.1\] INFO: Test message$/',
            AccessHelper::CallMethod($sut, 'formatEntry', ['INFO', 'Test message'])
        );
    }

    #endregion formatEntry

    #region writeEntry ---------------------------------------------------------

    function testWriteEntryDoesNothingIfFileCannotBeOpened()
    {
        $sut = $this->systemUnderTest('openFile');
        $filePath = $this->createMock(CPath::class);

        $sut->expects($this->once())
            ->method('openFile')
            ->with($filePath, CFile::MODE_APPEND)
            ->willReturn(null);

        AccessHelper::SetMockProperty(Logger::class, $sut, 'filePath', $filePath);
        AccessHelper::CallMethod($sut, 'writeEntry', ['']);
    }

    #[DataProviderExternal(DataHelper::class, 'BooleanProvider')]
    function testWriteEntryWritesEntryAndClosesFile($writeLineResult)
    {
        $sut = $this->systemUnderTest('openFile');
        $filePath = $this->createMock(CPath::class);
        $file = $this->createMock(CFile::class);

        $sut->expects($this->once())
            ->method('openFile')
            ->with($filePath, CFile::MODE_APPEND)
            ->willReturn($file);
        $file->expects($this->once())
            ->method('WriteLine')
            ->willReturn($writeLineResult); // silently ignored
        $file->expects($this->once())
            ->method('Close');

        AccessHelper::SetMockProperty(Logger::class, $sut, 'filePath', $filePath);
        AccessHelper::CallMethod($sut, 'writeEntry', ['']);
    }

    #endregion writeEntry

    #region Data Providers -----------------------------------------------------

    static function levelsBelowAllDataProvider()
    {
        return [
            [0], // LEVEL_NONE
            [1], // LEVEL_ERRORS
            [2]  // LEVEL_WARNINGS
        ];
    }

    static function levelsBelowWarningsDataProvider()
    {
        return [
            [0], // LEVEL_NONE
            [1]  // LEVEL_ERRORS
        ];
    }

    static function levelsBelowErrorsDataProvider()
    {
        return [
            [0] // LEVEL_NONE
        ];
    }

    static function isAbsolutePathDataProvider()
    {
        $linuxCases = [
            [true, '/'],
            [false, '\\'],
        ];
        $windowsCases = [
            [true, 'C:\\'],
            [true, 'C:/'],
            [true, '\\\\'],
            [true, '//'],
            [false, '\\'],
            [false, '/'],
        ];
        $commonCases = [
            [false, ''],
            [false, 'C:'],
            [false, 'app.log'],
        ];
        return \DIRECTORY_SEPARATOR === '/'
            ? \array_merge($linuxCases, $commonCases)
            : \array_merge($windowsCases, $commonCases);
    }

    #endregion Data Providers
}
