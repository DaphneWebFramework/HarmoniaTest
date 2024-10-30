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

    #region wrap ---------------------------------------------------------------

    public function testWrapWithCompatibleEncoding()
    {
        $cstr = new CString('こんにちは', 'UTF-8');
        $str = 'おはよう';
        $cstr2 = AccessHelper::CallNonPublicMethod($cstr, 'wrap', [$str]);
        $this->assertInstanceOf(CString::class, $cstr2);
        $this->assertEquals($str, (string)$cstr2);
    }

    #[DataProvider('wrapIncompatibleEncodingDataProvider')]
    function testWrapWithIncompatibleEncoding($encoding, $incompatibleString)
    {
        $cstr = new CString('', $encoding);
        $this->expectException(\ValueError::class);
        AccessHelper::CallNonPublicMethod($cstr, 'wrap', [$incompatibleString]);
    }

    #endregion wrap

    #region __construct --------------------------------------------------------

    function testDefaultConstructor()
    {
        $cstr = new CString();
        $this->assertSame('', (string)$cstr);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
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
        $str = 'Hello, World!';
        $cstr = new CString($str);
        $this->assertSame($str, (string)$cstr);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
        );
    }

    function testConstructorWithNativeStringAndNullEncoding()
    {
        $str = 'Hello, World!';
        $cstr = new CString($str, null);
        $this->assertSame($str, (string)$cstr);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
        );
    }

    function testConstructorWithNativeStringAndSpecifiedEncoding()
    {
        $str = 'Hello, World!';
        $cstr = new CString($str, 'ISO-8859-1');
        $this->assertSame($str, (string)$cstr);
        $this->assertSame(
            'ISO-8859-1',
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
        );
    }

    function testConstructorWithStringable()
    {
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'I am Stringable';
            }
        };
        $cstr = new CString($stringable);
        $this->assertSame('I am Stringable', (string)$cstr);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
        );
    }

    function testConstructorWithStringableAndNullEncoding()
    {
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'I am Stringable';
            }
        };
        $cstr = new CString($stringable, null);
        $this->assertSame('I am Stringable', (string)$cstr);
        $this->assertSame(
            \mb_internal_encoding(),
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
        );
    }

    function testConstructorWithStringableAndSpecifiedEncoding()
    {
        $stringable = new class() implements \Stringable {
            function __toString() {
                return 'I am Stringable';
            }
        };
        $cstr = new CString($stringable, 'ISO-8859-1');
        $this->assertSame('I am Stringable', (string)$cstr);
        $this->assertSame(
            'ISO-8859-1',
            AccessHelper::GetNonPublicProperty($cstr, 'encoding')
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
        $cstr = new CString('Hello, World!', $encoding);
        $this->assertTrue(AccessHelper::GetNonPublicProperty($cstr, 'isSingleByte'));
    }

    #[DataProvider('multiByteEncodingProvider')]
    function testConstructorWithMultiByteEncoding($encoding)
    {
        $cstr = new CString('Hello, World!', $encoding);
        $this->assertFalse(AccessHelper::GetNonPublicProperty($cstr, 'isSingleByte'));
    }

    #endregion __construct

    #region __toString ---------------------------------------------------------

    function testToString()
    {
        $str = 'Hello, World!';
        $cstr = new CString($str);
        $this->assertSame($str, (string)$cstr);
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

    #region Length -------------------------------------------------------------

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

    function testSetAtWithIncompatibleEncoding()
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\ValueError::class);
        $cstr->SetAt(0, 'さ');
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

    #region InsertAt -----------------------------------------------------------

    #[DataProvider('insertAtDataProvider')]
    function testInsertAt($expected, $value, $encoding, $offset, $substring)
    {
        $cstr = new CString($value, $encoding);
        $cstr->InsertAt($offset, $substring);
        $this->assertSame($expected, (string)$cstr);
    }

    function testInsertAtWithIncompatibleEncoding()
    {
        $cstr = new CString('atladı', 'CP1254');
        $this->expectException(\ValueError::class);
        $cstr->InsertAt(0, 'Быстрая');
    }

    function testInsertAtWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->InsertAt(0, 'Hey');
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testInsertAtWithNonIntegerOffset($offset)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->InsertAt($offset, 'Hey');
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringProvider')]
    function testInsertAtWithNonStringSubstring($substring)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->InsertAt(0, $substring);
    }

    #endregion InsertAt

    #region DeleteAt -----------------------------------------------------------

    #[DataProvider('deleteAtDataProvider')]
    function testDeleteAt($expected, $value, $encoding, $offset, $count = 1)
    {
        $cstr = new CString($value, $encoding);
        $cstr->DeleteAt($offset, $count);
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

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testDeleteAtWithNonIntegerCount($count)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->DeleteAt(0, $count);
    }

    #endregion DeleteAt

    #region Left ---------------------------------------------------------------

    #[DataProvider('leftDataProvider')]
    function testLeft($expected, $value, $encoding, $count)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, (string)$cstr->Left($count));
    }

    function testLeftWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Left(4);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testLeftWithNonIntegerCount($count)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Left($count);
    }

    #endregion Left

    #region Right --------------------------------------------------------------

    #[DataProvider('rightDataProvider')]
    function testRight($expected, $value, $encoding, $count)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, (string)$cstr->Right($count));
    }

    function testRightWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Right(2);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testRightWithNonIntegerCount($count)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Right($count);
    }

    #endregion Right

    #region Middle -------------------------------------------------------------

    #[DataProvider('middleDataProvider')]
    function testMiddle($expected, $value, $encoding, $offset, $count)
    {
        $cstr = new CString($value, $encoding);
        $this->assertSame($expected, (string)$cstr->Middle($offset, $count));
    }

    function testMiddleWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Middle(1, 3);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testMiddleWithNonIntegerOffset($offset)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Middle($offset, 2);
    }

    #[DataProviderExternal(DataHelper::class, 'NonIntegerProvider')]
    function testMiddleWithNonIntegerCount($count)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Middle(1, $count);
    }

    #endregion Middle

    #region Trim ---------------------------------------------------------------

    #[DataProvider('trimDataProvider')]
    function testTrim(string $expected, string $value, string $encoding,
        ?string $characters = null)
    {
        $cstr = new CString($value, $encoding);
        $this->assertEquals($expected, (string)$cstr->Trim($characters));
        $this->assertEquals($value, (string)$cstr);
    }

    function testTrimWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->Trim();
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringExcludingNullProvider')]
    function testTrimWithInvalidCharacters($characters)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->Trim($characters);
    }

    #endregion Trim

    #region TrimLeft -----------------------------------------------------------

    #[DataProvider('trimLeftDataProvider')]
    function testTrimLeft(string $expected, string $value, string $encoding,
        ?string $characters = null)
    {
        $cstr = new CString($value, $encoding);
        $this->assertEquals($expected, (string)$cstr->TrimLeft($characters));
        $this->assertEquals($value, (string)$cstr);
    }

    function testTrimLeftWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->TrimLeft();
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringExcludingNullProvider')]
    function testTrimLeftWithInvalidCharacters($characters)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->TrimLeft($characters);
    }

    #endregion TrimLeft

    #region TrimRight ----------------------------------------------------------

    #[DataProvider('trimRightDataProvider')]
    function testTrimRight(string $expected, string $value, string $encoding,
        ?string $characters = null)
    {
        $cstr = new CString($value, $encoding);
        $this->assertEquals($expected, (string)$cstr->TrimRight($characters));
        $this->assertEquals($value, (string)$cstr);
    }

    function testTrimRightWithInvalidEncoding()
    {
        $cstr = new CString('Hello', 'INVALID-ENCODING');
        $this->expectException(\ValueError::class);
        $cstr->TrimRight();
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringExcludingNullProvider')]
    function testTrimRightWithInvalidCharacters($characters)
    {
        $cstr = new CString('Hello', 'ISO-8859-1');
        $this->expectException(\TypeError::class);
        $cstr->TrimRight($characters);
    }

    #endregion TrimRight

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

    static function wrapIncompatibleEncodingDataProvider()
    {
        return [
            ['CP1254', 'Быстрая'],
            ['ISO-8859-1', chr(0xfe)],
            ['ISO-8859-1', 'こんにちは'],
        ];
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
            'offset past length (single-byte)' => [
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
            'offset past length (multibyte)' => [
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
            'offset past length (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 10, 'Y'
            ],
            'offset past length in empty string (single-byte)' => [
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
            'offset past length (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 10, 'さ'
            ],
            'offset past length in empty string (multibyte)' => [
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

    static function insertAtDataProvider()
    {
        return [
        // Single-byte
            'offset at start (single-byte)' => [
                'Hello, World!', 'World!', 'ISO-8859-1', 0, 'Hello, '
            ],
            'offset in middle (single-byte)' => [
                'Hell no', 'Hello', 'ISO-8859-1', 4, ' n'
            ],
            'offset at end (single-byte)' => [
                'Hello!', 'Hello', 'ISO-8859-1', 5, '!'
            ],
            'offset past length (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 6, ' everyone'
            ],
            'negative offset (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', -1, 'Oops'
            ],
            'empty substring (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 0, ''
            ],
            'empty string (single-byte)' => [
                'Hello there', '', 'ISO-8859-1', 0, 'Hello there'
            ],
        // Multibyte
            'offset at start (multibyte)' => [
                'おはようこんにちは', 'こんにちは', 'UTF-8', 0, 'おはよう'
            ],
            'offset in middle (multibyte)' => [
                'こんさにちは', 'こんにちは', 'UTF-8', 2, 'さ'
            ],
            'offset at end (multibyte)' => [
                'こんにちは!', 'こんにちは', 'UTF-8', 5, '!'
            ],
            'offset past length (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 6, ' またね'
            ],
            'negative offset (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', -1, 'さ'
            ],
            'empty substring (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 0, ''
            ],
            'empty string (multibyte)' => [
                'ありがとう', '', 'UTF-8', 0, 'ありがとう'
            ],
        ];
    }

    static function deleteAtDataProvider()
    {
        return [
        // Single-byte
            'offset at start, single character (single-byte)' => [
                'ello', 'Hello', 'ISO-8859-1', 0
            ],
            'offset at start, multiple characters (single-byte)' => [
                'llo', 'Hello', 'ISO-8859-1', 0, 2
            ],
            'offset in middle, single character (single-byte)' => [
                'Hllo', 'Hello', 'ISO-8859-1', 1
            ],
            'offset in middle, multiple characters (single-byte)' => [
                'Ho', 'Hello', 'ISO-8859-1', 1, 3
            ],
            'offset at end, single character (single-byte)' => [
                'Hell', 'Hello', 'ISO-8859-1', 4
            ],
            'offset at end, multiple characters (single-byte)' => [
                'Hel', 'Hello', 'ISO-8859-1', 3, 2
            ],
            'offset past length, single character (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 10
            ],
            'offset past length, multiple characters (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 10, 2
            ],
            'negative offset, single character (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', -1
            ],
            'negative offset, multiple characters (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', -1, 3
            ],
            'zero count (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 2, 0
            ],
            'negative count (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', 2, -1
            ],
            'count exceeds available (single-byte)' => [
                'He', 'Hello', 'ISO-8859-1', 2, 10
            ],
            'empty string, single character (single-byte)' => [
                '', '', 'ISO-8859-1', 0
            ],
            'empty string, multiple characters (single-byte)' => [
                '', '', 'ISO-8859-1', 0, 3
            ],
        // Multibyte
            'offset at start, single character (multibyte)' => [
                'んにちは', 'こんにちは', 'UTF-8', 0
            ],
            'offset at start, multiple characters (multibyte)' => [
                'にちは', 'こんにちは', 'UTF-8', 0, 2
            ],
            'offset in middle, single character (multibyte)' => [
                'こにちは', 'こんにちは', 'UTF-8', 1
            ],
            'offset in middle, multiple characters (multibyte)' => [
                'こは', 'こんにちは', 'UTF-8', 1, 3
            ],
            'offset at end, single character (multibyte)' => [
                'こんにち', 'こんにちは', 'UTF-8', 4
            ],
            'offset at end, multiple characters (multibyte)' => [
                'こんに', 'こんにちは', 'UTF-8', 3, 2
            ],
            'offset past length, single character (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 10
            ],
            'offset past length, multiple characters (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 10, 2
            ],
            'negative offset, single character (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', -1
            ],
            'negative offset, multiple characters (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', -1, 3
            ],
            'zero count (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 2, 0
            ],
            'negative count (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', 2, -1
            ],
            'count exceeds available (multibyte)' => [
                'こん', 'こんにちは', 'UTF-8', 2, 10
            ],
            'empty string, single character (multibyte)' => [
                '', '', 'UTF-8', 0
            ],
            'empty string, multiple characters (multibyte)' => [
                '', '', 'UTF-8', 0, 3
            ],
        ];
    }

    static function leftDataProvider()
    {
        return [
        // Single-byte
            ['Hel', 'Hello', 'ISO-8859-1', 3],
            ['Hello', 'Hello', 'ISO-8859-1', 10],
            ['', 'Hello', 'ISO-8859-1', 0],
            ['', 'Hello', 'ISO-8859-1', -1],
            ['', '', 'ISO-8859-1', 2],
        // Multibyte
            ['こん', 'こんにちは', 'UTF-8', 2],
            ['こんにちは', 'こんにちは', 'UTF-8', 10],
            ['', 'こんにちは', 'UTF-8', 0],
            ['', 'こんにちは', 'UTF-8', -3],
            ['', '', 'UTF-8', 3],
        ];
    }

    static function rightDataProvider()
    {
        return [
        // Single-byte
            ['llo', 'Hello', 'ISO-8859-1', 3],
            ['Hello', 'Hello', 'ISO-8859-1', 10],
            ['', 'Hello', 'ISO-8859-1', 0],
            ['', 'Hello', 'ISO-8859-1', -1],
            ['', '', 'ISO-8859-1', 2],
        // Multibyte
            ['ちは', 'こんにちは', 'UTF-8', 2],
            ['こんにちは', 'こんにちは', 'UTF-8', 10],
            ['', 'こんにちは', 'UTF-8', 0],
            ['', 'こんにちは', 'UTF-8', -3],
            ['', '', 'UTF-8', 3],
        ];
    }

    static function middleDataProvider()
    {
        return [
        // Single-byte
            ['ell', 'Hello', 'ISO-8859-1', 1, 3],
            ['Hello', 'Hello', 'ISO-8859-1', 0, 10],
            ['', 'Hello', 'ISO-8859-1', 10, 2],
            ['', 'Hello', 'ISO-8859-1', -1, 3],
            ['', 'Hello', 'ISO-8859-1', 1, -3],
            ['', '', 'ISO-8859-1', 3, 5],
        // Multibyte
            ['んに', 'こんにちは', 'UTF-8', 1, 2],
            ['こんにちは', 'こんにちは', 'UTF-8', 0, 10],
            ['', 'こんにちは', 'UTF-8', 10, 2],
            ['', 'こんにちは', 'UTF-8', -2, 3],
            ['', 'こんにちは', 'UTF-8', 1, -5],
            ['', '', 'UTF-8', 3, 5],
        ];
    }

    static function trimDataProvider()
    {
        return [
        // Single-byte
            'default whitespace (single-byte)' => [
                'Hello', " \n Hello \r\t", 'ISO-8859-1'
            ],
            'custom characters (single-byte)' => [
                'Hello', '-=Hello=-', 'ISO-8859-1', '=-'
            ],
            'no whitespace (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1'
            ],
            'only whitespace (single-byte)' => [
                '', '    ', 'ISO-8859-1'
            ],
            'only trim characters (single-byte)' => [
                '', '====', 'ISO-8859-1', '='
            ],
            'non-present characters (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', '=*'
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1'
            ],
            'empty characters (single-byte)' => [
                ' Hello ', ' Hello ', 'ISO-8859-1', ''
            ],
            'null characters (single-byte)' => [
                'Hello', " \n Hello \t ", 'ISO-8859-1', null
            ],
        // Multibyte
            'default whitespace (multibyte)' => [
                'こんにちは', '　こんにちは　', 'UTF-8'
            ],
            'custom characters (multibyte)' => [
                'こんにちは', '=*=こんにちは=*=', 'UTF-8', '=*'
            ],
            'no whitespace (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8'
            ],
            'only whitespace (single-byte)' => [
                '', '　　　　', 'UTF-8'
            ],
            'only trim characters (multibyte)' => [
                '', '＝＝＝', 'UTF-8', '＝'
            ],
            'non-present characters (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', '=*'
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8'
            ],
            'empty characters (multibyte)' => [
                '　こんにちは　', '　こんにちは　', 'UTF-8', ''
            ],
            'null characters (multibyte)' => [
                'こんにちは', '　こんにちは　', 'UTF-8', null
            ],
        ];
    }

    static function trimLeftDataProvider()
    {
        return [
        // Single-byte
            'default whitespace (single-byte)' => [
                "Hello \r\t", " \n Hello \r\t", 'ISO-8859-1'
            ],
            'custom characters (single-byte)' => [
                'Hello=-', '-=Hello=-', 'ISO-8859-1', '=-'
            ],
            'no whitespace (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1'
            ],
            'only whitespace (single-byte)' => [
                '', '    ', 'ISO-8859-1'
            ],
            'only trim characters (single-byte)' => [
                '', '====', 'ISO-8859-1', '='
            ],
            'non-present characters (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', '=*'
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1'
            ],
            'empty characters (single-byte)' => [
                ' Hello ', ' Hello ', 'ISO-8859-1', ''
            ],
            'null characters (single-byte)' => [
                "Hello \t ", " \n Hello \t ", 'ISO-8859-1', null
            ],
        // Multibyte
            'default whitespace (multibyte)' => [
                'こんにちは　', '　こんにちは　', 'UTF-8'
            ],
            'custom characters (multibyte)' => [
                'こんにちは=*=', '=*=こんにちは=*=', 'UTF-8', '=*'
            ],
            'no whitespace (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8'
            ],
            'only whitespace (single-byte)' => [
                '', '　　　　', 'UTF-8'
            ],
            'only trim characters (multibyte)' => [
                '', '＝＝＝', 'UTF-8', '＝'
            ],
            'non-present characters (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', '=*'
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8'
            ],
            'empty characters (multibyte)' => [
                '　こんにちは　', '　こんにちは　', 'UTF-8', ''
            ],
            'null characters (multibyte)' => [
                'こんにちは　', '　こんにちは　', 'UTF-8', null
            ],
        ];
    }

    static function trimRightDataProvider()
    {
        return [
        // Single-byte
            'default whitespace (single-byte)' => [
                " \n Hello", " \n Hello \r\t", 'ISO-8859-1'
            ],
            'custom characters (single-byte)' => [
                '-=Hello', '-=Hello=-', 'ISO-8859-1', '=-'
            ],
            'no whitespace (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1'
            ],
            'only whitespace (single-byte)' => [
                '', '    ', 'ISO-8859-1'
            ],
            'only trim characters (single-byte)' => [
                '', '====', 'ISO-8859-1', '='
            ],
            'non-present characters (single-byte)' => [
                'Hello', 'Hello', 'ISO-8859-1', '=*'
            ],
            'empty string (single-byte)' => [
                '', '', 'ISO-8859-1'
            ],
            'empty characters (single-byte)' => [
                ' Hello ', ' Hello ', 'ISO-8859-1', ''
            ],
            'null characters (single-byte)' => [
                " \n Hello", " \n Hello \t ", 'ISO-8859-1', null
            ],
        // Multibyte
            'default whitespace (multibyte)' => [
                '　こんにちは', '　こんにちは　', 'UTF-8'
            ],
            'custom characters (multibyte)' => [
                '=*=こんにちは', '=*=こんにちは=*=', 'UTF-8', '=*'
            ],
            'no whitespace (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8'
            ],
            'only whitespace (single-byte)' => [
                '', '　　　　', 'UTF-8'
            ],
            'only trim characters (multibyte)' => [
                '', '＝＝＝', 'UTF-8', '＝'
            ],
            'non-present characters (multibyte)' => [
                'こんにちは', 'こんにちは', 'UTF-8', '=*'
            ],
            'empty string (multibyte)' => [
                '', '', 'UTF-8'
            ],
            'empty characters (multibyte)' => [
                '　こんにちは　', '　こんにちは　', 'UTF-8', ''
            ],
            'null characters (multibyte)' => [
                '　こんにちは', '　こんにちは　', 'UTF-8', null
            ],
        ];
    }

    #endregion Data Providers
}
