<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\DataProvider;

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
        $this->assertEquals(
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

    #endregion Data Providers
}
