<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\MetaRules\StandardMetaRule;

use \Harmonia\Config;
use \Harmonia\Core\CArray;
use \Harmonia\Systems\ValidationSystem\RuleFactory;
use \TestToolkit\AccessHelper;

#[CoversClass(StandardMetaRule::class)]
class StandardMetaRuleTest extends TestCase
{
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalConfig = Config::ReplaceInstance($this->config());
        AccessHelper::SetStaticProperty(RuleFactory::class, 'ruleObjects', null);
        AccessHelper::SetStaticProperty(RuleFactory::class, 'nativeFunctions', null);
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

    #region GetName ------------------------------------------------------------

    function testGetNameReturnsRuleName()
    {
        $sut = new StandardMetaRule('email', null);
        $this->assertEquals('email', $sut->GetName());
    }

    function testGetNameReturnsRuleNameInExactCase()
    {
        $sut = new StandardMetaRule('eMaIl', null);
        $this->assertEquals('eMaIl', $sut->GetName());
    }

    #endregion GetName

    #region GetParam -----------------------------------------------------------

    function testGetParamReturnsParam()
    {
        $sut = new StandardMetaRule('min', 10);
        $this->assertEquals(10, $sut->GetParam());
    }

    #endregion GetParam

    #region Validate -----------------------------------------------------------

    function testValidateThrowsWhenRuleDoesNotExist()
    {
        $sut = new StandardMetaRule('nonexistent', null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unknown rule 'nonexistent'");
        $sut->Validate('field1', 'value');
    }

    function testValidateThrowsWhenRuleFails()
    {
        $sut = new StandardMetaRule('regex', '/^[a-z]+$/');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'field1' must match the required pattern: /^[a-z]+$/");
        $sut->Validate('field1', '1234');
    }

    function testValidateSucceedsWhenRuleExists()
    {
        $sut = new StandardMetaRule('regex', '/^\d{9}$/');

        $sut->Validate('passportNumber', '987654321');
        $this->expectNotToPerformAssertions();
    }

    #endregion Validate
}
