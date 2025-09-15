<?php declare(strict_types=1);
namespace suite\Systems\ValidationSystem\MetaRules;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\MetaRules\CustomMetaRule;

#[CoversClass(CustomMetaRule::class)]
class CustomMetaRuleTest extends TestCase
{
    #region GetName ------------------------------------------------------------

    function testGetNameReturnsEmptyString()
    {
        $sut = new CustomMetaRule(function() {});
        $this->assertEquals('', $sut->GetName());
    }

    #endregion GetName

    #region GetParam -----------------------------------------------------------

    function testGetParamReturnsNull()
    {
        $sut = new CustomMetaRule(function() {});
        $this->assertNull($sut->GetParam());
    }

    #endregion GetParam

    #region Validate -----------------------------------------------------------

    function testValidateSucceedsWhenCallableReturnsNothing()
    {
        $sut = new CustomMetaRule(function($value) {});
        $sut->Validate('field1', 42);
        $this->expectNotToPerformAssertions();
    }

    function testValidateSucceedsWhenCallableReturnsTrue()
    {
        $sut = new CustomMetaRule(function($value) {
            return $value === 42;
        });
        $sut->Validate('field1', 42);
        $this->expectNotToPerformAssertions();
    }

    function testValidateThrowsWhenCallableReturnsFalse()
    {
        $sut = new CustomMetaRule(function($value) {
            return $value === 42;
        });
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Field 'field1' failed custom validation.");
        $sut->Validate('field1', 43);
    }

    #endregion Validate
}
