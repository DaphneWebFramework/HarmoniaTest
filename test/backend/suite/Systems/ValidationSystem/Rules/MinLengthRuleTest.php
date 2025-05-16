<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\Rules\MinlengthRule;

use \Harmonia\Config;
use \Harmonia\Systems\ValidationSystem\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(MinlengthRule::class)]
class MinlengthRuleTest extends TestCase
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

    private function systemUnderTest(): MinlengthRule
    {
        return new MinlengthRule($this->createMock(NativeFunctions::class));
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
        $sut->Validate('field1', 123, 3);
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
        $this->expectExceptionMessage("Rule 'minLength' must be used with an integer.");
        $sut->Validate('field1', 'abc', 'not-an-int');
    }

    function testValidateSucceedsWhenStringLengthExceedsMin()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with('abcdef')
            ->willReturn(true);
        $nativeFunctions->expects($this->once())
            ->method('IsIntegerLike')
            ->with(3)
            ->willReturn(true);

        $sut->Validate('field1', 'abcdef', 3);
    }

    function testValidateSucceedsWhenStringLengthEqualsMin()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with('abc')
            ->willReturn(true);
        $nativeFunctions->expects($this->once())
            ->method('IsIntegerLike')
            ->with(3)
            ->willReturn(true);

        $sut->Validate('field1', 'abc', 3);
    }

    function testValidateThrowsWhenStringLengthIsLessThanMin()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsString')
            ->with('ab')
            ->willReturn(true);
        $nativeFunctions->expects($this->once())
            ->method('IsIntegerLike')
            ->with(3)
            ->willReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'field1' must have a minimum length of 3 characters.");
        $sut->Validate('field1', 'ab', 3);
    }

    #endregion Validate
}
