<?php declare(strict_types=1);
namespace suite\Systems\ValidationSystem\Rules;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\Rules\StringRule;

use \Harmonia\Systems\ValidationSystem\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(StringRule::class)]
class StringRuleTest extends TestCase
{
    private function systemUnderTest(): StringRule
    {
        return new StringRule($this->createMock(NativeFunctions::class));
    }

    #region Validate -----------------------------------------------------------

    function testValidateSucceedsWhenValueIsString()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with('valid-string')
            ->willReturn(true);

        $sut->Validate('field1', 'valid-string', null);
    }

    function testValidateThrowsWhenValueIsNotString()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with(12345)
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' must be a string.");
        $sut->Validate('field1', 12345, null);
    }

    #endregion Validate
}
