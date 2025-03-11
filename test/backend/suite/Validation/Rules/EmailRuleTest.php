<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Validation\Rules\EmailRule;

use \Harmonia\Config;
use \Harmonia\Validation\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(EmailRule::class)]
class EmailRuleTest extends TestCase
{
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalConfig = Config::ReplaceInstance($this->createConfigMock());
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalConfig);
    }

    private function createConfigMock()
    {
        $mock = $this->createMock(Config::class);
        $mock->expects($this->any())
            ->method('Option')
            ->with('Language')
            ->willReturn('en');
        return $mock;
    }

    private function systemUnderTest(): EmailRule
    {
        return new EmailRule($this->createMock(NativeFunctions::class));
    }

    #region Validate -----------------------------------------------------------

    function testValidateSucceedsWhenValueIsValidEmail()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsEmailAddress')
            ->with('user@example.com')
            ->willReturn(true);

        $sut->Validate('field1', 'user@example.com', null);
    }

    function testValidateThrowsWhenValueIsNotValidEmail()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsEmailAddress')
            ->with('invalid-email')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' must be a valid email address.");
        $sut->Validate('field1', 'invalid-email', null);
    }

    #endregion Validate
}
