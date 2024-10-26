<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Core\CString;

use \TestToolkit\AccessHelper;
use \TestToolkit\DataHelper;

#[CoversClass(CString::class)]
class CStringTest extends TestCase
{
    #region Self Test ----------------------------------------------------------

    static $encodingsWithAliases = [];

    static function setUpBeforeClass(): void
    {
        $encodings = \mb_list_encodings();
        self::$encodingsWithAliases = \array_unique(
            \array_merge(
                $encodings,
                \call_user_func_array(
                    '\array_merge',
                    \array_map('\mb_encoding_aliases', $encodings)
                )
            )
        );
    }

    #[DataProvider('singleByteEncodingProvider')]
    function testSingleByteEncoding($encoding, $sampleString)
    {
        $this->assertTrue(
            \in_array($encoding, self::$encodingsWithAliases, true),
            "Encoding {$encoding} is not supported in this environment."
        );
        $this->assertSame(
            \strlen($sampleString),
            \mb_strlen($sampleString, $encoding),
            "Encoding {$encoding} did not behave as a single-byte encoding."
        );
        $this->assertTrue(
            \mb_check_encoding($sampleString, $encoding),
            "Sample string is not valid in encoding {$encoding}."
        );
    }

    #[DataProvider('multiByteEncodingProvider')]
    function testMultiByteEncoding($encoding)
    {
        $this->assertTrue(
            \in_array($encoding, self::$encodingsWithAliases, true),
            "Encoding {$encoding} is not supported in this environment."
        );
    }

    #endregion Self Test

    #region __construct --------------------------------------------------------

    function testDefaultConstructor()
    {
        $cstring = new CString();
        $this->assertSame('', (string)$cstring);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstring, 'encoding')
        );
    }

    function testCopyConstructor()
    {
        $original = new CString('Hello, World!', 'ISO-8859-1');
        $copy = new CString($original, 'UTF-8'); // 'UTF-8' should be ignored
        $this->assertSame((string)$original, (string)$copy);
        $this->assertSame(
            AccessHelper::GetNonPublicProperty($original, 'encoding'),
            AccessHelper::GetNonPublicProperty($copy, 'encoding')
        );
        $this->assertSame(
            AccessHelper::GetNonPublicProperty($original, 'isSingleByte'),
            AccessHelper::GetNonPublicProperty($copy, 'isSingleByte')
        );
    }

    function testConstructorWithNativeString()
    {
        $string = 'Hello, World!';
        $cstring = new CString($string);
        $this->assertSame($string, (string)$cstring);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstring, 'encoding')
        );
    }

    function testConstructorWithNativeStringAndNullEncoding()
    {
        $string = 'Hello, World!';
        $cstring = new CString($string, null);
        $this->assertSame($string, (string)$cstring);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstring, 'encoding')
        );
    }

    function testConstructorWithNativeStringAndSpecifiedEncoding()
    {
        $string = 'Hello, World!';
        $cstring = new CString($string, 'ISO-8859-1');
        $this->assertSame($string, (string)$cstring);
        $this->assertSame(
            'ISO-8859-1',
            AccessHelper::GetNonPublicProperty($cstring, 'encoding')
        );
    }

    function testConstructorWithStringable()
    {
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'I am Stringable';
            }
        };
        $cstring = new CString($stringable);
        $this->assertSame('I am Stringable', (string)$cstring);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstring, 'encoding')
        );
    }

    function testConstructorWithStringableAndNullEncoding()
    {
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'I am Stringable';
            }
        };
        $cstring = new CString($stringable, null);
        $this->assertSame('I am Stringable', (string)$cstring);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstring, 'encoding')
        );
    }

    function testConstructorWithStringableAndSpecifiedEncoding()
    {
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'I am Stringable';
            }
        };
        $cstring = new CString($stringable, 'ISO-8859-1');
        $this->assertSame('I am Stringable', (string)$cstring);
        $this->assertSame(
            'ISO-8859-1',
            AccessHelper::GetNonPublicProperty($cstring, 'encoding')
        );
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringProvider')]
    function testConstructorWithInvalidValueType($value)
    {
        $this->expectException(\TypeError::class);
        new CString($value);
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringExcludingNullProvider')]
    function testConstructorWithInvalidEncodingType($encoding)
    {
        $this->expectException(\TypeError::class);
        new CString('Hello, World!', $encoding);
    }

    #[DataProvider('singleByteEncodingProvider')]
    function testConstructorWithSingleByteEncoding($encoding)
    {
        $cstring = new CString('Hello, World!', $encoding);
        $this->assertTrue(AccessHelper::GetNonPublicProperty($cstring, 'isSingleByte'));
    }

    #[DataProvider('multiByteEncodingProvider')]
    function testConstructorWithMultiByteEncoding($encoding)
    {
        $cstring = new CString('Hello, World!', $encoding);
        $this->assertFalse(AccessHelper::GetNonPublicProperty($cstring, 'isSingleByte'));
    }

    #endregion __construct

    #region __toString ---------------------------------------------------------

    function testToString()
    {
        $string = 'Hello, World!';
        $cstring = new CString($string);
        $this->assertSame($string, (string)$cstring);
    }

    #endregion __toString

    #region IsEmpty ------------------------------------------------------------

    #[DataProvider('isEmptyDataProvider')]
    function testIsEmpty($expected, $value, $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->IsEmpty());
    }

    #endregion IsEmpty

    #region Length ------------------------------------------------------------

    #[DataProvider('lengthDataProvider')]
    function testLength($expected, $value, $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->Length());
    }

    function testLengthWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Length();
    }

    #endregion Length

    #region First --------------------------------------------------------------

    #[DataProvider('firstDataProvider')]
    function testFirst($expected, $value, $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->First());
    }

    function testFirstWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->First();
    }

    #endregion First

    #region Last ---------------------------------------------------------------

    #[DataProvider('lastDataProvider')]
    function testLast($expected, $value, $encoding)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->Last());
    }

    function testLastWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Last();
    }

    #endregion Last

    #region At -----------------------------------------------------------------

    #[DataProvider('atDataProvider')]
    function testAt($expected, $value, $encoding, $offset)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, $cstr->At($offset));
    }

    function testAtWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->At(1);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testAtWithNonIntegerOffset($offset)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->At($offset);
    }

    #endregion At

    #region SetAt --------------------------------------------------------------

    #[DataProvider('setAtDataProvider')]
    function testSetAt($expected, $value, $encoding, $offset, $character)
    {
        $cstr = new CString($value, $encoding);
        $cstr->SetAt($offset, $character);
        $this->assertSame($expected, (string)$cstr);
    }

    function testSetAtWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->SetAt(0, 'Y');
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testSetAtWithNonIntegerOffset($offset)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->SetAt($offset, 'Y');
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringProvider')]
    function testSetAtWithNonStringCharacter($character)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->SetAt(0, $character);
    }

    #endregion SetAt

    #region DeleteAt -----------------------------------------------------------

    #[DataProvider('deleteAtDataProvider')]
    function testDeleteAt($expected, $value, $encoding, $offset)
    {
        $cstr = new CString($value, $encoding);
        $cstr->DeleteAt($offset);
        $this->assertSame($expected, (string)$cstr);
    }

    function testDeleteAtWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->DeleteAt(0);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testDeleteAtWithNonIntegerOffset($offset)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->DeleteAt($offset);
    }

    #endregion DeleteAt

    #region Data Providers -----------------------------------------------------

    static function singleByteEncodingProvider()
    {
        return [
            'Standard ASCII (7-bit)' => [
                'ASCII',
                'The quick brown fox jumps over the lazy dog'
            ],
            'Alias for ASCII' => [
                'US-ASCII',
                'The quick brown fox jumps over the lazy dog'
            ],
            'Western European (DOS)' => [
                'CP850',
                'Le renard brun rapide saute par-dessus le chien paresseux'
            ],
            'Cyrillic (DOS)' => [
                'CP866',
                'Быстрая коричневая лиса прыгает через ленивую собаку'
            ],
            'Cyrillic (Windows)' => [
                'CP1251',
                'Быстрая коричневая лиса прыгает через ленивую собаку'
            ],
            'Turkish (Windows)' => [
                'CP1254',
                'Çabuk kahverengi tilki tembel köpeğin üzerine atlar'
            ],
            'Latin-1 (Western European)' => [
                'ISO-8859-1',
                'Le garçon était très heureux d’apprendre qu’il gagnerait bientôt une voiture.'
            ],
            'Latin-2 (Central European)' => [
                'ISO-8859-2',
                'Příliš žluťoučký kůň úpěl ďábelské ódy, často šťavnaté lízátko plné chvástání.'
            ],
            'Latin-3 (South European)' => [
                'ISO-8859-3',
                'Ċetta ħadet il-karozza sabiħa għax hemm raġel maġenb il-knisja.'
            ],
            'Latin-4 (North European)' => [
                'ISO-8859-4',
                'Lietuvių kalbos mokykloje buvau labai išsigandęs.'
            ],
            'Cyrillic' => [
                'ISO-8859-5',
                'Быстрая коричневая лиса перепрыгнула через ленивую собаку.'
            ],
            'Greek' => [
                'ISO-8859-7',
                'Η ταχεία καφέ αλεπού πηδά πάνω από τον τεμπέλη σκύλο.'
            ],
            'Latin-5 (Turkish)' => [
                'ISO-8859-9',
                'Çabuk kahverengi tilki tembel köpeğin üstünden atladı.'
            ],
            'Latin-6 (Nordic)' => [
                'ISO-8859-10',
                'Hunden hoppar över den bruna räven som vilar bredvid kyrkan.'
            ],
            'Baltic Rim' => [
                'ISO-8859-13',
                'Vėjas pūtė stipriai ir sukūrė didžiules bangas.'
            ],
            'Latin-8 (Celtic)' => [
                'ISO-8859-14',
                'Fuaire an madra bána faoi bhun an crainn.'
            ],
            'Latin-9 (Western European)' => [
                'ISO-8859-15',
                'L’élève réussit l’examen difficile grâce à l’aide de ses amis.'
            ],
            'Latin-10 (South-Eastern European)' => [
                'ISO-8859-16',
                'Vulpea maronie a sărit peste câinele leneș din curte.'
            ],
            'Cyrillic (Russian)' => [
                'KOI8-R',
                'Быстрая лиса перепрыгнула через ленивого пса возле дома.'
            ],
            'Cyrillic (Ukrainian)' => [
                'KOI8-U',
                'Швидка лисиця стрибнула через ледачого собаку.'
            ],
            'Cyrillic (Windows)' => [
                'Windows-1251',
                'Быстрая коричневая лиса перепрыгнула через ленивую собаку.'
            ],
            'Western European (Windows)' => [
                'Windows-1252',
                'Le rapide renard brun saute par-dessus le chien paresseux.'
            ],
            'Turkish (Windows)' => [
                'Windows-1254',
                'Çabuk kahverengi tilki tembel köpeğin üstünden atladı.'
            ],
        ];
    }

    static function supportedMultiByteEncodingProvider()
    {
        return [
            ['BIG-5'],    // Traditional Chinese (Taiwan, Hong Kong)
            ['CP932'],    // Shift-JIS variant (Windows Japanese)
            ['EUC-CN'],   // Simplified Chinese (Extended Unix Code)
            ['EUC-JP'],   // Extended Unix Code for Japanese
            ['EUC-KR'],   // Extended Unix Code for Korean
            ['EUC-TW'],   // Traditional Chinese (Extended Unix Code)
            ['SJIS-win'], // Shift-JIS variant for Windows
            ['UCS-4'],    // Universal Coded Character Set (4-byte)
            ['UCS-4LE'],  // UCS-4 Little Endian
            ['UTF-8'],    // Unicode Transformation Format (8-bit, variable-length)
            ['UTF-16'],   // Unicode Transformation Format (16-bit)
            ['UTF-16BE'], // UTF-16 Big Endian
            ['UTF-16LE'], // UTF-16 Little Endian
            ['UTF-32'],   // Unicode Transformation Format (32-bit)
            ['UTF-32BE'], // UTF-32 Big Endian
            ['UTF-32LE'], // UTF-32 Little Endian
        ];
    }

    static function unsupportedMultiByteEncodingProvider()
    {
        return [
            ['CP936'],             // Simplified Chinese (GBK, Windows)
            ['CP950'],             // Traditional Chinese (Big5, Windows)
            ['CP50220'],           // Japanese, ISO-2022-JP with extensions (JIS)
            ['CP50221'],           // Japanese, ISO-2022-JP with 1-byte Kana
            ['CP50222'],           // Japanese, ISO-2022-JP variant
            ['CP51932'],           // Japanese, EUC variant for Windows
            ['GB18030'],           // Simplified Chinese (Unicode-compatible)
            ['ISO-2022-JP-2004'],  // Japanese (ISO-2022 variant, JIS X 0213:2004)
            ['ISO-2022-JP-MOBILE#KDDI'], // Japanese (ISO-2022 for mobile devices)
            ['ISO-2022-JP-MS'],    // Japanese (Microsoft variant of ISO-2022-JP)
            ['ISO-2022-JP'],       // Japanese (ISO 2022 standard)
            ['ISO-2022-KR'],       // Korean (ISO 2022 standard)
            ['ISO2022JPMS'],       // Japanese (ISO-2022 variant for Microsoft systems)
            ['SJIS-2004'],         // Shift-JIS variant for JIS X 0213:2004
            ['SJIS-mac'],          // Shift-JIS variant for Mac systems
            ['SJIS-Mobile#DOCOMO'], // Shift-JIS variant for DOCOMO mobile devices
            ['SJIS-Mobile#KDDI'],   // Shift-JIS variant for KDDI mobile devices
            ['SJIS-Mobile#SOFTBANK'], // Shift-JIS variant for SoftBank mobile devices
            ['UCS-2'],             // Universal Coded Character Set (2-byte, BMP)
            ['UCS-2BE'],           // UCS-2 Big Endian
            ['UCS-2LE'],           // UCS-2 Little Endian
            ['UCS-4BE'],           // UCS-4 Big Endian
            ['UTF-7'],             // Unicode Transformation Format (7-bit)
            ['UTF7-IMAP'],         // UTF-7 variant for IMAP (mail protocol)
        ];
    }

    static function multiByteEncodingProvider()
    {
        return \array_merge(
            self::supportedMultiByteEncodingProvider(),
            self::unsupportedMultiByteEncodingProvider()
        );
    }

    static function isEmptyDataProvider()
    {
        return [
            'non-empty string (single-byte)' => [
                false, 'Hello', 'ISO-8859-1'
            ],
            'empty string (single-byte)' => [
                true, '', 'ISO-8859-1'
            ],
            'non-empty string (multibyte)' => [
                false, 'こんにちは', 'UTF-8'
            ],
            'empty string (multibyte)' => [
                true, '', 'UTF-8'
            ],
        ];
    }

    static function lengthDataProvider()
    {
        return [
            'non-empty string (single-byte)' => [
                5, 'Hello', 'ISO-8859-1'
            ],
            'empty string (single-byte)' => [
                0, '', 'ISO-8859-1'
            ],
            'non-empty string (multibyte)' => [
                5, 'こんにちは', 'UTF-8'
            ],
            'empty string (multibyte)' => [
                0, '', 'UTF-8'
            ],
        ];
    }

    static function firstDataProvider()
    {
        return [
            'non-empty string (single-byte)' => [
                'H', 'Hello', 'ISO-8859-1'
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1'
            ],
            'non-empty string (multibyte)' => [
                'こ', 'こんにちは', 'UTF-8'
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8'
            ],
        ];
    }

    static function lastDataProvider()
    {
        return [
            'non-empty string (single-byte)' => [
                'o', 'Hello', 'ISO-8859-1'
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1'
            ],
            'non-empty string (multibyte)' => [
                'は', 'こんにちは', 'UTF-8'
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8'
            ],
        ];
    }

    static function atDataProvider()
    {
        return [
        // Single-byte
            'offset at start (single-byte)' => [
                'H', 'Hello', 'ISO-8859-1', 0
            ],
            'offset in middle (single-byte)' => [
                'e', 'Hello', 'ISO-8859-1', 1
            ],
            'offset at end (single-byte)' => [
                'o', 'Hello', 'ISO-8859-1', 4
            ],
            'offset past the length (single-byte)' => [
                '', 'Hello', 'ISO-8859-1', 10
            ],
            'negative offset (single-byte)' => [
                '', 'Hello', 'ISO-8859-1', -1
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1', 0
            ],
        // Multibyte
            'offset at start (multibyte)' => [
                'こ', 'こんにちは', 'UTF-8', 0
            ],
            'offset in middle (multibyte)' => [
                'ん', 'こんにちは', 'UTF-8', 1
            ],
            'offset at end (multibyte)' => [
                'は', 'こんにちは', 'UTF-8', 4
            ],
            'offset past the length (multibyte)' => [
                '', 'こんにちは', 'UTF-8', 10
            ],
            'negative offset (multibyte)' => [
                '', 'こんにちは', 'UTF-8', -1
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8', 0
            ],
        ];
    }

    static function setAtDataProvider()
    {
        return [
        // Single-byte
            'offset at start (single-byte)' => [
                'Yello', 'Hello', 'ISO-8859-1', 0, 'Y'
            ],
            'offset in middle (single-byte)' => [
                'Hallo', 'Hello', 'ISO-8859-1', 1, 'a'
            ],
            'offset at end (single-byte)' => [
                'Helly', 'Hello', 'ISO-8859-1', 4, 'y'
            ],
            'offset after last character (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 5, '!'
            ],
            'offset past the length (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 10, 'Y'
            ],
            'offset past the length in empty string (single-byte)' => [
                '', '', 'ISO-8859-1', 3, 'Y'
            ],
            'negative offset (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', -1, 'Y'
            ],
            'multi-character input (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 0, 'ABC'
            ],
            'empty character (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 0, ''
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1', 0, 'Y'
            ],
        // Multibyte
            'offset at start (multibyte)' => [
                'さんにちは', 'こんにちは', 'UTF-8', 0, 'さ'
            ],
            'offset in middle (multibyte)' => [
                'こすにちは', 'こんにちは', 'UTF-8', 1, 'す'
            ],
            'offset at end (multibyte)' => [
                'こんにちせ', 'こんにちは', 'UTF-8', 4, 'せ'
            ],
            'offset after last character (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 5, 'ぞ'
            ],
            'offset past the length (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 10, 'さ'
            ],
            'offset past the length in empty string (multibyte)' => [
                '', '', 'UTF-8', 3, 'さ'
            ],
            'negative offset (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', -1, 'さ'
            ],
            'multi-character input (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 0, 'あいうえお'
            ],
            'empty character (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 0, ''
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8', 0, 'さ'
            ],
        ];
    }

    static function deleteAtDataProvider()
    {
        return [
        // Single-byte
            'offset at start (single-byte)' => [
                'ello', 'Hello', 'ISO-8859-1', 0
            ],
            'offset in middle (single-byte)' => [
                'Hllo', 'Hello', 'ISO-8859-1', 1
            ],
            'offset at end (single-byte)' => [
                'Hell', 'Hello', 'ISO-8859-1', 4
            ],
            'offset past the length (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 10
            ],
            'negative offset (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', -1
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1', 0
            ],
        // Multibyte
            'offset at start (multibyte)' => [
                'んにちは', 'こんにちは', 'UTF-8', 0
            ],
            'offset in middle (multibyte)' => [
                'こにちは', 'こんにちは', 'UTF-8', 1
            ],
            'offset at end (multibyte)' => [
                'こんにち', 'こんにちは', 'UTF-8', 4
            ],
            'offset past the length (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 10
            ],
            'negative offset (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', -1
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8', 0
            ],
        ];
    }

    #endregion Data Providers
}
