<?php declare(strict_types=1);
namespace suite\Systems\ValidationSystem\Rules;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\Rules\MinRule;

use \Harmonia\Config;
use \Harmonia\Systems\ValidationSystem\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(MinRule::class)]
class MinRuleTest extends TestCase
{
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalConfig = Config::ReplaceInstance($this->createConfig());
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalConfig);
    }

    private function createConfig(): Config
    {
        $mock = $this->createMock(Config::class);
        $mock->method('Option')->with('Language')->willReturn('en');
        return $mock;
    }

    private function systemUnderTest(): MinRule
    {
        return new MinRule($this->createMock(NativeFunctions::class));
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
        $sut->Validate('field1', 'non-numeric', 10);
    }

    function testValidateThrowsWhenParamIsNotNumeric()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsNumeric')
            ->willReturnMap([
                [5, true],
                ['non-numeric', false]
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Rule 'min' must be used with a number.");
        $sut->Validate('field1', 5, 'non-numeric');
    }

    function testValidateSucceedsWhenValueExceedsMin()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsNumeric')
            ->willReturnMap([
                [20, true],
                [10, true]
            ]);

        $sut->Validate('field1', 20, 10);
    }

    function testValidateSucceedsWhenValueEqualsMin()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsNumeric')
            ->willReturnMap([
                [10, true],
                [10, true]
            ]);

        $sut->Validate('field1', 10, 10);
    }

    function testValidateThrowsWhenValueIsLessThanMin()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsNumeric')
            ->willReturnMap([
                [5, true],
                [10, true]
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' must have a minimum value of 10.");
        $sut->Validate('field1', 5, 10);
    }

    #endregion Validate
}
