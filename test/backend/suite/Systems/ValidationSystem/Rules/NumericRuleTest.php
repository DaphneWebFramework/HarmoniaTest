<?php declare(strict_types=1);
namespace suite\Systems\ValidationSystem\Rules;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\Rules\NumericRule;

use \Harmonia\Config;
use \Harmonia\Systems\ValidationSystem\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(NumericRule::class)]
class NumericRuleTest extends TestCase
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

    private function systemUnderTest(): NumericRule
    {
        return new NumericRule($this->createMock(NativeFunctions::class));
    }

    #region Validate -----------------------------------------------------------

    function testValidateSucceedsWhenValueIsNumeric()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsNumeric')
            ->with(123)
            ->willReturn(true);

        $sut->Validate('field1', 123, null);
    }

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
        $sut->Validate('field1', 'non-numeric', null);
    }

    #endregion Validate
}
