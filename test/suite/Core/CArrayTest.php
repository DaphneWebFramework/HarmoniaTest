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
        $copy = new CArray($original);
        $this->assertSame(
            AccessHelper::GetNonPublicProperty($original, 'value'),
            AccessHelper::GetNonPublicProperty($copy, 'value')
        );
    }

    function testConstructorWithNativeArray()
    {
        $arr = [1, 2, 3];
        $carr = new CArray($arr);
        $this->assertSame($arr, AccessHelper::GetNonPublicProperty($carr, 'value'));
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
    function testContainsKey(bool $expected, array $arr, string|int $key)
    {
        $carr = new CArray($arr);
        $this->assertSame($expected, $carr->ContainsKey($key));
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringOrIntegerProvider')]
    function testContainsKeyWithInvalidKeyType($key)
    {
        $carr = new CArray();
        $this->expectException(\TypeError::class);
        $carr->ContainsKey($key);
    }

    #endregion ContainsKey

    #region ValueOrDefault -----------------------------------------------------

    #[DataProvider('valueOrDefaultDataProvider')]
    function testValueOrDefault(mixed $expected, array $arr, string|int $key,
        mixed $defaultValue = null)
    {
        $carr = new CArray($arr);
        $this->assertSame($expected, $carr->ValueOrDefault($key, $defaultValue));
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringOrIntegerProvider')]
    function testValueOrDefaultWithInvalidKeyType($key)
    {
        $carr = new CArray();
        $this->expectException(\TypeError::class);
        $carr->ValueOrDefault($key);
    }

    #endregion ValueOrDefault

    #region Delete -------------------------------------------------------------

    #[DataProviderExternal(DataHelper::class, 'NonStringOrIntegerProvider')]
    function testDeleteWithInvalidKeyType($key)
    {
        $carr = new CArray();
        $this->expectException(\TypeError::class);
        $carr->Delete($key);
    }

    #[DataProvider('deleteDataProvider')]
    public function testDelete(array $expected, array $arr, string|int $key)
    {
        $carr = new CArray($arr);
        $carr->Delete($key);
        $this->assertSame($expected, AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    function testDeleteChaining()
    {
        $carr = new CArray(['x' => 10, 'y' => 20, 'z' => 30]);
        $carr->Delete('x')->Delete('z');
        $this->assertSame(['y' => 20],
            AccessHelper::GetNonPublicProperty($carr, 'value'));
    }

    #endregion Delete

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
                false, [], 'missing'
            ],
        ];
    }

    static function valueOrDefaultDataProvider()
    {
        return [
            'string key that exists' => [
                42, ['a' => 42, 'b' => 24], 'a'
            ],
            'string key that does not exist with null default' => [
                null, ['a' => 42, 'b' => 24], 'c'
            ],
            'string key that does not exist with non-null default' => [
                'default', ['a' => 42, 'b' => 24], 'c', 'default'
            ],
            'integer key that exists' => [
                'one', [1 => 'one', 2 => 'two'], 1
            ],
            'integer key that does not exist with null default' => [
                null, [1 => 'one', 2 => 'two'], 3
            ],
            'integer key that does not exist with non-null default' => [
                'three', [1 => 'one', 2 => 'two'], 3, 'three'
            ],
            'numeric string key that exists as integer' => [
                'one', ['1' => 'one', '2' => 'two'], 1
            ],
            'numeric string key that exists as string' => [
                'one', [1 => 'one', 2 => 'two'], '1'
            ],
            'key in empty array with default value' => [
                'empty', [], 'missing', 'empty'
            ],
        ];
    }

    static function deleteDataProvider()
    {
        return [
            'existing string key' => [
                ['a' => 1, 'c' => 3],
                ['a' => 1, 'b' => 2, 'c' => 3],
                'b'
            ],
            'existing integer key' => [
                [0 => 'apple', 2 => 'cherry'],
                [0 => 'apple', 1 => 'banana', 2 => 'cherry'],
                1
            ],
            'non-existing string key' => [
                ['a' => 1, 'b' => 2],
                ['a' => 1, 'b' => 2],
                'c'
            ],
            'non-existing integer key' => [
                [0 => 'apple', 1 => 'banana'],
                [0 => 'apple', 1 => 'banana'],
                5
            ]
        ];
    }

    #endregion Data Providers
}
