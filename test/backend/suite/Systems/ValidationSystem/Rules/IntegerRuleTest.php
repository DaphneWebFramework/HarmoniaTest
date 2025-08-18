<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Systems\ValidationSystem\Rules\IntegerRule;

use \Harmonia\Config;
use \Harmonia\Systems\ValidationSystem\NativeFunctions;
use \TestToolkit\AccessHelper;

#[CoversClass(IntegerRule::class)]
class IntegerRuleTest extends TestCase
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

    private function systemUnderTest(): IntegerRule
    {
        return new IntegerRule($this->createMock(NativeFunctions::class));
    }

    #region Validate -----------------------------------------------------------

    function testValidateSucceedsWithNoParameterWhenValueIsIntegerLike()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsIntegerLike')
            ->with(123)
            ->willReturn(true);
        $nativeFunctions->expects($this->never())
            ->method('IsInteger');

        $sut->Validate('field1', 123, null);
    }

    function testValidateThrowsWithNoParameterWhenValueIsNotIntegerLike()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsIntegerLike')
            ->with('not-an-int')
            ->willReturn(false);
        $nativeFunctions->expects($this->never())
            ->method('IsInteger');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' must be an integer.");
        $sut->Validate('field1', 'not-an-int', null);
    }

    function testValidateSucceedsWithStrictParameterWhenValueIsInteger()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsInteger')
            ->with(123)
            ->willReturn(true);
        $nativeFunctions->expects($this->never())
            ->method('IsIntegerLike');

        $sut->Validate('field1', 123, 'strict');
    }

    function testValidateThrowsWithStrictParameterWhenValueIsNotInteger()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->once())
            ->method('IsInteger')
            ->with('123')
            ->willReturn(false);
        $nativeFunctions->expects($this->never())
            ->method('IsIntegerLike');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' must be an integer.");
        $sut->Validate('field1', '123', 'strict');
    }

    function testValidateThrowsWhenParameterIsInvalid()
    {
        $sut = $this->systemUnderTest();
        $nativeFunctions = AccessHelper::GetProperty($sut, 'nativeFunctions');

        $nativeFunctions->expects($this->never())
            ->method('IsInteger');
        $nativeFunctions->expects($this->never())
            ->method('IsIntegerLike');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Rule 'integer' must be used with either 'strict' or no parameter.");
        $sut->Validate('field1', 123, 'banana');
    }

    #endregion Validate
}
