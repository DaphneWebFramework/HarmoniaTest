<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\Requirements\FieldRequirementConstraints;

use \Harmonia\Config;
use \Harmonia\Systems\ValidationSystem\MetaRules\IMetaRule;

#[CoversClass(FieldRequirementConstraints::class)]
class FieldRequirementConstraintsTest extends TestCase
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

    private function metaRule(string $name, mixed $param = null): IMetaRule
    {
        $mock = $this->createMock(IMetaRule::class);
        $mock->method('GetName')->willReturn($name);
        $mock->method('GetParam')->willReturn($param);
        return $mock;
    }

    #region FromMetaRules ------------------------------------------------------

    function testFromMetaRulesExtractsRequiredRule()
    {
        $sut = FieldRequirementConstraints::FromMetaRules([
            $this->metaRule('required')
        ]);

        $this->assertTrue($sut->IsRequired());
        $this->assertFalse($sut->HasRequiredWithoutFields());
        $this->assertEmpty($sut->RequiredWithoutFields());
    }

    function testFromMetaRulesExtractsRequiredWithoutRule()
    {
        $sut = FieldRequirementConstraints::FromMetaRules([
            $this->metaRule('requiredwithout', 'email')
        ]);

        $this->assertFalse($sut->IsRequired());
        $this->assertTrue($sut->HasRequiredWithoutFields());
        $this->assertSame(['email'], $sut->RequiredWithoutFields());
    }

    function testFromMetaRulesExtractsBothRequiredAndRequiredWithoutRules()
    {
        $sut = FieldRequirementConstraints::FromMetaRules([
            $this->metaRule('required'),
            $this->metaRule('requiredwithout', 'email')
        ]);

        $this->assertTrue($sut->IsRequired());
        $this->assertTrue($sut->HasRequiredWithoutFields());
        $this->assertSame(['email'], $sut->RequiredWithoutFields());
    }

    function testFromMetaRulesThrowsIfRequiredWithoutRuleHasNoFieldName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Rule 'requiredWithout' must be used with a field name.");

        FieldRequirementConstraints::FromMetaRules([
            $this->metaRule('requiredwithout')
        ]);
    }

    #endregion FromMetaRules

    #region FormatRequiredWithoutList ------------------------------------------

    function testFormatRequiredWithoutListWithSingleField()
    {
        $sut = FieldRequirementConstraints::FromMetaRules([
            $this->metaRule('requiredwithout', 'email')
        ]);

        $this->assertSame("'email'", $sut->FormatRequiredWithoutList());
    }

    function testFormatRequiredWithoutListWithMultipleFields()
    {
        $sut = FieldRequirementConstraints::FromMetaRules([
            $this->metaRule('requiredwithout', 'email'),
            $this->metaRule('requiredwithout', 'phone')
        ]);

        $this->assertSame("one of 'email', 'phone'", $sut->FormatRequiredWithoutList());
    }

    #endregion FormatRequiredWithoutList
}
