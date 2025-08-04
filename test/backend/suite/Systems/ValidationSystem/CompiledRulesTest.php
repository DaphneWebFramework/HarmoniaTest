<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\CompiledRules;

use \Harmonia\Config;
use \Harmonia\Systems\ValidationSystem\MetaRules\CustomMetaRule;
use \Harmonia\Systems\ValidationSystem\MetaRules\StandardMetaRule;

#[CoversClass(CompiledRules::class)]
class CompiledRulesTest extends TestCase
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

    #region __construct --------------------------------------------------------

    function testConstructorCompilesStandardRules()
    {
        $sut = new CompiledRules([
            'name' => 'required',
            'email' => 'email'
        ]);
        $mrc = $sut->MetaRulesCollection();

        $this->assertInstanceOf(StandardMetaRule::class, $mrc['name'][0]);
        $this->assertInstanceOf(StandardMetaRule::class, $mrc['email'][0]);
    }

    function testConstructorCompilesCustomRules()
    {
        $sut = new CompiledRules([
            'age' => function($value) { return $value >= 18; }
        ]);
        $mrc = $sut->MetaRulesCollection();

        $this->assertInstanceOf(CustomMetaRule::class, $mrc['age'][0]);
    }

    function testConstructorCompilesMixedRules()
    {
        $sut = new CompiledRules([
            'id' => ['required', 'integer', 'min:1'],
            'name' => 'string',
            'age' => function($value) { return $value >= 18; }
        ]);
        $mrc = $sut->MetaRulesCollection();

        $this->assertInstanceOf(StandardMetaRule::class, $mrc['id'][0]);
        $this->assertInstanceOf(StandardMetaRule::class, $mrc['id'][1]);
        $this->assertInstanceOf(StandardMetaRule::class, $mrc['id'][2]);
        $this->assertInstanceOf(StandardMetaRule::class, $mrc['name'][0]);
        $this->assertInstanceOf(CustomMetaRule::class, $mrc['age'][0]);
    }

    function testConstructorThrowsWhenRuleIsInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rule must be a non-empty string.');

        new CompiledRules([
            'name' => ''
        ]);
    }

    #endregion __construct
}
