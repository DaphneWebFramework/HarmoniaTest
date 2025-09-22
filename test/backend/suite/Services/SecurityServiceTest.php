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
    private const TOKEN_PATTERN = '/^[a-f0-9]{64}$/';
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
        $password = 'pass123';
        $hash = $sut->HashPassword($password);
        $this->assertTrue(\password_verify($password, $hash));
    }

    #endregion HashPassword

    #region VerifyPassword -----------------------------------------------------

    function testVerifyPasswordWithInvalidPassword()
    {
        $sut = $this->systemUnderTest();
        $password = 'pass123';
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
        $password = 'pass123';
        $hash = \password_hash($password, \PASSWORD_DEFAULT);
        $this->assertTrue($sut->VerifyPassword($password, $hash));
    }

    #endregion VerifyPassword

    #region GenerateToken ------------------------------------------------------

    function testGenerateToken()
    {
        $sut = $this->systemUnderTest();
        $token = $sut->GenerateToken();
        $this->assertMatchesRegularExpression(self::TOKEN_PATTERN, $token);
    }

    #endregion GenerateToken

    #region GenerateCsrfToken --------------------------------------------------

    function testGenerateCsrfToken()
    {
        $sut = $this->systemUnderTest();
        $csrfToken = $sut->GenerateCsrfToken();
        $this->assertMatchesRegularExpression(
            self::TOKEN_PATTERN, $csrfToken->Token());
        $this->assertMatchesRegularExpression(
            self::CSRF_TOKEN_COOKIE_VALUE_PATTERN, $csrfToken->CookieValue());
        $deobfuscatedCookieValue = AccessHelper::CallMethod(
            $sut, 'deobfuscate', [$csrfToken->CookieValue()]);
        $this->assertTrue(\password_verify($csrfToken->Token(),
                                           $deobfuscatedCookieValue));
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
}
