<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Validation\Rules\MaxRule;

use \Harmonia\Config;
use \Harmonia\Validation\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(MaxRule::class)]
class MaxRuleTest extends TestCase
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

    private function systemUnderTest(): MaxRule
    {
        return new MaxRule($this->createMock(NativeFunctions::class));
    }

    #region Validate -----------------------------------------------------------

    function testValidateThrowsWhenValueIsNotNumeric()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsNumeric')
            ->with('non-numeric')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' must be numeric.");
        $sut->Validate('field1', 'non-numeric', 100);
    }

    function testValidateThrowsWhenParamIsNotNumeric()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsNumeric')
            ->willReturnMap([
                [50, true],
                ['non-numeric', false]
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Rule 'max' must be used with a number.");
        $sut->Validate('field1', 50, 'non-numeric');
    }

    function testValidateSucceedsWhenValueIsLessThanMax()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsNumeric')
            ->willReturnMap([
                [50, true],
                [100, true]
            ]);

        $sut->Validate('field1', 50, 100);
    }

    function testValidateSucceedsWhenValueEqualsMax()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsNumeric')
            ->willReturnMap([
                [100, true],
                [100, true]
            ]);

        $sut->Validate('field1', 100, 100);
    }

    function testValidateThrowsWhenValueExceedsMax()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsNumeric')
            ->willReturnMap([
                [150, true],
                [100, true]
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' must have a maximum value of 100.");
        $sut->Validate('field1', 150, 100);
    }

    #endregion Validate
}
