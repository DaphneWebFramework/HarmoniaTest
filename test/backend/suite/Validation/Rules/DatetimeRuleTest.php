<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Validation\Rules\DatetimeRule;

use \Harmonia\Config;
use \Harmonia\Validation\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(DatetimeRule::class)]
class DatetimeRuleTest extends TestCase
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

    private function systemUnderTest(): DatetimeRule
    {
        return new DatetimeRule($this->createMock(NativeFunctions::class));
    }

    #region Validate -----------------------------------------------------------

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
        $sut->Validate('field1', 12345, 'Y-m-d');
    }

    function testValidateThrowsWhenParamIsNotString()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsString')
            ->willReturnMap([
                ['2025-03-10', true],
                [12345, false]
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Rule 'datetime' must be used with a valid datetime format.");
        $sut->Validate('field1', '2025-03-10', 12345);
    }

    function testValidateSucceedsWhenValueMatchesFormat()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsString')
            ->willReturnMap([
                ['2025-03-10', true],
                ['Y-m-d', true]
            ]);
        $nativeFunctions->expects($this->once())
            ->method('MatchDateTime')
            ->with('2025-03-10', 'Y-m-d')
            ->willReturn(true);

        $sut->Validate('field1', '2025-03-10', 'Y-m-d');
    }

    function testValidateThrowsWhenValueDoesNotMatchFormat()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsString')
            ->willReturnMap([
                ['03/10/2025', true],
                ['Y-m-d', true]
            ]);
        $nativeFunctions->expects($this->once())
            ->method('MatchDateTime')
            ->with('03/10/2025', 'Y-m-d')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'field1' must match the datetime format: Y-m-d");
        $sut->Validate('field1', '03/10/2025', 'Y-m-d');
    }

    #endregion Validate
}
