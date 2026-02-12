<?php declare(strict_types=1);
namespace suite\Systems\ValidationSystem\Rules;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\Rules\NumericRule;

use \Harmonia\Systems\ValidationSystem\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(NumericRule::class)]
class NumericRuleTest extends TestCase
{
    private function systemUnderTest(): NumericRule
    {
        return new NumericRule($this->createMock(NativeFunctions::class));
    }

    #region Validate -----------------------------------------------------------

    function testValidateSucceedsWithNoParameterWhenValueIsNumeric()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsNumeric')
            ->with(123)
            ->willReturn(true);
        $nativeFunctions->expects($this->never())
            ->method('IsNumber');

        $sut->Validate('field1', 123, null);
    }

    function testValidateThrowsWithNoParameterWhenValueIsNotNumeric()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsNumeric')
            ->with('non-numeric')
            ->willReturn(false);
        $nativeFunctions->expects($this->never())
            ->method('IsNumber');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' must be numeric.");
        $sut->Validate('field1', 'non-numeric', null);
    }

    function testValidateSucceedsWithStrictParameterWhenValueIsNumber()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsNumber')
            ->with(123.45)
            ->willReturn(true);
        $nativeFunctions->expects($this->never())
            ->method('IsNumeric');

        $sut->Validate('field1', 123.45, 'strict');
    }

    function testValidateThrowsWithStrictParameterWhenValueIsNotNumber()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsNumber')
            ->with('123')
            ->willReturn(false);
        $nativeFunctions->expects($this->never())
            ->method('IsNumeric');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' must be numeric.");
        $sut->Validate('field1', '123', 'strict');
    }

    function testValidateThrowsWhenParameterIsInvalid()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->never())
            ->method('IsNumber');
        $nativeFunctions->expects($this->never())
            ->method('IsNumeric');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Rule 'numeric' must be used with either 'strict' or no parameter.");
        $sut->Validate('field1', 123, 'banana');
    }

    #endregion Validate
}
