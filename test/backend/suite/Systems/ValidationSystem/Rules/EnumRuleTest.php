<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\Rules\EnumRule;

use \Harmonia\Config;
use \Harmonia\Systems\ValidationSystem\NativeFunctions;
use \TestToolkit\AccessHelper;

enum TestEnum: int {
    case Zero = 0;
    case One = 1;
    case Two = 2;
}

#[CoversClass(EnumRule::class)]
class EnumRuleTest extends TestCase
{
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalConfig = Config::ReplaceInstance($this->config());
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalConfig);
    }

    private function config()
    {
        $mock = $this->createMock(Config::class);
        $mock->method('Option')->with('Language')->willReturn('en');
        return $mock;
    }

    private function systemUnderTest(): EnumRule
    {
        return new EnumRule($this->createMock(NativeFunctions::class));
    }

    #region Validate -----------------------------------------------------------

    function testValidateThrowsWhenParamIsNotString()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with(123)
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Rule 'enum' must be used with a valid enum class name.");
        $sut->Validate('field1', 'value1', 123);
    }

    function testValidateThrowsWhenValueIsNotEnumValue()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with(TestEnum::class)
            ->willReturn(true);
        $nativeFunctions->expects($this->once())
            ->method('IsEnumValue')
            ->with('value1', TestEnum::class)
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' must be a valid value of enum 'TestEnum'.");
        $sut->Validate('field1', 'value1', TestEnum::class);
    }

    function testValidateSucceedsWhenValueIsEnumValue()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with(TestEnum::class)
            ->willReturn(true);
        $nativeFunctions->expects($this->once())
            ->method('IsEnumValue')
            ->with(1, TestEnum::class)
            ->willReturn(true);

        $sut->Validate('field1', 1, TestEnum::class);
    }

    #endregion Validate
}
