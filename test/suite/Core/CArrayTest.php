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

    #region Has ----------------------------------------------------------------

    #[DataProvider('hasDataProvider')]
    function testHas(bool $expected, array $arr, string|int $key)
    {
        $carr = new CArray($arr);
        $this->assertSame($expected, $carr->Has($key));
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringOrIntegerProvider')]
    function testHasWithInvalidKeyType($key)
    {
        $carr = new CArray();
        $this->expectException(\TypeError::class);
        $carr->Has($key);
    }

    #endregion Has

    #region Get ----------------------------------------------------------------

    #[DataProvider('getDataProvider')]
    function testGet(mixed $expected, array $arr, string|int $key,
        mixed $defaultValue = null)
    {
        $carr = new CArray($arr);
        $this->assertSame($expected, $carr->Get($key, $defaultValue));
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringOrIntegerProvider')]
    function testGetWithInvalidKeyType($key)
    {
        $carr = new CArray();
        $this->expectException(\TypeError::class);
        $carr->Get($key);
    }

    #endregion Get

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

    static function hasDataProvider()
    {
        return [
            'existing string key' => [
                true, ['a' => 1, 'b' => 2], 'a'
            ],
            'existing integer key' => [
                true, [0 => 'first', 1 => 'second'], 0
            ],
            'non-existing string key' => [
                false, ['x' => 10, 'y' => 20], 'z'
            ],
            'non-existing integer key' => [
                false, [0 => 'first', 1 => 'second'], 2
            ],
            'existing integer key accessed as string' => [
                true, [1 => 'one', '2' => 'two'], '1'
            ],
            'existing numeric string key accessed as integer' => [
                true, ['1' => 'one', '2' => 'two'], 1
            ],
            'empty array' => [
                false, [], 'missing'
            ],
        ];
    }

    static function getDataProvider()
    {
        return [
            'existing string key' => [
                42, ['a' => 42, 'b' => 24], 'a'
            ],
            'non-existing string key with null default' => [
                null, ['a' => 42, 'b' => 24], 'c'
            ],
            'non-existing string key with non-null default' => [
                'default', ['a' => 42, 'b' => 24], 'c', 'default'
            ],
            'existing integer key' => [
                'one', [1 => 'one', 2 => 'two'], 1
            ],
            'non-existing integer key with null default' => [
                null, [1 => 'one', 2 => 'two'], 3
            ],
            'non-existing integer key with non-null default' => [
                'three', [1 => 'one', 2 => 'two'], 3, 'three'
            ],
            'existing integer key accessed as string' => [
                'one', [1 => 'one', 2 => 'two'], '1'
            ],
            'existing numeric string key accessed as integer' => [
                'one', ['1' => 'one', '2' => 'two'], 1
            ],
            'empty array with null default' => [
                null, [], 'missing'
            ],
            'empty array with default value' => [
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
            ],
            'existing integer key accessed as string' => [
                [2 => 'two'],
                [1 => 'one', 2 => 'two'],
                '1'
            ],
            'existing numeric string key accessed as integer' => [
                ['2' => 'two'],
                ['1' => 'one', '2' => 'two'],
                1
            ],
            'empty array' => [
                [],
                [],
                'missing'
            ],
        ];
    }

    #endregion Data Providers
}
