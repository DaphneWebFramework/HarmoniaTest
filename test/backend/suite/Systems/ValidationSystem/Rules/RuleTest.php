<?php declare(strict_types=1);
namespace suite\Systems\ValidationSystem\Rules;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;

use \Harmonia\Systems\ValidationSystem\Rules\Rule;

use \Harmonia\Systems\ValidationSystem\NativeFunctions;
use \TestToolkit\AccessHelper;

class TestRule extends Rule {
    public function Validate(string|int $field, mixed $value, mixed $param): void {}
}

#[CoversClass(Rule::class)]
class RuleTest extends TestCase
{
    #region __construct --------------------------------------------------------

    function testConstructorAssignsNativeFunctions()
    {
        $nativeFunctions = $this->createStub(NativeFunctions::class);
        $rule = new TestRule($nativeFunctions);

        $this->assertSame(
            $nativeFunctions,
            AccessHelper::GetProperty($rule, 'nativeFunctions')
        );
    }

    #endregion __construct
}
