<?php declare(strict_types=1);
namespace suite\Systems\ValidationSystem\Requirements;

use \PHPUnit\Framework\TestCase;
use \PHPUnit\Framework\Attributes\CoversClass;
use \PHPUnit\Framework\Attributes\DataProvider;

use \Harmonia\Systems\ValidationSystem\Requirements\RequirementEngine;

use \Harmonia\Systems\ValidationSystem\DataAccessor;
use \Harmonia\Systems\ValidationSystem\MetaRules\IMetaRule;

#[CoversClass(RequirementEngine::class)]
class RequirementEngineTest extends TestCase
{
    private function createMetaRule(string $name, mixed $param = null): IMetaRule
    {
        $mock = $this->createMock(IMetaRule::class);
        $mock->method('GetName')->willReturn($name);
        $mock->method('GetParam')->willReturn($param);
        return $mock;
    }

    private function createMetaRules(array $rules): array
    {
        return \array_map(
            fn($rule) => $this->createMetaRule(...$rule),
            $rules
        );
    }

    private function createDataAccessor(array $fields = []): DataAccessor
    {
        $mock = $this->createMock(DataAccessor::class);
        $mock->method('HasField')->willReturnCallback(
            fn($field) => \array_key_exists($field, $fields));
        return $mock;
    }

    #region Validate -----------------------------------------------------------

    #[DataProvider('validateDataProvider')]
    function testValidate(
        ?string $exceptionMessage,
        string $field,
        array $rules,
        array $data
    ) {
        // Exceptions can be thrown both from the constructor and the Validate.
        if ($exceptionMessage !== null) {
            // Can be either an InvalidArgumentException or a RuntimeException.
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage($exceptionMessage);
        }

        $sut = new RequirementEngine(
            $field,
            $this->createMetaRules($rules),
            $this->createDataAccessor($data)
        );
        $sut->Validate();

        if ($exceptionMessage === null) {
            $this->expectNotToPerformAssertions();
        }
    }

    #endregion Validate

    #region ShouldSkipFurtherValidation ----------------------------------------

    #[DataProvider('shouldSkipFurtherValidationDataProvider')]
    function testShouldSkipFurtherValidation(
        bool $expected,
        string $field,
        array $rules,
        array $data
    ) {
        $sut = new RequirementEngine(
            $field,
            $this->createMetaRules($rules),
            $this->createDataAccessor($data)
        );

        $this->assertSame($expected, $sut->ShouldSkipFurtherValidation());
    }

    #endregion ShouldSkipFurtherValidation

    #region FilterOutRequirementRules ------------------------------------------

    function testFilterOutRequirementRules()
    {
        $metaRules = [
            $this->createMetaRule('required'),
            $this->createMetaRule('requiredwithout', 'someField'),
            $this->createMetaRule('string')
        ];
        $sut = new RequirementEngine(
            'myField',
            $metaRules,
            $this->createDataAccessor()
        );

        $filteredRules = $sut->FilterOutRequirementRules($metaRules);

        $this->assertCount(1, $filteredRules);
        $this->assertSame(
            'string',
            $filteredRules[\array_key_first($filteredRules)]->GetName()
        );
    }

    #endregion FilterOutRequirementRules

    #region Data Providers -----------------------------------------------------

    static function validateDataProvider()
    {
        return [
            'Passes when there are no validation rules' => [
                null,            // exception message
                'myField',       // field
                [],              // rules
                ['myField' => 1] // data
            ],
            'Passes when required field is present' => [
                null,
                'myField',
                [['required']],
                ['myField' => 1]
            ],
            'Passes when requiredWithout field is present' => [
                null,
                'myField',
                [['requiredwithout', 'otherField']],
                ['otherField' => 1]
            ],
            'Passes when at least one requiredWithout field is present' => [
                null,
                'myField',
                [['requiredwithout', 'otherField1'], ['requiredwithout', 'otherField2']],
                ['otherField2' => 1]
            ],
            'Fails when field and its requiredWithout field both exist' => [
                "Only one of fields 'myField' or 'otherField' can be present.",
                'myField',
                [['requiredwithout', 'otherField']],
                ['myField' => 1, 'otherField' => 1]
            ],
            'Fails when required and requiredWithout rules exist, and field is present' => [
                "Only one of fields 'myField' or 'otherField' can be present.",
                'myField',
                [['required'], ['requiredwithout', 'otherField']],
                ['myField' => 1, 'otherField' => 1]
            ],
            'Fails when required field is missing despite a requiredWithout field' => [
                "Required field 'myField' is missing.",
                'myField',
                [['required'], ['requiredwithout', 'otherField']],
                ['otherField' => 1]
            ],
            'Fails when required field is absent' => [
                "Required field 'myField' is missing.",
                'myField',
                [['required']],
                []
            ],
            'Fails when requiredWithout field is absent' => [
                "Either field 'myField' or 'otherField' must be present.",
                'myField',
                [['requiredwithout', 'otherField']],
                []
            ],
            'Fails when all requiredWithout fields are missing' => [
                "Either field 'myField' or one of 'otherField1', 'otherField2' must be present.",
                'myField',
                [['requiredwithout', 'otherField1'], ['requiredwithout', 'otherField2']],
                []
            ],
            'Fails when requiredWithout rule has an empty field name' => [
                "Rule 'requiredWithout' must be used with a field name.",
                'myField',
                [['requiredwithout', null]],
                []
            ],
            'Fails when requiredWithout rule refers to the same field' => [
                "Rule 'requiredWithout' must not reference the field itself.",
                'myField',
                [['requiredwithout', 'myField']],
                []
            ],
        ];
    }

    static function shouldSkipFurtherValidationDataProvider()
    {
        return [
            'Does not skip when field is present' => [
                false, 'myField', [['required']], ['myField' => 1]
            ],
            'Skips when field is missing and requiredWithout field exists' => [
                true, 'myField', [['requiredwithout', 'otherField']], ['otherField' => 1]
            ],
            'Skips when field is missing and is not required' => [
                true, 'myField', [], []
            ],
            'Does not skip when field is missing but required' => [
                false, 'myField', [['required']], []
            ]
        ];
    }

    #endregion Data Providers
}
