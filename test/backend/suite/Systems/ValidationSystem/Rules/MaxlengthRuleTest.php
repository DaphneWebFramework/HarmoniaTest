<?php declare(strict_types=1);
namespace suite\Systems\ValidationSystem\Rules;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\Rules\MaxlengthRule;

use \Harmonia\Systems\ValidationSystem\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(MaxlengthRule::class)]
class MaxlengthRuleTest extends TestCase
{
    private function systemUnderTest(): MaxlengthRule
    {
        return new MaxlengthRule($this->createMock(NativeFunctions::class));
    }

    #region Validate -----------------------------------------------------------

    function testValidateThrowsWhenValueIsNotString()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with(123)
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' must be a string.");
        $sut->Validate('field1', 123, 5);
    }

    function testValidateThrowsWhenParamIsNotIntegerLike()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with('abc')
            ->willReturn(true);
        $nativeFunctions->expects($this->once())
            ->method('IsIntegerLike')
            ->with('not-an-int')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Rule 'maxLength' must be used with an integer.");
        $sut->Validate('field1', 'abc', 'not-an-int');
    }

    function testValidateSucceedsWhenStringLengthIsLessThanMax()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with('abc')
            ->willReturn(true);
        $nativeFunctions->expects($this->once())
            ->method('IsIntegerLike')
            ->with(5)
            ->willReturn(true);

        $sut->Validate('field1', 'abc', 5);
    }

    function testValidateSucceedsWhenStringLengthEqualsMax()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with('abcde')
            ->willReturn(true);
        $nativeFunctions->expects($this->once())
            ->method('IsIntegerLike')
            ->with(5)
            ->willReturn(true);

        $sut->Validate('field1', 'abcde', 5);
    }

    function testValidateThrowsWhenStringLengthExceedsMax()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with('abcdef')
            ->willReturn(true);
        $nativeFunctions->expects($this->once())
            ->method('IsIntegerLike')
            ->with(5)
            ->willReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'field1' must have a maximum length of 5 characters.");
        $sut->Validate('field1', 'abcdef', 5);
    }

    #endregion Validate
}
