<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Core\CArray;

use \TestToolkit\AccessHelper;
use \TestToolkit\DataHelper;

#[CoversClass(CArray::class)]
class CArrayTest extends TestCase
{
    #region __construct --------------------------------------------------------

    function testDefaultConstructor()
    {
        $carr = new CArray();
        $this->assertSame([], AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testCopyConstructor()
    {
        $original = new CArray([1, 2, 3]);
        $carr = new CArray($original);
        $this->assertSame(
            AccessHelper::GetNonPublicProperty($original, 'value'),
            AccessHelper::GetNonPublicProperty($carr, 'value')
        );
    }

    function testConstructorWithNativeArray()
    {
        $array = [1, 2, 3];
        $carr = new CArray($array);
        $this->assertSame($array, AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #[DataProviderExternal(DataHelper::class, 'NonArrayProvider')]
    function testConstructorWithInvalidValueType($value)
    {
        $this->expectException(\TypeError::class);
        new CArray($value);
    }

    #endregion __construct

    #region ContainsKey --------------------------------------------------------

    #[DataProvider('containsKeyDataProvider')]
    function testContainsKey(bool $expected, array $array, string|int $key)
    {
        $carr = new CArray($array);
        $this->assertSame($expected, $carr->ContainsKey($key));
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringOrIntegerProvider')]
    function testContainsKeyWithInvalidKeyType($key)
    {
        $carr = new CArray(['a' => 1, 'b' => 2]);
        $this->expectException(\TypeError::class);
        $carr->ContainsKey($key);
    }

    #endregion ContainsKey

    #region Data Providers -----------------------------------------------------

    static function containsKeyDataProvider()
    {
        return [
            'string key that exists' => [
                true, ['a' => 1, 'b' => 2], 'a'
            ],
            'integer key that exists' => [
                true, [0 => 'first', 1 => 'second'], 0
            ],
            'string key that does not exist' => [
                false, ['x' => 10, 'y' => 20], 'z'
            ],
            'integer key that does not exist' => [
                false, [0 => 'first', 1 => 'second'], 2
            ],
            'numeric string key that exists as string' => [
                true, ['1' => 'one', '2' => 'two'], '1'
            ],
            'numeric string key that exists as integer' => [
                true, ['1' => 'one', '2' => 'two'], 1
            ],
            'key in empty array' => [
                false, [], 'key'
            ],
        ];
    }

    #endregion Data Providers
}
