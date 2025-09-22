<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Services\SecurityService;

use \Harmonia\Services\Security\CsrfToken;
use \TestToolkit\AccessHelper;

#[CoversClass(SecurityService::class)]
class SecurityServiceTest extends TestCase
{
    private const DEFAULT_TOKEN_PATTERN = '/^[a-f0-9]{64}$/';
    private const CSRF_TOKEN_COOKIE_VALUE_PATTERN = '/^[a-f0-9]{120}$/';

    private function systemUnderTest(string ...$mockedMethods): SecurityService
    {
        return $this->getMockBuilder(SecurityService::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    #region HashPassword -------------------------------------------------------

    function testHashPasswordWithEmptyPassword()
    {
        $sut = $this->systemUnderTest();
        $password = '';
        $hash = $sut->HashPassword($password);
        $this->assertTrue(\password_verify($password, $hash));
    }

    function testHashPasswordWithNonEmptyPassword()
    {
        $sut = $this->systemUnderTest();
        $password = 'pass1234';
        $hash = $sut->HashPassword($password);
        $this->assertTrue(\password_verify($password, $hash));
    }

    #endregion HashPassword

    #region VerifyPassword -----------------------------------------------------

    function testVerifyPasswordWithInvalidPassword()
    {
        $sut = $this->systemUnderTest();
        $password = 'pass1234';
        $hash = \password_hash($password, \PASSWORD_DEFAULT);
        $this->assertFalse($sut->VerifyPassword('invalid', $hash));
    }

    function testVerifyPasswordWithEmptyPassword()
    {
        $sut = $this->systemUnderTest();
        $password = '';
        $hash = \password_hash($password, \PASSWORD_DEFAULT);
        $this->assertTrue($sut->VerifyPassword($password, $hash));
    }

    function testVerifyPasswordWithNonEmptyPassword()
    {
        $sut = $this->systemUnderTest();
        $password = 'pass1234';
        $hash = \password_hash($password, \PASSWORD_DEFAULT);
        $this->assertTrue($sut->VerifyPassword($password, $hash));
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
        $this->assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $token);
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
        $this->assertSame('/^[a-f0-9]{16}$/', $tokenPattern);
    }

    #endregion TokenPattern

    #region GenerateCsrfToken --------------------------------------------------

    function testGenerateCsrfToken()
    {
        $sut = $this->systemUnderTest();
        $csrfToken = $sut->GenerateCsrfToken();
        $this->assertMatchesRegularExpression(
            self::DEFAULT_TOKEN_PATTERN,
            $csrfToken->Token()
        );
        $this->assertMatchesRegularExpression(
            self::CSRF_TOKEN_COOKIE_VALUE_PATTERN,
            $csrfToken->CookieValue()
        );
        $deobfuscatedCookieValue = AccessHelper::CallMethod(
            $sut,
            'deobfuscate',
            [$csrfToken->CookieValue()]
        );
        $this->assertTrue(\password_verify(
            $csrfToken->Token(),
            $deobfuscatedCookieValue
        ));
    }

    #endregion GenerateCsrfToken

    #region VerifyCsrfToken ----------------------------------------------------

    function testVerifyCsrfTokenWithEmptyTokenAndEmptyCookieValue()
    {
        $sut = $this->systemUnderTest();
        $csrfToken = new CsrfToken('', '');
        $this->assertFalse($sut->VerifyCsrfToken($csrfToken));
    }

    function testVerifyCsrfTokenWithInvalidTokenAndInvalidCookieValue()
    {
        $sut = $this->systemUnderTest();
        $csrfToken = new CsrfToken('invalid', 'invalid');
        $this->assertFalse($sut->VerifyCsrfToken($csrfToken));
    }

    function testVerifyCsrfTokenWithValidTokenAndEmptyCookieValue()
    {
        $sut = $this->systemUnderTest();
        $csrfToken = new CsrfToken($sut->GenerateToken(), '');
        $this->assertFalse($sut->VerifyCsrfToken($csrfToken));
    }

    function testVerifyCsrfTokenWithValidTokenAndInvalidCookieValue()
    {
        $sut = $this->systemUnderTest();
        // Odd number of characters.
        $csrfToken = new CsrfToken($sut->GenerateToken(), 'invalid');
        $this->assertFalse($sut->VerifyCsrfToken($csrfToken));
        // Even number of characters.
        $csrfToken = new CsrfToken($sut->GenerateToken(), 'invalid0');
        $this->assertFalse($sut->VerifyCsrfToken($csrfToken));
    }

    function testVerifyCsrfTokenWithValidTokenAndValidCookieValue()
    {
        $sut = $this->systemUnderTest();
        $csrfToken = $sut->GenerateCsrfToken();
        $this->assertTrue($sut->VerifyCsrfToken($csrfToken));
    }

    #endregion VerifyCsrfToken

    #region obfuscate ----------------------------------------------------------

    #[DataProvider('obfuscateDataProvider')]
    public function testObfuscate($expected, $input)
    {
        $sut = $this->systemUnderTest();
        $actual = AccessHelper::CallMethod($sut, 'obfuscate', [$input]);
        $this->assertSame($expected, $actual);
    }

    #endregion obfuscate

    #region deobfuscate --------------------------------------------------------

    #[DataProvider('deobfuscateDataProvider')]
    public function testDeobfuscate($expected, $input)
    {
        $sut = $this->systemUnderTest();
        $actual = AccessHelper::CallMethod($sut, 'deobfuscate', [$input]);
        $this->assertSame($expected, $actual);
    }

    #endregion deobfuscate

    #region Data Providers -----------------------------------------------------

    static function obfuscateDataProvider()
    {
        return [
            'empty' => ['', ''],
            'non-empty' => ['3433323173736170', 'pass1234'],
        ];
    }

    static function deobfuscateDataProvider()
    {
        return [
            'empty' => ['', ''],
            'non-hex' => ['', 'hello'],
            'non-even' => ['', '12345'],
            'lowercase-hex' => ['hello', '6f6c6c6568'],
            'uppercase-hex' => ['hello', '6F6C6C6568'],
            'valid' => ['pass1234', '3433323173736170'],
        ];
    }

    #endregion Data Providers
}
