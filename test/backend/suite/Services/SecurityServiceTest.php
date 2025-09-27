<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\TestWith;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Services\SecurityService;

use \Harmonia\Config;
use \TestToolkit\AccessHelper;
use \TestToolkit\DataHelper;

#[CoversClass(SecurityService::class)]
class SecurityServiceTest extends TestCase
{
    private const CSRF_COOKIE_VALUE_PATTERN = '/^[0-9a-fA-F]{64}$/';

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

        $this->assertMatchesRegularExpression(
            SecurityService::TOKEN_DEFAULT_PATTERN,
            $sut->GenerateToken()
        );
    }

    #[TestWith([-1])]
    #[TestWith([0])]
    function testGenerateTokenThrowsIfByteLengthIsLessThanOne($byteLength)
    {
        $sut = $this->systemUnderTest();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Byte length must be at least 1.');
        $sut->GenerateToken($byteLength);
    }

    #[TestWith([1])]
    #[TestWith([2])]
    #[TestWith([8])]
    #[TestWith([16])]
    #[TestWith([17])]
    function testGenerateTokenWithArbitraryByteLength($byteLength)
    {
        $sut = $this->systemUnderTest();
        $pattern = '/^[0-9a-fA-F]{' . ($byteLength * 2) . '}$/';

        $this->assertMatchesRegularExpression(
            $pattern,
            $sut->GenerateToken($byteLength)
        );
    }

    #endregion GenerateToken

    #region TokenPattern -------------------------------------------------------

    function testTokenPatternWithDefaultByteLength()
    {
        $sut = $this->systemUnderTest();

        $this->assertSame(
            SecurityService::TOKEN_DEFAULT_PATTERN,
            $sut->TokenPattern()
        );
    }

    #[TestWith([-1])]
    #[TestWith([0])]
    function testTokenPatternThrowsIfByteLengthIsLessThanOne($byteLength)
    {
        $sut = $this->systemUnderTest();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Byte length must be at least 1.');
        $sut->TokenPattern($byteLength);
    }

    #[TestWith([1])]
    #[TestWith([2])]
    #[TestWith([8])]
    #[TestWith([16])]
    #[TestWith([17])]
    function testTokenPatternWithArbitraryByteLength($byteLength)
    {
        $sut = $this->systemUnderTest();
        $pattern = '/^[0-9a-fA-F]{' . ($byteLength * 2) . '}$/';

        $this->assertSame(
            $pattern,
            $sut->TokenPattern($byteLength)
        );
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
            SecurityService::TOKEN_DEFAULT_PATTERN,
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
