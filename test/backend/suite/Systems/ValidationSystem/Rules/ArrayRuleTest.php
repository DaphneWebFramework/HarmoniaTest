<?php declare(strict_types=1);
namespace suite\Systems\ValidationSystem\Rules;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\Rules\ArrayRule;

use \Harmonia\Systems\ValidationSystem\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(ArrayRule::class)]
class ArrayRuleTest extends TestCase
{
    private function systemUnderTest(): ArrayRule
    {
        return new ArrayRule($this->createMock(NativeFunctions::class));
    }

    #region Validate -----------------------------------------------------------

    function testValidateSucceedsWhenValueIsArray()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsArray')
            ->with([])
            ->willReturn(true);

        $sut->Validate('field1', [], null);
    }

    function testValidateThrowsWhenValueIsNotArray()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsArray')
            ->with('not-an-array')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' must be an array.");
        $sut->Validate('field1', 'not-an-array', null);
    }

    #endregion Validate
}
