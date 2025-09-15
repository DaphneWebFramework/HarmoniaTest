<?php declare(strict_types=1);
namespace suite\Systems\ValidationSystem;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Systems\ValidationSystem\RuleFactory;

use \Harmonia\Config;
use \TestToolkit\AccessHelper;

#[CoversClass(RuleFactory::class)]
class RuleFactoryTest extends TestCase
{
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalConfig = Config::ReplaceInstance($this->createConfig());
        AccessHelper::SetStaticProperty(RuleFactory::class, 'ruleObjects', null);
        AccessHelper::SetStaticProperty(RuleFactory::class, 'nativeFunctions', null);
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

    #region Create -------------------------------------------------------------

    #[DataProvider('invalidRuleNameDataProvider')]
    function testCreateThrowsIfRuleNameIsInvalid($ruleName)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Rule name must be non-empty, trimmed, and lowercased');
        RuleFactory::Create($ruleName);
    }

    function testCreateReturnsNullIfRuleDoesNotExist()
    {
        $this->assertNull(RuleFactory::Create('non-existent-rule'));
    }

    #[DataProvider('existingRuleNameDataProvider')]
    function testCreateReturnsRuleObjectWhenRuleExists($ruleName)
    {
        $rule = RuleFactory::Create($ruleName);

        $ruleClassName = '\\Harmonia\\Systems\\ValidationSystem\\Rules\\'
                       . \ucfirst(\strtolower($ruleName))
                       . 'Rule';
        $this->assertInstanceof($ruleClassName, $rule);
    }

    function testCreateRetrievesRuleFromCacheWhenCalledMoreThanOnce()
    {
        $rule1 = RuleFactory::Create('string');
        $rule2 = RuleFactory::Create('string');
        $this->assertSame($rule1, $rule2);
    }

    function testCreateConstructsRulesWithSameNativeFunctionsInstance()
    {
        $rule1 = RuleFactory::Create('string');
        $rule2 = RuleFactory::Create('integer');

        $nativeFunctions1 = AccessHelper::GetProperty($rule1, 'nativeFunctions');
        $nativeFunctions2 = AccessHelper::GetProperty($rule2, 'nativeFunctions');
        $nativeFunctions =
            AccessHelper::GetStaticProperty(RuleFactory::class, 'nativeFunctions');

        $this->assertSame($nativeFunctions1, $nativeFunctions2);
        $this->assertSame($nativeFunctions1, $nativeFunctions);
    }

    #endregion Create

    #region Data Providers -----------------------------------------------------

    static function invalidRuleNameDataProvider()
    {
        return [
            'empty' => [''],
            'whitespace' => [' '],
            'non-trimmed' => [' regex '],
            'non-lowercase' => ['ReGeX'],
        ];
    }

    /**
     * @todo When adding new rule classes, add their names here as well.
     */
    static function existingRuleNameDataProvider()
    {
        return [
            ['array'], ['datetime'], ['email'], ['file'], ['integer'],
            ['maxlength'], ['max'], ['minlength'], ['min'], ['numeric'],
            ['regex'], ['string']
        ];
    }


    #endregion Data Providers
}
