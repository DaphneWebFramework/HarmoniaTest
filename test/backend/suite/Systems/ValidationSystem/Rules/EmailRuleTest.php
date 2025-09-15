<?php declare(strict_types=1);
namespace suite\Systems\ValidationSystem\Rules;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\Rules\EmailRule;

use \Harmonia\Systems\ValidationSystem\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(EmailRule::class)]
class EmailRuleTest extends TestCase
{
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
