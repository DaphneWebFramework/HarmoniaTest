<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Systems\ValidationSystem\Messages;

use \Harmonia\Config;
use \Harmonia\Core\CPath;
use \TestToolkit\AccessHelper;

#[CoversClass(Messages::class)]
class MessagesTest extends TestCase
{
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalConfig =
            Config::ReplaceInstance($this->createMock(Config::class));
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalConfig);
    }

    private function systemUnderTest(string ...$mockedMethods): Messages
    {
        return $this->getMockBuilder(Messages::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    #region Get ----------------------------------------------------------------

    #[DataProvider('getWithoutParamDataProvider')]
    function testGetWithoutParam($expected, $language, $key)
    {
        $sut = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('Option')
            ->with('Language')
            ->willReturn($language);

        $this->assertSame($expected, $sut->Get($key));
    }

    #[DataProvider('getWithParamDataProvider')]
    function testGetWithParam($expected, $language, $key, $param)
    {
        $sut = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('Option')
            ->with('Language')
            ->willReturn($language);

        $this->assertSame($expected, $sut->Get($key, $param));
    }

    #endregion Get

    #region filePaths ----------------------------------------------------------

    function testFilePaths()
    {
        $sut = $this->systemUnderTest();
        $path = CPath::Join(
            \dirname((new ReflectionClass(Messages::class))->getFileName()),
            'messages.json'
        );
        $this->assertEquals([$path], AccessHelper::CallMethod($sut, 'filePaths'));
    }

    #endregion filePaths

    #region Data Providers -----------------------------------------------------

    static function getWithoutParamDataProvider()
    {
        return [
            [
                "Rule must be a non-empty string.",
                'en',
                'rule_must_be_non_empty'
            ],
            [
                "Kural boş olmayan bir metin olmalıdır.",
                'tr',
                'rule_must_be_non_empty'
            ],
        ];
    }

    static function getWithParamDataProvider()
    {
        return [
            [
                "Field 'price' must be numeric.",
                'en',
                'field_must_be_numeric',
                'price'
            ],
            [
                "Alan 'price' bir sayı olmalıdır.",
                'tr',
                'field_must_be_numeric',
                'price'
            ],
        ];
    }

    #endregion Data Providers
}
