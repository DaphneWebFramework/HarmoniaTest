<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Validation\Rules\RegexRule;

use \Harmonia\Config;
use \Harmonia\Validation\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(RegexRule::class)]
class RegexRuleTest extends TestCase
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

    private function systemUnderTest(): RegexRule
    {
        return new RegexRule($this->createMock(NativeFunctions::class));
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
        $sut->Validate('field1', 12345, '/^[a-z]+$/');
    }

    function testValidateThrowsWhenParamIsNotString()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsString')
            ->willReturnMap([
                ['hello', true],
                [12345, false]
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Rule 'regex' must be used with a valid pattern.");
        $sut->Validate('field1', 'hello', 12345);
    }

    function testValidateSucceedsWhenValueMatchesPattern()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsString')
            ->willReturnMap([
                ['hello', true],
                ['/^[a-z]+$/', true]
            ]);
        $nativeFunctions->expects($this->once())
            ->method('MatchRegex')
            ->with('hello', '/^[a-z]+$/')
            ->willReturn(true);

        $sut->Validate('field1', 'hello', '/^[a-z]+$/');
    }

    function testValidateThrowsWhenValueDoesNotMatchPattern()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->exactly(2))
            ->method('IsString')
            ->willReturnMap([
                ['123', true],
                ['/^[a-z]+$/', true]
            ]);
        $nativeFunctions->expects($this->once())
            ->method('MatchRegex')
            ->with('123', '/^[a-z]+$/')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'field1' must match the required pattern: /^[a-z]+$/");
        $sut->Validate('field1', '123', '/^[a-z]+$/');
    }

    #endregion Validate
}
