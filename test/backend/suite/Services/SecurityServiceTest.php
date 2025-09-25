<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Services\SecurityService;

use \Harmonia\Config;
use \TestToolkit\AccessHelper;
use \TestToolkit\DataHelper;

#[CoversClass(SecurityService::class)]
class SecurityServiceTest extends TestCase
{
    private const DEFAULT_TOKEN_PATTERN = '/^[A-Fa-f0-9]{64}$/';
    private const CSRF_COOKIE_VALUE_PATTERN = '/^[A-Fa-f0-9]{64}$/';

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

    private function systemUnderTest(string ...$mockedMethods): SecurityService
    {
        return $this->getMockBuilder(SecurityService::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    #region HashPassword -------------------------------------------------------

    function testHashPassword()
    {
        $sut = $this->systemUnderTest();
        $password = 'pass1234';
        $hash = $sut->HashPassword($password);

        $this->assertTrue(\password_verify($password, $hash));
    }

    #endregion HashPassword

    #region VerifyPassword -----------------------------------------------------

    function testVerifyPassword()
    {
        $sut = $this->systemUnderTest();
        $password = 'pass1234';
        $hash = \password_hash($password, \PASSWORD_DEFAULT);

        $this->assertTrue($sut->VerifyPassword($password, $hash));
        $this->assertFalse($sut->VerifyPassword('invalid', $hash));
        $this->assertFalse($sut->VerifyPassword($password, 'invalid'));
    }

    #endregion VerifyPassword

    #region GenerateToken ------------------------------------------------------

    function testGenerateTokenWithDefaultByteLength()
    {
        $sut = $this->systemUnderTest();
        $token = $sut->GenerateToken();
        $this->assertMatchesRegularExpression(self::DEFAULT_TOKEN_PATTERN, $token);
    }

    function testGenerateTokenWithArbitraryByteLength()
    {
        $sut = $this->systemUnderTest();
        $token = $sut->GenerateToken(8);
        $this->assertMatchesRegularExpression('/^[A-Fa-f0-9]{16}$/', $token);
    }

    #endregion GenerateToken

    #region TokenPattern -------------------------------------------------------

    function testTokenPatternWithDefaultByteLength()
    {
        $sut = $this->systemUnderTest();
        $tokenPattern = $sut->TokenPattern();
        $this->assertSame(self::DEFAULT_TOKEN_PATTERN, $tokenPattern);
    }

    function testTokenPatternWithArbitraryByteLength()
    {
        $sut = $this->systemUnderTest();
        $tokenPattern = $sut->TokenPattern(8);
        $this->assertSame('/^[A-Fa-f0-9]{16}$/', $tokenPattern);
    }

    #endregion TokenPattern

    #region GenerateCsrfPair ---------------------------------------------------

    function testGenerateCsrfPair()
    {
        $sut = $this->systemUnderTest('csrfSecret');

        $sut->expects($this->once())
            ->method('csrfSecret')
            ->willReturn('12345678901234567890123456789012');

        [$token, $cookieValue] = $sut->GenerateCsrfPair();

        $this->assertMatchesRegularExpression(
            self::DEFAULT_TOKEN_PATTERN,
            $token
        );
        $this->assertMatchesRegularExpression(
            self::CSRF_COOKIE_VALUE_PATTERN,
            $cookieValue
        );
    }

    #endregion GenerateCsrfPair

    #region VerifyCsrfPair -----------------------------------------------------

    function testVerifyCsrfPair()
    {
        $sut = $this->systemUnderTest('csrfSecret');

        $sut->expects($this->any())
            ->method('csrfSecret')
            ->willReturn('12345678901234567890123456789012');

        [$token, $cookieValue] = $sut->GenerateCsrfPair();

        $this->assertTrue($sut->VerifyCsrfPair($token, $cookieValue));
        $this->assertFalse($sut->VerifyCsrfPair('invalid', $cookieValue));
        $this->assertFalse($sut->VerifyCsrfPair($token, 'invalid'));
    }

    #endregion VerifyCsrfPair

    #region csrfSecret ---------------------------------------------------------

    #[DataProviderExternal(DataHelper::class, 'NonStringProvider')]
    function testCsrfSecretWhenValueIsNotString($value)
    {
        $sut = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('Option')
            ->with('CsrfSecret')
            ->willReturn($value);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'CSRF secret must be a string of at least 32 characters.');
        AccessHelper::CallMethod($sut, 'csrfSecret');
    }

    function testCsrfSecretWhenValueIsTooShort()
    {
        $sut = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('Option')
            ->with('CsrfSecret')
            ->willReturn('1234567890'); // less than 32 characters

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'CSRF secret must be a string of at least 32 characters.');
        AccessHelper::CallMethod($sut, 'csrfSecret');
    }

    function testCsrfSecretWhenValueIsValid()
    {
        $sut = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('Option')
            ->with('CsrfSecret')
            ->willReturn('12345678901234567890123456789012');

        $this->assertSame(
            '12345678901234567890123456789012',
            AccessHelper::CallMethod($sut, 'csrfSecret')
        );
    }

    #endregion csrfSecret
}
