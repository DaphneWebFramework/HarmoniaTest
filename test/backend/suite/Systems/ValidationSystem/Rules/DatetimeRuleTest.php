<?php declare(strict_types=1);
namespace suite\Systems\ValidationSystem\Rules;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\Rules\DatetimeRule;

use \Harmonia\Systems\ValidationSystem\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(DatetimeRule::class)]
class DatetimeRuleTest extends TestCase
{
    private function systemUnderTest(): DatetimeRule
    {
        return new DatetimeRule($this->createMock(NativeFunctions::class));
    }

    #region Validate -----------------------------------------------------------

    function testValidateSucceedsWithNoParameterWhenValueIsDateTime()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsDateTime')
            ->with('2025-03-10T20:35:51Z')
            ->willReturn(true);

        $sut->Validate('field1', '2025-03-10T20:35:51Z', null);
    }

    function testValidateThrowsWithNoParameterWhenValueIsNotDateTime()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsDateTime')
            ->with('not-a-date')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'field1' must be a valid datetime string.");
        $sut->Validate('field1', 'not-a-date', null);
    }

    function testValidateSucceedsWithFormatParameterWhenValueMatchesFormat()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with('Y-m-d')
            ->willReturn(true);
        $nativeFunctions->expects($this->once())
            ->method('MatchDateTime')
            ->with('2025-03-10', 'Y-m-d')
            ->willReturn(true);

        $sut->Validate('field1', '2025-03-10', 'Y-m-d');
    }

    function testValidateThrowsWithFormatParameterWhenValueDoesNotMatchFormat()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with('Y-m-d')
            ->willReturn(true);
        $nativeFunctions->expects($this->once())
            ->method('MatchDateTime')
            ->with('03/10/2025', 'Y-m-d')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'field1' must match the exact datetime format: Y-m-d");
        $sut->Validate('field1', '03/10/2025', 'Y-m-d');
    }

    function testValidateThrowsWhenParameterIsInvalid()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with(12345)
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Rule 'datetime' must be used with either a format string or no parameter.");
        $sut->Validate('field1', '2025-03-10', 12345);
    }

    #endregion Validate
}
