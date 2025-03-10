<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Validation\RuleParser;

use \Harmonia\Config;

#[CoversClass(RuleParser::class)]
class RuleParserTest extends TestCase
{
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalConfig = Config::ReplaceInstance($this->createConfigMock());
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalConfig);
    }

    private function createConfigMock()
    {
        $mock = $this->createMock(Config::class);
        $mock->expects($this->any())
            ->method('Option')
            ->with('Language')
            ->willReturn('en');
        return $mock;
    }

    #region Parse --------------------------------------------------------------

    function testParseThrowsIfRuleIsEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rule must be a non-empty string.');
        RuleParser::Parse('');
    }

    function testParseThrowsIfRuleIsWhitespace()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rule must be a non-empty string.');
        RuleParser::Parse('   ');
    }

    #[DataProvider('parseDataProvider')]
    function testParseSucceeds($expected, $rule)
    {
        $this->assertSame($expected, RuleParser::Parse($rule));
    }

    #endregion Parse

    #region Data Providers -----------------------------------------------------

    static function parseDataProvider()
    {
        return [
            [['name', null]   , 'name'         ],
            [['name', null]   , ' name'        ],
            [['name', null]   , 'name '        ],
            [['name', null]   , ' name '       ],
            [['name', null]   , 'name:'        ],
            [['name', null]   , ' name:'       ],
            [['name', null]   , 'name: '       ],
            [['name', null]   , ' name: '      ],
            [['name', null]   , 'name :'       ],
            [['name', null]   , ' name :'      ],
            [['name', null]   , 'name : '      ],
            [['name', null]   , ' name : '     ],
            [['name', 'param'], 'name:param'   ],
            [['name', 'param'], ' name:param'  ],
            [['name', 'param'], 'name:param '  ],
            [['name', 'param'], ' name:param ' ],
            [['name', 'param'], 'name :param'  ],
            [['name', 'param'], 'name: param'  ],
            [['name', 'param'], 'name : param' ],
            [['name', 'param'], ' name :param' ],
            [['name', 'param'], ' name: param' ],
            [['name', 'param'], 'name:param '  ],
            [['name', 'param'], 'name :param'  ],
            [['name', 'param'], 'name: param ' ],
            [['name', 'param'], ' name :param '],
            [['name', 'param'], ' name: param '],
            [['name', 'param'], 'name : param ']
        ];
    }

    #endregion Data Providers
}
