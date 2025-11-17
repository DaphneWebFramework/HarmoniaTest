<?php declare(strict_types=1);
namespace suite\Systems\ValidationSystem;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Systems\ValidationSystem\NativeFunctions;

enum EnumInteger: int {
    case Zero = 0;
    case One = 1;
    case Two = 2;
}

enum EnumString: string {
    case Alpha = 'alpha';
    case Beta = 'beta';
    case Gamma = 'gamma';
}

enum EnumPure {
    case Small;
    case Medium;
    case Large;
}

#[CoversClass(NativeFunctions::class)]
class NativeFunctionsTest extends TestCase
{
    private ?NativeFunctions $sut = null;

    protected function setUp(): void
    {
        $this->sut = new NativeFunctions();
    }

    protected function tearDown(): void
    {
        $this->sut = null;
    }

    #[DataProvider('isIntegerDataProvider')]
    function testIsInteger(bool $expected, mixed $value)
    {
        $this->assertSame($expected, $this->sut->IsInteger($value));
    }

    #[DataProvider('isNumericDataProvider')]
    function testIsNumeric(bool $expected, mixed $value)
    {
        $this->assertSame($expected, $this->sut->IsNumeric($value));
    }

    #[DataProvider('isStringDataProvider')]
    function testIsString(bool $expected, mixed $value)
    {
        $this->assertSame($expected, $this->sut->IsString($value));
    }

    #[DataProvider('isIntegerLikeDataProvider')]
    function testIsIntegerLike(bool $expected, mixed $value)
    {
        $this->assertSame($expected, $this->sut->IsIntegerLike($value));
    }

    #[DataProvider('isEmailAddressDataProvider')]
    function testIsEmailAddress(bool $expected, mixed $value)
    {
        $this->assertSame($expected, $this->sut->IsEmailAddress($value));
    }

    #[DataProvider('isArrayDataProvider')]
    function testIsArray(bool $expected, mixed $value)
    {
        $this->assertSame($expected, $this->sut->IsArray($value));
    }

    #[DataProvider('isDateTimeDataProvider')]
    function testIsDateTime(bool $expected, mixed $value)
    {
        $this->assertSame($expected, $this->sut->IsDateTime($value));
    }

    #[DataProvider('isUploadedFileDataProvider')]
    function testIsUploadedFile(bool $expected, mixed $value)
    {
        $this->assertSame($expected, $this->sut->IsUploadedFile($value));
    }

    #[DataProvider('matchRegexDataProvider')]
    function testMatchRegex(bool $expected, string $value, string $pattern)
    {
        $this->assertSame($expected, $this->sut->MatchRegex($value, $pattern));
    }

    #[DataProvider('matchDateTimeDataProvider')]
    function testMatchDateTime(bool $expected, string $value, string $format)
    {
        $this->assertSame($expected, $this->sut->MatchDateTime($value, $format));
    }

    #[DataProvider('isEnumValueDataProvider')]
    function testIsEnumValue(bool $expected, mixed $value, string $enumClass)
    {
        $this->assertSame($expected, $this->sut->IsEnumValue($value, $enumClass));
    }

    #region Data Providers -----------------------------------------------------

    static function isIntegerDataProvider()
    {
        return [
            [true, 0],
            [true, 123],
            [true, -123],
            [false, 123.45],
            [false, '123'],
            [false, 'abc'],
            [false, null],
            [false, true],
            [false, false],
            [false, []],
            [false, new \stdClass()],
        ];
    }

    static function isNumericDataProvider()
    {
        return [
            [true, 0],
            [true, 123],
            [true, -123],
            [true, 0.0],
            [true, 123.45],
            [true, -123.45],
            [true, '0'],
            [true, '123'],
            [true, '-123'],
            [true, '0.0'],
            [true, '123.45'],
            [true, '-123.45'],
            [false, 'abc'],
            [false, '123abc'],
            [false, 'abc123'],
            [false, '12.3.4'],
            [false, null],
            [false, true],
            [false, false],
            [false, []],
            [false, new \stdClass()],
        ];
    }

    static function isStringDataProvider()
    {
        return [
            [true, ''],
            [true, 'Hello'],
            [true, '123'],
            [true, ' '],
            [false, 123],
            [false, 123.45],
            [false, null],
            [false, true],
            [false, false],
            [false, []],
            [false, new \stdClass()],
        ];
    }

    static function isIntegerLikeDataProvider()
    {
        return [
            [true, 0],
            [true, 123],
            [true, -123],
            [true, '0'],
            [true, '123'],
            [true, '-123'],
            [false, '123abc'],
            [false, 'abc123'],
            [false, '12.3'],
            [false, 123.45],
            [false, '123.45'],
            [false, null],
            [false, true],
            [false, false],
            [false, []],
            [false, new \stdClass()],
        ];
    }

    static function isEmailAddressDataProvider()
    {
        return [
            [true, 'test@example.com'],
            [true, 'user.name+tag+sorting@example.com'],
            [true, 'user@sub.example.co.uk'],
            [true, 'x@x.io'],
            [false, 'plainaddress'],
            [false, '@missinguser.com'],
            [false, 'user@.com'],
            [false, 'user@com'],
            [false, 'user@com.'],
            [false, 'user@com,com'],
            [false, 'user@@example.com'],
            [false, 'user@example..com'],
            [false, 'user@example'],
            [false, 'user@ example.com'],
            [false, null],
            [false, true],
            [false, false],
            [false, []],
            [false, new \stdClass()],
        ];
    }

    static function isArrayDataProvider()
    {
        return [
            [true, []],
            [true, [1, 2, 3]],
            [true, ['a' => 1, 'b' => 2]],
            [false, null],
            [false, true],
            [false, false],
            [false, 123],
            [false, 123.45],
            [false, '[1, 2, 3]'],
            [false, new \stdClass()],
        ];
    }

    static function isDateTimeDataProvider()
    {
        return [
            [true, '2025-11-16T20:35:51.6381234Z'],
            [true, '2025-11-16T20:35:51.638123Z'],
            [true, '2025-11-16T20:35:51.63812Z'],
            [true, '2025-11-16T20:35:51.6381Z'],
            [true, '2025-11-16T20:35:51.638Z'],
            [true, '2025-11-16T20:35:51.63Z'],
            [true, '2025-11-16T20:35:51.6Z'],
            [true, '2025-11-16T20:35:51.Z'],
            [true, '2025-11-16T20:35:51Z'],
            [true, '2025-11-16 20:35:51'],
            [true, '2025-11-16T20:35'],
            [true, '2025-11-16T20:35:51-05:00'],
            [true, '2025-11-16T20:35:51+00:00'],
            [true, '2025-11-16T20:35:51+02:00'],
            [true, '2025-11-16'],
            [true, '2025/11/16'],

            [false, '2025-11-16T20:35:51+99:99'], // invalid offset
            [false, '2025-11-16T20:35:61Z'], // invalid seconds
            [false, '2025-11-32T20:35:51Z'], // invalid day
            [false, '2025-13-16T20:35:51Z'], // invalid month
            [false, 'not-a-date'],

            [false, 123],
            [false, 123.45],
            [false, null],
            [false, true],
            [false, false],
            [false, []],
            [false, new \stdClass()],
        ];
    }

    static function isUploadedFileDataProvider()
    {
        return [
            [true, [
                'name' => 'file.txt',
                'type' => 'text/plain',
                'tmp_name' => __FILE__, // Any existing file will do.
                'error' => UPLOAD_ERR_OK,
                'size' => 1234
            ]],
            [false, null],
            [false, true],
            [false, false],
            [false, []],
            [false, [
                'name' => 'file.txt'
            ]],
            [false, [
                'name' => 'file.txt',
                'type' => 'text/plain'
            ]],
            [false, [
                'name' => 'file.txt',
                'type' => 'text/plain',
                'tmp_name' => 'nonexistent'
            ]],
            [false, [
                'name' => 'file.txt',
                'type' => 'text/plain',
                'tmp_name' => __FILE__,
                'error' => UPLOAD_ERR_CANT_WRITE
            ]],
            [false, [
                'name' => 'file.txt',
                'type' => 'text/plain',
                'tmp_name' => __FILE__,
                'error' => UPLOAD_ERR_OK,
                'size' => 'not-an-int'
            ]],
        ];
    }

    static function matchRegexDataProvider()
    {
        return [
            [true, 'abc123', '/^[a-z]+\d+$/'],
            [false, 'abc!', '/^[a-z]+\d+$/'],
            [true, '123-456-7890', '/^\d{3}-\d{3}-\d{4}$/'],
            [false, '1234567890', '/^\d{3}-\d{3}-\d{4}$/'],
            [true, 'test@example.com', '/^[\w\.-]+@[\w\.-]+\.\w+$/'],
            [false, 'user@domain', '/^[\w\.-]+@[\w\.-]+\.\w+$/'],
            [true, 'Şebnem Yılmaz', "/^[\p{L}\p{N}][\p{L}\p{N} .\-']{1,49}$/u"],
            [false, ' 😎 Cool Name', "/^[\p{L}\p{N}][\p{L}\p{N} .\-']{1,49}$/u"],
            [true, '山田 太郎', "/^[\p{L}\p{N}][\p{L}\p{N} .\-']{1,49}$/u"],
            [false, '　山田', "/^[\p{L}\p{N}][\p{L}\p{N} .\-']{1,49}$/u"],
            // Malformed patterns for triggering compilation errors:
            [false, '123', '/(\d/'],     // Unclosed parenthesis
            [false, 'test', '/a{3,2}/'], // Invalid quantifier order
            [false, 'hello', '/??/'],    // Invalid syntax
            [false, 'world', '/[z-a]/'], // Invalid range
        ];
    }

    static function matchDateTimeDataProvider()
    {
        return [
            [true, '2024-03-07', 'Y-m-d'],
            [true, '07-03-2024', 'd-m-Y'],
            [true, '03/07/2024', 'm/d/Y'],
            [true, '2024-03-07 15:30:45', 'Y-m-d H:i:s'],
            [false, '2024-03-07 15:30', 'Y-m-d H:i:s'],
            [false, '07-03-24', 'd-m-Y'],
            [false, '31-04-2024', 'd-m-Y'], // Invalid date (April 31)
            [false, '2023-02-29', 'Y-m-d'], // 2023 is NOT a leap year
            [false, 'not-a-date', 'Y-m-d'],
            [false, '15:30:45', 'Y-m-d H:i:s'],
        ];
    }

    static function isEnumValueDataProvider()
    {
        return [
            // EnumInteger
            [true, 0, EnumInteger::class],
            [true, 1, EnumInteger::class],
            [true, 2, EnumInteger::class],
            [false, 999, EnumInteger::class],
            [false, '1', EnumInteger::class],
            [false, null, EnumInteger::class],
            [false, true, EnumInteger::class],
            // EnumString
            [true, 'alpha', EnumString::class],
            [true, 'beta', EnumString::class],
            [true, 'gamma', EnumString::class],
            [false, 'delta', EnumString::class],
            [false, 0, EnumString::class],
            [false, null, EnumString::class],
            // EnumPure
            [true, 'Small', EnumPure::class],
            [true, 'Medium', EnumPure::class],
            [true, 'Large', EnumPure::class],
            [false, 'small', EnumPure::class],
            [false, 0, EnumPure::class],
            [false, null, EnumPure::class],
            // Invalid enum class
            [false, 'whatever', \stdClass::class],
            [false, 'whatever', 'NopeClass'],
        ];
    }

    #endregion Data Providers
}
