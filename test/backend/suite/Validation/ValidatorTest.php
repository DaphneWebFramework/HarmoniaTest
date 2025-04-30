<?php declare(strict_types=1);
use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Validation\Validator;

use \Harmonia\Config;
use \Harmonia\Validation\DataAccessor;

#[CoversClass(Validator::class)]
class ValidatorTest extends TestCase
{
    private ?Config $originalConfig = null;

    protected function setUp(): void
    {
        $this->originalConfig = Config::ReplaceInstance($this->config());
    }

    protected function tearDown(): void
    {
        Config::ReplaceInstance($this->originalConfig);
    }

    private function config()
    {
        $mock = $this->createMock(Config::class);
        $mock->method('Option')->with('Language')->willReturn('en');
        return $mock;
    }

    #region Validate -----------------------------------------------------------

    #[DataProvider('validateDataProvider')]
    function testValidate(
        ?string $exceptionMessage,
        array $rules,
        array $data
    ) {
        $sut = new Validator($rules);

        if ($exceptionMessage !== null) {
            // Can be either an InvalidArgumentException or a RuntimeException.
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage($exceptionMessage);
        }

        $dataAccessor = $sut->Validate($data);

        if ($exceptionMessage === null) {
            $this->assertInstanceOf(DataAccessor::class, $dataAccessor);
        }
    }

    #endregion Validate

    #region Data Providers -----------------------------------------------------

    static function validateDataProvider()
    {
        return [
            'Passes with nested fields' => [
                null,
                [
                    'foo' => 'email',
                    'bar.qux' => 'email',
                    'bar.vax.koo' => ['numeric', 'max:60'],
                    'bar.vax.rok' => 'string',
                ],
                [
                    'foo' => 'john.doe@example.com',
                    'bar' => [
                        'qux' => 'john.doe@example.com',
                        'vax' => [
                            'koo' => 56.89,
                            'rok' => 'example'
                        ]
                    ]
                ]
            ],

            'Passes with custom rule' => [
                null,
                [
                    'username' => ['required', 'string', 'minLength:3'],
                    'email' => ['required', 'email'],
                    'rememberMe' => ['required', function($value) {
                        return 'on' === $value || 'off' === $value;
                    }]
                ],
                [
                    'username' => 'john.doe',
                    'email' => 'john.doe@example.com',
                    'rememberMe' => 'on'
                ]
            ],

            'Passes requiredWithout rule with ArtistName only' => [
                null,
                [
                    'ArtistName' => ['requiredWithout:ArtistId', 'string'],
                    'ArtistId' => ['requiredWithout:ArtistName', 'integer', 'min:1']
                ],
                [
                    'ArtistName' => 'Michael Jackson'
                ]
            ],

            'Passes requiredWithout rule with ArtistId only' => [
                null,
                [
                    'ArtistName' => ['requiredWithout:ArtistId', 'string'],
                    'ArtistId' => ['requiredWithout:ArtistName', 'integer', 'min:1']
                ],
                [
                    'ArtistId' => '5'
                ]
            ],

            'Fails requiredWithout rule when neither field is present' => [
                "Either field 'ArtistName' or 'ArtistId' must be present.",
                [
                    'ArtistName' => ['requiredWithout:ArtistId', 'string'],
                    'ArtistId' => ['requiredWithout:ArtistName', 'integer', 'min:1']
                ],
                []
            ],

            'Fails requiredWithout rule when both fields are present' => [
                "Only one of fields 'ArtistName' or 'ArtistId' can be present.",
                [
                    'ArtistName' => ['requiredWithout:ArtistId', 'string'],
                    'ArtistId' => ['requiredWithout:ArtistName', 'integer', 'min:1']
                ],
                [
                    'ArtistName' => 'Michael Jackson',
                    'ArtistId' => '5'
                ]
            ],

            'Passes requiredWithout rule with ArtistId only (multi-field)' => [
                null,
                [
                    'ArtistFirstName' => ['requiredWithout:ArtistId', 'string'],
                    'ArtistLastName' => ['requiredWithout:ArtistId', 'string'],
                    'ArtistId' => [
                        'requiredWithout:ArtistFirstName',
                        'requiredWithout:ArtistLastName',
                        'integer',
                        'min:1'
                    ]
                ],
                [
                    'ArtistId' => '5'
                ]
            ],

            'Passes requiredWithout rule with ArtistFirstName and ArtistLastName' => [
                null,
                [
                    'ArtistFirstName' => ['requiredWithout:ArtistId', 'string'],
                    'ArtistLastName' => ['requiredWithout:ArtistId', 'string'],
                    'ArtistId' => [
                        'requiredWithout:ArtistFirstName',
                        'requiredWithout:ArtistLastName',
                        'integer',
                        'min:1'
                    ]
                ],
                [
                    'ArtistFirstName' => 'Michael',
                    'ArtistLastName' => 'Jackson'
                ]
            ],

            'Fails requiredWithout rule when no fields are present (multi-field)' => [
                "Either field 'ArtistFirstName' or 'ArtistId' must be present.",
                [
                    'ArtistFirstName' => ['requiredWithout:ArtistId', 'string'],
                    'ArtistLastName' => ['requiredWithout:ArtistId', 'string'],
                    'ArtistId' => [
                        'requiredWithout:ArtistFirstName',
                        'requiredWithout:ArtistLastName',
                        'integer',
                        'min:1'
                    ]
                ],
                []
            ],

            'Fails requiredWithout rule when all fields are present (multi-field)' => [
                "Only one of fields 'ArtistFirstName' or 'ArtistId' can be present.",
                [
                    'ArtistFirstName' => ['requiredWithout:ArtistId', 'string'],
                    'ArtistLastName' => ['requiredWithout:ArtistId', 'string'],
                    'ArtistId' => [
                        'requiredWithout:ArtistFirstName',
                        'requiredWithout:ArtistLastName',
                        'integer',
                        'min:1'
                    ]
                ],
                [
                    'ArtistFirstName' => 'Michael',
                    'ArtistLastName' => 'Jackson',
                    'ArtistId' => '5'
                ]
            ],

            'Passes requiredWithout rule with SocialSecurityNumber only' => [
                null,
                [
                    'SocialSecurityNumber' => [
                        'requiredWithout:PassportNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{3}-\d{2}-\d{4}$/'
                    ],
                    'PassportNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{9}$/'
                    ],
                    'DriverLicenseNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:PassportNumber',
                        'regex:/^DL\d{6}$/'
                    ]
                ],
                [
                    'SocialSecurityNumber' => '123-45-6789'
                ]
            ],

            'Passes requiredWithout rule with PassportNumber only' => [
                null,
                [
                    'SocialSecurityNumber' => [
                        'requiredWithout:PassportNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{3}-\d{2}-\d{4}$/'
                    ],
                    'PassportNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{9}$/'
                    ],
                    'DriverLicenseNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:PassportNumber',
                        'regex:/^DL\d{6}$/'
                    ]
                ],
                [
                    'PassportNumber' => '987654321'
                ]
            ],

            'Passes requiredWithout rule with DriverLicenseNumber only' => [
                null,
                [
                    'SocialSecurityNumber' => [
                        'requiredWithout:PassportNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{3}-\d{2}-\d{4}$/'
                    ],
                    'PassportNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{9}$/'
                    ],
                    'DriverLicenseNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:PassportNumber',
                        'regex:/^DL\d{6}$/'
                    ]
                ],
                [
                    'DriverLicenseNumber' => 'DL123456'
                ]
            ],

            'Fails requiredWithout rule when no identification fields are present' => [
                "Either field 'SocialSecurityNumber' or one of 'PassportNumber', 'DriverLicenseNumber' must be present.",
                [
                    'SocialSecurityNumber' => [
                        'requiredWithout:PassportNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{3}-\d{2}-\d{4}$/'
                    ],
                    'PassportNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{9}$/'
                    ],
                    'DriverLicenseNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:PassportNumber',
                        'regex:/^DL\d{6}$/'
                    ]
                ],
                []
            ],

            'Fails requiredWithout rule when all identification fields are present' => [
                "Only one of fields 'SocialSecurityNumber' or one of 'PassportNumber', 'DriverLicenseNumber' can be present.",
                [
                    'SocialSecurityNumber' => [
                        'requiredWithout:PassportNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{3}-\d{2}-\d{4}$/'
                    ],
                    'PassportNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{9}$/'
                    ],
                    'DriverLicenseNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:PassportNumber',
                        'regex:/^DL\d{6}$/'
                    ]
                ],
                [
                    'SocialSecurityNumber' => '123-45-6789',
                    'PassportNumber' => '987654321',
                    'DriverLicenseNumber' => 'DL123456'
                ]
            ],

            'Fails requiredWithout rule with PassportNumber and DriverLicenseNumber' => [
                "Only one of fields 'PassportNumber' or one of 'SocialSecurityNumber', 'DriverLicenseNumber' can be present.",
                [
                    'SocialSecurityNumber' => [
                        'requiredWithout:PassportNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{3}-\d{2}-\d{4}$/'
                    ],
                    'PassportNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{9}$/'
                    ],
                    'DriverLicenseNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:PassportNumber',
                        'regex:/^DL\d{6}$/'
                    ]
                ],
                [
                    'PassportNumber' => '987654321',
                    'DriverLicenseNumber' => 'DL123456'
                ]
            ],

            'Fails requiredWithout rule with SocialSecurityNumber and DriverLicenseNumber' => [
                "Only one of fields 'SocialSecurityNumber' or one of 'PassportNumber', 'DriverLicenseNumber' can be present.",
                [
                    'SocialSecurityNumber' => [
                        'requiredWithout:PassportNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{3}-\d{2}-\d{4}$/'
                    ],
                    'PassportNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{9}$/'
                    ],
                    'DriverLicenseNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:PassportNumber',
                        'regex:/^DL\d{6}$/'
                    ]
                ],
                [
                    'SocialSecurityNumber' => '123-45-6789',
                    'DriverLicenseNumber' => 'DL123456'
                ]
            ],

            'Fails requiredWithout rule with SocialSecurityNumber and PassportNumber' => [
                "Only one of fields 'SocialSecurityNumber' or one of 'PassportNumber', 'DriverLicenseNumber' can be present.",
                [
                    'SocialSecurityNumber' => [
                        'requiredWithout:PassportNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{3}-\d{2}-\d{4}$/'
                    ],
                    'PassportNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:DriverLicenseNumber',
                        'regex:/^\d{9}$/'
                    ],
                    'DriverLicenseNumber' => [
                        'requiredWithout:SocialSecurityNumber',
                        'requiredWithout:PassportNumber',
                        'regex:/^DL\d{6}$/'
                    ]
                ],
                [
                    'SocialSecurityNumber' => '123-45-6789',
                    'PassportNumber' => '987654321'
                ]
            ],

            'Fails requiredWithout rule with empty field name (colon)' => [
                "Rule 'requiredWithout' must be used with a field name.",
                [
                    'ArtistName' => ['requiredWithout:', 'string'],
                    'ArtistId' => ['requiredWithout:ArtistName', 'integer', 'min:1']
                ],
                [
                    'ArtistName' => 'Michael Jackson'
                ]
            ],

            'Fails requiredWithout rule with no field name' => [
                "Rule 'requiredWithout' must be used with a field name.",
                [
                    'ArtistName' => ['requiredWithout', 'string'],
                    'ArtistId' => ['requiredWithout:ArtistName', 'integer', 'min:1']
                ],
                [
                    'ArtistName' => 'Michael Jackson'
                ]
            ],

            'Passes requiredWithout rule with case-insensitive rule names' => [
                null,
                [
                    'ArtistName' => ['REQUIREDWITHOUT:ArtistId', 'string'],
                    'ArtistId' => ['RequiredWithout:ArtistName', 'integer', 'min:1']
                ],
                [
                    'ArtistName' => 'Michael Jackson'
                ]
            ],

            'Passes required and requiredWithout rules with ArtistName' => [
                null,
                [
                    'ArtistName' => ['required', 'requiredWithout:ArtistId', 'string'],
                    'ArtistId' => ['requiredWithout:ArtistName', 'integer', 'min:1']
                ],
                [
                    'ArtistName' => 'Michael Jackson'
                ]
            ],

            'Fails required and requiredWithout rules with only ArtistId' => [
                "Required field 'ArtistName' is missing.",
                [
                    'ArtistName' => ['required', 'requiredWithout:ArtistId', 'string'],
                    'ArtistId' => ['requiredWithout:ArtistName', 'integer', 'min:1']
                ],
                [
                    'ArtistId' => '5'
                ]
            ],

            'Fails required and requiredWithout rules with no fields' => [
                "Required field 'ArtistName' is missing.",
                [
                    'ArtistName' => ['required', 'requiredWithout:ArtistId', 'string'],
                    'ArtistId' => ['requiredWithout:ArtistName', 'integer', 'min:1']
                ],
                []
            ],

            'Fails required and requiredWithout rules when both fields are present' => [
                "Only one of fields 'ArtistName' or 'ArtistId' can be present.",
                [
                    'ArtistName' => ['required', 'requiredWithout:ArtistId', 'string'],
                    'ArtistId' => ['required', 'requiredWithout:ArtistName', 'integer', 'min:1']
                ],
                [
                    'ArtistName' => 'Michael Jackson',
                    'ArtistId' => '5'
                ]
            ],

            'Passes account register validation' => [
                null,
                [
                    'email',
                    'regex:/^[A-Za-z_][\w\-\.]{1,31}$/',
                    ['minLength:8', 'maxLength:72']
                ],
                [
                    'john.doe@example.com',
                    'john.doe',
                    'TestPass123!'
                ]
            ],

            'Passes account activate validation' => [
                null,
                [
                    'regex:/^[a-f0-9]{64}$/'
                ],
                [
                    'a3f4b6e8129c0d5e7f8a6b4c3d2e1f09876e4c3b2a1f0e9d8c7b6a5f4e3d2c1b'
                ]
            ],

            'Passes account send password reset email validation' => [
                null,
                [
                    'email'
                ],
                [
                    'john.doe@example.com'
                ]
            ],

            'Passes account reset password validation' => [
                null,
                [
                    'regex:/^[a-f0-9]{64}$/',
                    ['minLength:8', 'maxLength:72']
                ],
                [
                    'a3f4b6e8129c0d5e7f8a6b4c3d2e1f09876e4c3b2a1f0e9d8c7b6a5f4e3d2c1b',
                    'NewPass123!'
                ]
            ],

            'Passes account login validation' => [
                null,
                [
                    'regex:/^[A-Za-z_][\w\-\.]{1,31}$/',
                    ['minLength:8', 'maxLength:72']
                ],
                [
                    'john.doe',
                    'TestPass123!'
                ]
            ],

            'Passes account change password validation' => [
                null,
                [
                    ['minLength:8', 'maxLength:72'],
                    ['minLength:8', 'maxLength:72']
                ],
                [
                    'OldPass123!',
                    'NewPass123!'
                ]
            ],

            'Passes administrator add account validation' => [
                null,
                [
                    'Email' => ['required', 'email'],
                    'Username' => ['required', 'regex:/^[A-Za-z_][\w\-\.]{1,31}$/'],
                    'PasswordHash' => ['required', 'regex:/^\$2[aby]?\$\d{1,2}\$[.\/A-Za-z0-9]{53}$/']
                ],
                [
                    'Email' => 'john.doe@example.com',
                    'Username' => 'john.doe',
                    'PasswordHash' => '$2a$08$TYykDj.WYELAn3U4bTsmo.aXPEi44da.Q8dgJi29Adu4zH4wzKAnK'
                ]
            ],

            'Passes administrator edit account validation' => [
                null,
                [
                    'ID' => ['required', 'integer', 'min:1'],
                    'Email' => ['email'],
                    'Username' => ['regex:/^[A-Za-z_][\w\-\.]{1,31}$/'],
                    'PasswordHash' => ['regex:/^\$2[aby]?\$\d{1,2}\$[.\/A-Za-z0-9]{53}$/']
                ],
                [
                    'ID' => '23',
                    'Username' => 'john.doe',
                    'PasswordHash' => '$2a$08$TYykDj.WYELAn3U4bTsmo.aXPEi44da.Q8dgJi29Adu4zH4wzKAnK'
                ]
            ],

            'Passes administrator add account role validation' => [
                null,
                [
                    'AccountId' => ['required', 'integer', 'min:1'],
                    'Role' => ['required', 'integer', 'min:0', 'max:2']
                ],
                [
                    'AccountId' => '45',
                    'Role' => '0'
                ]
            ],

            'Passes administrator edit account role validation' => [
                null,
                [
                    'ID' => ['required', 'integer', 'min:1'],
                    'AccountId' => ['integer', 'min:1'],
                    'Role' => ['integer', 'min:0', 'max:2']
                ],
                [
                    'ID' => '23',
                    'AccountId' => '45',
                    'Role' => '0'
                ]
            ],

            'Passes administrator add pending account validation' => [
                null,
                [
                    'Email' => ['required', 'email'],
                    'Username' => ['required', 'regex:/^[A-Za-z_][\w\-\.]{1,31}$/'],
                    'PasswordHash' => ['required', 'regex:/^\$2[aby]?\$\d{1,2}\$[.\/A-Za-z0-9]{53}$/'],
                    'ActivationCode' => ['required', 'regex:/^[a-f0-9]{64}$/']
                ],
                [
                    'Email' => 'john.doe@example.com',
                    'Username' => 'john.doe',
                    'PasswordHash' => '$2a$08$TYykDj.WYELAn3U4bTsmo.aXPEi44da.Q8dgJi29Adu4zH4wzKAnK',
                    'ActivationCode' => 'a3f4b6e8129c0d5e7f8a6b4c3d2e1f09876e4c3b2a1f0e9d8c7b6a5f4e3d2c1b'
                ]
            ],

            'Passes administrator edit pending account validation' => [
                null,
                [
                    'ID' => ['required', 'integer', 'min:1'],
                    'Email' => ['email'],
                    'Username' => ['regex:/^[A-Za-z_][\w\-\.]{1,31}$/'],
                    'PasswordHash' => ['regex:/^\$2[aby]?\$\d{1,2}\$[.\/A-Za-z0-9]{53}$/'],
                    'ActivationCode' => ['regex:/^[a-f0-9]{64}$/']
                ],
                [
                    'ID' => '23',
                    'Email' => 'john.doe@example.com',
                    'Username' => 'john.doe',
                    'PasswordHash' => '$2a$08$TYykDj.WYELAn3U4bTsmo.aXPEi44da.Q8dgJi29Adu4zH4wzKAnK',
                    'ActivationCode' => 'a3f4b6e8129c0d5e7f8a6b4c3d2e1f09876e4c3b2a1f0e9d8c7b6a5f4e3d2c1b'
                ]
            ],

            'Passes administrator add forgetful account validation' => [
                null,
                [
                    'AccountId' => ['required', 'integer', 'min:1'],
                    'ResetCode' => ['required', 'regex:/^[a-f0-9]{64}$/']
                ],
                [
                    'AccountId' => '45',
                    'ResetCode' => 'a3f4b6e8129c0d5e7f8a6b4c3d2e1f09876e4c3b2a1f0e9d8c7b6a5f4e3d2c1b'
                ]
            ],

            'Passes administrator edit forgetful account validation' => [
                null,
                [
                    'ID' => ['required', 'integer', 'min:1'],
                    'AccountId' => ['integer', 'min:1'],
                    'ResetCode' => ['regex:/^[a-f0-9]{64}$/']
                ],
                [
                    'ID' => '23',
                    'AccountId' => '45',
                    'ResetCode' => 'a3f4b6e8129c0d5e7f8a6b4c3d2e1f09876e4c3b2a1f0e9d8c7b6a5f4e3d2c1b'
                ]
            ],
        ];
    }

    #endregion Data Providers
}
