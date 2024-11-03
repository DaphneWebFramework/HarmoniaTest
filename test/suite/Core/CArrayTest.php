<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
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
}
