<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;
use \PHPUnit\Framework\Attributes\DataProviderExternal;

use \Harmonia\Translation;

use \Harmonia\Config;
use \Harmonia\Core\CArray;
use \Harmonia\Core\CFile;
use \Harmonia\Core\CPath;
use \TestToolkit\AccessHelper;
use \TestToolkit\DataHelper;

class TestTranslation extends Translation {
    protected function filePaths(): array {
        return []; // Not used in tests.
    }
}

#[CoversClass(Translation::class)]
class TranslationTest extends TestCase
{
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalConfig = Config::ReplaceInstance(
            $this->createMock(Config::class));
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalConfig);
    }

    private function systemUnderTest(string ...$mockedMethods): Translation
    {
        return $this->getMockBuilder(TestTranslation::class)
            ->disableOriginalConstructor()
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    #region Get ----------------------------------------------------------------

    function testGetThrowsWhenTranslationsThrows()
    {
        $sut = $this->systemUnderTest('translations');

        $sut->expects($this->once())
            ->method('translations')
            ->willThrowException(new \RuntimeException());

        $this->expectException(\RuntimeException::class);
        $sut->Get('field_must_be_numeric');
    }

    function testGetThrowsWhenTranslationIdNotFound()
    {
        $sut = $this->systemUnderTest('translations');
        $translations = $this->createMock(CArray::class);

        $sut->expects($this->once())
            ->method('translations')
            ->willReturn($translations);
        $translations->expects($this->once())
            ->method('Has')
            ->with('field_must_be_numeric')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Translation ID 'field_must_be_numeric' not found.");
        $sut->Get('field_must_be_numeric');
    }

    function testGetThrowsWhenLanguageThrows()
    {
        $sut = $this->systemUnderTest('translations', 'language');
        $translations = $this->createMock(CArray::class);

        $sut->expects($this->once())
            ->method('translations')
            ->willReturn($translations);
        $translations->expects($this->once())
            ->method('Has')
            ->with('field_must_be_numeric')
            ->willReturn(true);
        $sut->expects($this->once())
            ->method('language')
            ->willThrowException(new \RuntimeException());

        $this->expectException(\RuntimeException::class);
        $sut->Get('field_must_be_numeric');
    }

    function testGetThrowsWhenLanguageNotFoundForTranslationId()
    {
        $sut = $this->systemUnderTest('translations', 'language');
        $translations = $this->createMock(CArray::class);
        $unit = $this->createMock(CArray::class);

        $sut->expects($this->once())
            ->method('translations')
            ->willReturn($translations);
        $translations->expects($this->once())
            ->method('Has')
            ->with('field_must_be_numeric')
            ->willReturn(true);
        $sut->expects($this->once())
            ->method('language')
            ->willReturn('fr');
        $translations->expects($this->once())
            ->method('Get')
            ->with('field_must_be_numeric')
            ->willReturn($unit);
        $unit->expects($this->once())
            ->method('Has')
            ->with('fr')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Language 'fr' not found for translation ID 'field_must_be_numeric'.");
        $sut->Get('field_must_be_numeric');
    }

    function testGetReturnsTranslationWithoutFormatting()
    {
        $sut = $this->systemUnderTest('translations', 'language');
        $translations = $this->createMock(CArray::class);
        $unit = $this->createMock(CArray::class);

        $sut->expects($this->once())
            ->method('translations')
            ->willReturn($translations);
        $translations->expects($this->once())
            ->method('Has')
            ->with('field_must_be_numeric')
            ->willReturn(true);
        $sut->expects($this->once())
            ->method('language')
            ->willReturn('en');
        $translations->expects($this->once())
            ->method('Get')
            ->with('field_must_be_numeric')
            ->willReturn($unit);
        $unit->expects($this->once())
            ->method('Has')
            ->with('en')
            ->willReturn(true);
        $unit->expects($this->once())
            ->method('Get')
            ->with('en')
            ->willReturn('Field must be numeric.');

        $result = $sut->Get('field_must_be_numeric');
        $this->assertSame('Field must be numeric.', $result);
    }

    function testGetReturnsTranslationWithFormatting()
    {
        $sut = $this->systemUnderTest('translations', 'language');
        $translations = $this->createMock(CArray::class);
        $unit = $this->createMock(CArray::class);

        $sut->expects($this->once())
            ->method('translations')
            ->willReturn($translations);
        $translations->expects($this->once())
            ->method('Has')
            ->with('field_must_be_numeric')
            ->willReturn(true);
        $sut->expects($this->once())
            ->method('language')
            ->willReturn('en');
        $translations->expects($this->once())
            ->method('Get')
            ->with('field_must_be_numeric')
            ->willReturn($unit);
        $unit->expects($this->once())
            ->method('Has')
            ->with('en')
            ->willReturn(true);
        $unit->expects($this->once())
            ->method('Get')
            ->with('en')
            ->willReturn("Field '%s' must be numeric.");

        $result = $sut->Get('field_must_be_numeric', 'price');
        $this->assertSame("Field 'price' must be numeric.", $result);
    }

    #endregion Get

    #region translations -------------------------------------------------------

    function testTranslationsThrowsWhenLoadTranslationsFromFileThrows()
    {
        $sut = $this->systemUnderTest(
            'filePaths',
            'loadTranslationsFromFile'
        );
        $paths = [$this->createStub(CPath::class)];

        $sut->expects($this->once())
            ->method('filePaths')
            ->willReturn($paths);
        $sut->expects($this->once())
            ->method('loadTranslationsFromFile')
            ->with($paths[0])
            ->willThrowException(new \RuntimeException());

        $this->expectException(\RuntimeException::class);
        AccessHelper::CallMethod($sut, 'translations');
    }

    #[DataProvider('translationsDataProvider')]
    function testTranslations($expected, $base, $override)
    {
        $sut = $this->systemUnderTest(
            'filePaths',
            'loadTranslationsFromFile'
        );
        $paths = [
            $this->createStub(CPath::class),
            $this->createStub(CPath::class)
        ];

        $sut->expects($this->once())
            ->method('filePaths')
            ->willReturn($paths);
        $sut->expects($invokedCount = $this->exactly(2))
            ->method('loadTranslationsFromFile')
            ->willReturnCallback(function($filePath)
                use($invokedCount, $paths, $base, $override)
            {
                switch ($invokedCount->numberOfInvocations()) {
                case 1:
                    $this->assertSame($paths[0], $filePath);
                    return $base;
                case 2:
                    $this->assertSame($paths[1], $filePath);
                    return $override;
                }
            });

        $actual = AccessHelper::CallMethod($sut, 'translations');

        $this->assertEquals($expected, $actual);
        // Ensure the same instance is returned on subsequent calls.
        $this->assertSame($actual, AccessHelper::CallMethod($sut, 'translations'));
    }

    #endregion translations

    #region loadTranslationsFromFile -------------------------------------------

    function testLoadTranslationsFromFileThrowsWhenFileOpenFails()
    {
        $sut = $this->systemUnderTest('openFile');
        $path = $this->createStub(CPath::class);

        $sut->expects($this->once())
            ->method('openFile')
            ->with($path)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to open translation file.');
        AccessHelper::CallMethod($sut, 'loadTranslationsFromFile', [$path]);
    }

    function testLoadTranslationsFromFileThrowsWhenFileReadFails()
    {
        $sut = $this->systemUnderTest('openFile');
        $path = $this->createStub(CPath::class);
        $file = $this->createMock(CFile::class);

        $sut->expects($this->once())
            ->method('openFile')
            ->with($path)
            ->willReturn($file);
        $file->expects($this->once())
            ->method('Read')
            ->willReturn(null);
        $file->expects($this->once())
            ->method('Close');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not read translation file.');
        AccessHelper::CallMethod($sut, 'loadTranslationsFromFile', [$path]);
    }

    function testLoadTranslationsFromFileThrowsWhenWhenJsonDecodeFails()
    {
        $sut = $this->systemUnderTest('openFile');
        $path = $this->createStub(CPath::class);
        $file = $this->createMock(CFile::class);

        $sut->expects($this->once())
            ->method('openFile')
            ->with($path)
            ->willReturn($file);
        $file->expects($this->once())
            ->method('Read')
            ->willReturn('{invalid'); // Bad JSON
        $file->expects($this->once())
            ->method('Close');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Translation file could not be decoded.');
        AccessHelper::CallMethod($sut, 'loadTranslationsFromFile', [$path]);
    }

    function testLoadTranslationsFromFileThrowsWhenRootIsNotArray()
    {
        $sut = $this->systemUnderTest('openFile');
        $path = $this->createStub(CPath::class);
        $file = $this->createMock(CFile::class);

        $sut->expects($this->once())
            ->method('openFile')
            ->with($path)
            ->willReturn($file);
        $file->expects($this->once())
            ->method('Read')
            ->willReturn('"not an object"');
        $file->expects($this->once())
            ->method('Close');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Translation file must have an object as its root structure.');
        AccessHelper::CallMethod($sut, 'loadTranslationsFromFile', [$path]);
    }

    function testLoadTranslationsFromFileThrowsWhenTranslationIdIsNotString()
    {
        $sut = $this->systemUnderTest('openFile');
        $path = $this->createStub(CPath::class);
        $file = $this->createMock(CFile::class);

        $sut->expects($this->once())
            ->method('openFile')
            ->with($path)
            ->willReturn($file);
        $file->expects($this->once())
            ->method('Read')
            ->willReturn(<<<JSON
                [
                  {
                    "en": "Welcome to our application!",
                    "tr": "Uygulamamıza hoş geldiniz!",
                    "se": "Välkommen till vår applikation!"
                  },
                  {
                    "en": "Are you sure you want to log out?",
                    "tr": "Çıkış yapmak istediğinizden emin misiniz?",
                    "se": "Är du säker på att du vill logga ut?"
                  }
                ]
            JSON);
        $file->expects($this->once())
            ->method('Close');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Translation ID must be a string.');
        AccessHelper::CallMethod($sut, 'loadTranslationsFromFile', [$path]);
    }

    function testLoadTranslationsFromFileThrowsWhenTranslationIdIsEmpty()
    {
        $sut = $this->systemUnderTest('openFile');
        $path = $this->createStub(CPath::class);
        $file = $this->createMock(CFile::class);

        $sut->expects($this->once())
            ->method('openFile')
            ->with($path)
            ->willReturn($file);
        $file->expects($this->once())
            ->method('Read')
            ->willReturn(<<<JSON
                {
                  "": {
                    "en": "Welcome to our application!",
                    "tr": "Uygulamamıza hoş geldiniz!",
                    "se": "Välkommen till vår applikation!"
                  },
                  "logout_confirmation": {
                    "en": "Are you sure you want to log out?",
                    "tr": "Çıkış yapmak istediğinizden emin misiniz?",
                    "se": "Är du säker på att du vill logga ut?"
                  }
                }
            JSON);
        $file->expects($this->once())
            ->method('Close');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Translation ID cannot be empty.');
        AccessHelper::CallMethod($sut, 'loadTranslationsFromFile', [$path]);
    }

    function testLoadTranslationsFromFileThrowsWhenUnitIsNotArray()
    {
        $sut = $this->systemUnderTest('openFile');
        $path = $this->createStub(CPath::class);
        $file = $this->createMock(CFile::class);

        $sut->expects($this->once())
            ->method('openFile')
            ->with($path)
            ->willReturn($file);
        $file->expects($this->once())
            ->method('Read')
            ->willReturn(<<<JSON
                {
                  "welcome_message": "not an object",
                  "logout_confirmation": {
                    "en": "Are you sure you want to log out?",
                    "tr": "Çıkış yapmak istediğinizden emin misiniz?",
                    "se": "Är du säker på att du vill logga ut?"
                  }
                }
            JSON);
        $file->expects($this->once())
            ->method('Close');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Each translation ID must map to an object of language-text pairs.');
        AccessHelper::CallMethod($sut, 'loadTranslationsFromFile', [$path]);
    }

    function testLoadTranslationsFromFileThrowsWhenLanguageCodeIsNotString()
    {
        $sut = $this->systemUnderTest('openFile');
        $path = $this->createStub(CPath::class);
        $file = $this->createMock(CFile::class);

        $sut->expects($this->once())
            ->method('openFile')
            ->with($path)
            ->willReturn($file);
        $file->expects($this->once())
            ->method('Read')
            ->willReturn(<<<JSON
                {
                  "welcome_message": [
                    "Welcome to our application!",
                    "Uygulamamıza hoş geldiniz!",
                    "Välkommen till vår applikation!"
                  ]
                }
            JSON);
        $file->expects($this->once())
            ->method('Close');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Language code must be a string.');
        AccessHelper::CallMethod($sut, 'loadTranslationsFromFile', [$path]);
    }

    function testLoadTranslationsFromFileThrowsWhenLanguageCodeIsEmpty()
    {
        $sut = $this->systemUnderTest('openFile');
        $path = $this->createStub(CPath::class);
        $file = $this->createMock(CFile::class);

        $sut->expects($this->once())
            ->method('openFile')
            ->with($path)
            ->willReturn($file);
        $file->expects($this->once())
            ->method('Read')
            ->willReturn(<<<JSON
                {
                  "welcome_message": {
                    "": "Welcome to our application!",
                    "tr": "Uygulamamıza hoş geldiniz!",
                    "se": "Välkommen till vår applikation!"
                  }
                }
            JSON);
        $file->expects($this->once())
            ->method('Close');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Language code cannot be empty.');
        AccessHelper::CallMethod($sut, 'loadTranslationsFromFile', [$path]);
    }

    function testLoadTranslationsFromFileThrowsWhenTranslationIsNotString()
    {
        $sut = $this->systemUnderTest('openFile');
        $path = $this->createStub(CPath::class);
        $file = $this->createMock(CFile::class);

        $sut->expects($this->once())
            ->method('openFile')
            ->with($path)
            ->willReturn($file);
        $file->expects($this->once())
            ->method('Read')
            ->willReturn(<<<JSON
                {
                  "welcome_message": {
                    "en": 123,
                    "tr": "Uygulamamıza hoş geldiniz!",
                    "se": "Välkommen till vår applikation!"
                  }
                }
            JSON);
        $file->expects($this->once())
            ->method('Close');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Translation text must be a string.');
        AccessHelper::CallMethod($sut, 'loadTranslationsFromFile', [$path]);
    }

    function testLoadTranslationsFromFileSucceeds()
    {
        $sut = $this->systemUnderTest('openFile');
        $path = $this->createStub(CPath::class);
        $file = $this->createMock(CFile::class);

        $sut->expects($this->once())
            ->method('openFile')
            ->with($path)
            ->willReturn($file);
        $file->expects($this->once())
            ->method('Read')
            ->willReturn(<<<JSON
                {
                  "welcome_message": {
                    "en": "Welcome to our application!",
                    "tr": "Uygulamamıza hoş geldiniz!",
                    "se": "Välkommen till vår applikation!"
                  },
                  "logout_confirmation": {
                    "en": "Are you sure you want to log out?",
                    "tr": "Çıkış yapmak istediğinizden emin misiniz?",
                    "se": "Är du säker på att du vill logga ut?"
                  }
                }
            JSON);
        $file->expects($this->once())
            ->method('Close');

        $root = AccessHelper::CallMethod($sut, 'loadTranslationsFromFile', [$path]);

        $expected = [
            'welcome_message' => [
                'en' => 'Welcome to our application!',
                'tr' => 'Uygulamamıza hoş geldiniz!',
                'se' => 'Välkommen till vår applikation!'
            ],
            'logout_confirmation' => [
                'en' => 'Are you sure you want to log out?',
                'tr' => 'Çıkış yapmak istediğinizden emin misiniz?',
                'se' => 'Är du säker på att du vill logga ut?'
            ]
        ];
        $this->assertInstanceOf(CArray::class, $root);
        $this->assertCount(\count($expected), $root);
        foreach ($root as $translationId => $unit) {
            $this->assertInstanceOf(CArray::class, $unit);
            $this->assertSame($expected[$translationId], $unit->ToArray());
        }
    }

    #endregion loadTranslationsFromFile

    #region language -----------------------------------------------------------

    function testLanguageThrowsWhenConfigOptionLanguageNotFound()
    {
        $sut = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('Option')
            ->with('Language')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Language not set in configuration.');
        AccessHelper::CallMethod($sut, 'language');
    }

    #[DataProviderExternal(DataHelper::class, 'NonStringExcludingNullProvider')]
    function testLanguageThrowsWhenConfigOptionLanguageIsNotString($language)
    {
        $sut = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('Option')
            ->with('Language')
            ->willReturn($language);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Language setting is not a string.');
        AccessHelper::CallMethod($sut, 'language');
    }

    function testLanguageSucceeds()
    {
        $sut = $this->systemUnderTest();
        $config = Config::Instance();

        $config->expects($this->once())
            ->method('Option')
            ->with('Language')
            ->willReturn('en');

        $this->assertSame('en', AccessHelper::CallMethod($sut, 'language'));
        // Ensure the same instance is returned on subsequent calls.
        $this->assertSame('en', AccessHelper::CallMethod($sut, 'language'));
    }

    #endregion language

    #region Data Providers -----------------------------------------------------

    static function translationsDataProvider()
    {
        return [
            'both empty' => [
                new CArray(), // expected
                new CArray(), // base
                new CArray()  // override
            ],
            'empty base' => [
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome to our application!',
                        'tr' => 'Uygulamamıza hoş geldiniz!',
                        'se' => 'Välkommen till vår applikation!'
                    ]
                ]),
                new CArray(), // base
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome to our application!',
                        'tr' => 'Uygulamamıza hoş geldiniz!',
                        'se' => 'Välkommen till vår applikation!'
                    ]
                ])
            ],
            'empty override' => [
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome to our application!',
                        'tr' => 'Uygulamamıza hoş geldiniz!',
                        'se' => 'Välkommen till vår applikation!'
                    ]
                ]),
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome to our application!',
                        'tr' => 'Uygulamamıza hoş geldiniz!',
                        'se' => 'Välkommen till vår applikation!'
                    ]
                ]),
                new CArray() // override
            ],
            'distinct translation IDs' => [
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome to our application!',
                        'tr' => 'Uygulamamıza hoş geldiniz!',
                        'se' => 'Välkommen till vår applikation!'
                    ],
                    'logout_confirmation' => [
                        'en' => 'Are you sure you want to log out?',
                        'tr' => 'Çıkış yapmak istediğinizden emin misiniz?',
                        'se' => 'Är du säker på att du vill logga ut?'
                    ]
                ]),
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome to our application!',
                        'tr' => 'Uygulamamıza hoş geldiniz!',
                        'se' => 'Välkommen till vår applikation!'
                    ]
                ]),
                new CArray([
                    'logout_confirmation' => [
                        'en' => 'Are you sure you want to log out?',
                        'tr' => 'Çıkış yapmak istediğinizden emin misiniz?',
                        'se' => 'Är du säker på att du vill logga ut?'
                    ]
                ])
            ],
            'overriding single language' => [
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome to our application!',
                        'tr' => 'Hoş geldin!', // overridden
                        'se' => 'Välkommen till vår applikation!'
                    ]
                ]),
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome to our application!',
                        'tr' => 'Uygulamamıza hoş geldiniz!',
                        'se' => 'Välkommen till vår applikation!'
                    ]
                ]),
                new CArray([
                    'welcome_message' => [
                        'tr' => 'Hoş geldin!'
                    ]
                ])
            ],
            'new and overlapping keys' => [
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome back!',
                        'tr' => 'Uygulamamıza hoş geldiniz!',
                        'se' => 'Välkommen till vår applikation!'
                    ],
                    'logout_confirmation' => [
                        'en' => 'Are you sure you want to log out?',
                        'tr' => 'Çıkış yapmak istediğinizden emin misiniz?',
                        'se' => 'Är du säker på att du vill logga ut?'
                    ]
                ]),
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome to our application!',
                        'tr' => 'Uygulamamıza hoş geldiniz!',
                        'se' => 'Välkommen till vår applikation!'
                    ]
                ]),
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome back!',
                    ],
                    'logout_confirmation' => [
                        'en' => 'Are you sure you want to log out?',
                        'tr' => 'Çıkış yapmak istediğinizden emin misiniz?',
                        'se' => 'Är du säker på att du vill logga ut?'
                    ]
                ])
            ],
            'new language' => [
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome to our application!',
                        'tr' => 'Uygulamamıza hoş geldiniz!',
                        'se' => 'Välkommen till vår applikation!',
                        'de' => 'Willkommen in unserer Anwendung!' // new
                    ]
                ]),
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome to our application!',
                        'tr' => 'Uygulamamıza hoş geldiniz!',
                        'se' => 'Välkommen till vår applikation!'
                    ]
                ]),
                new CArray([
                    'welcome_message' => [
                        'de' => 'Willkommen in unserer Anwendung!'
                    ]
                ])
            ],
            'multiple overrides' => [
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome back!',
                        'tr' => 'Tekrar hoş geldin!',
                        'se' => 'Välkommen till vår applikation!'
                    ],
                    'logout_confirmation' => [
                        'en' => 'Confirm logout?',
                        'tr' => 'Çıkış yapmak istediğinizden emin misiniz?',
                        'se' => 'Bekräfta utloggning?'
                    ]
                ]),
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome to our application!',
                        'tr' => 'Uygulamamıza hoş geldiniz!',
                        'se' => 'Välkommen till vår applikation!'
                    ],
                    'logout_confirmation' => [
                        'en' => 'Are you sure you want to log out?',
                        'tr' => 'Çıkış yapmak istediğinizden emin misiniz?',
                        'se' => 'Är du säker på att du vill logga ut?'
                    ]
                ]),
                new CArray([
                    'welcome_message' => [
                        'en' => 'Welcome back!',
                        'tr' => 'Tekrar hoş geldin!'
                    ],
                    'logout_confirmation' => [
                        'en' => 'Confirm logout?',
                        'se' => 'Bekräfta utloggning?'
                    ]
                ])
            ]
        ];
    }

    #endregion Data Providers
}
