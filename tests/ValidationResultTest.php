<?php

declare(strict_types=1);

use Goez\OpenAPI\Tester\ValidationResult;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class ValidationResultTest extends PHPUnitTestCase
{
    public function test_create_report_for_success_result()
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
                'age' => [
                    'type' => 'number',
                ],
            ],
        ];

        $response = json_encode(['name' => 'john', 'age' => 999]);

        $validationResult = new ValidationResult($schema, $response, true, []);

        $report = $validationResult->createReport();

        $this->assertTrue($report['is_valid']);
        $this->assertJsonStringEqualsJsonString(json_encode($schema), $report['schema']);
        $this->assertEquals($response, $report['response']);
    }

    public function test_create_report_for_failed_result()
    {
        $schema = [
            'type' => 'object',
            'required' => ['name', 'age'],
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
                'age' => [
                    'type' => 'number',
                ],
            ],
        ];

        $response = json_encode(['name' => 1234]);

        $error = [
            [
                'type' => 'code',
                'message' => 'this is an error about wrong response code',
            ],
            [
                'type' => 'parse',
                'message' => 'this is an error about unable to parse response',
            ],
            [
                'type' => 'schema',
                'data' => [
                    'property' => 'age',
                    'pointer' => '/age',
                    'message' => 'The property age is required',
                    'constraint' => 'required',
                    'context' => 1,
                ],
            ],
            [
                'type' => 'schema',
                'data' => [
                    'property' => 'name',
                    'pointer' => '/name',
                    'message' => 'Integer value found, but a string is required',
                    'constraint' => 'type',
                    'context' => 1,
                ],
            ],
        ];

        $validationResult = new ValidationResult($schema, $response, false, $error);

        $report = $validationResult->createReport();

        $this->assertFalse($report['is_valid']);
        $this->assertJsonStringEqualsJsonString(json_encode($schema), $report['schema']);
        $this->assertEquals($response, $report['response']);
        $this->assertEquals([
            'this is an error about wrong response code',
            'this is an error about unable to parse response',
            '[Schema] /age (required): The property age is required',
            '[Schema] /name (type): Integer value found, but a string is required',
        ], $report['problems']);
    }

    public function test_render_report_for_success_result()
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
                'age' => [
                    'type' => 'number',
                ],
            ],
        ];

        $response = json_encode(['name' => 'john', 'age' => 999]);

        $validationResult = new ValidationResult($schema, $response, true, []);

        $render = $validationResult->renderReport();

        $this->assertStringContainsString('Validation Passed', $render);
        $this->assertStringContainsString('Response:', $render);
        $this->assertStringContainsString('Schema:', $render);
    }

    public function test_render_report_for_failed_result()
    {
        $schema = [
            'type' => 'object',
            'required' => ['name', 'age'],
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
                'age' => [
                    'type' => 'number',
                ],
            ],
        ];

        $response = json_encode(['name' => 1234]);

        $error = [
            [
                'type' => 'code',
                'message' => 'this is an error about wrong response code',
            ],
            [
                'type' => 'parse',
                'message' => 'this is an error about unable to parse response',
            ],
            [
                'type' => 'schema',
                'data' => [
                    'property' => 'age',
                    'pointer' => '/age',
                    'message' => 'The property age is required',
                    'constraint' => 'required',
                    'context' => 1,
                ],
            ],
            [
                'type' => 'schema',
                'data' => [
                    'property' => 'name',
                    'pointer' => '/name',
                    'message' => 'Integer value found, but a string is required',
                    'constraint' => 'type',
                    'context' => 1,
                ],
            ],
        ];

        $validationResult = new ValidationResult($schema, $response, false, $error);
        $render = $validationResult->renderReport();

        $this->assertStringContainsString('Validation Failed', $render);
        $this->assertStringContainsString('Problems:', $render);
        $this->assertStringContainsString('this is an error about wrong response code', $render);
        $this->assertStringContainsString('this is an error about unable to parse response', $render);
        $this->assertStringContainsString('[Schema] /name (type): Integer value found, but a string is required', $render);
        $this->assertStringContainsString('Response:', $render);
        $this->assertStringContainsString('Schema:', $render);
    }

    public function test_render_report_for_failed_result_without_colors()
    {
        $schema = [
            'type' => 'object',
            'required' => ['name', 'age'],
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
                'age' => [
                    'type' => 'number',
                ],
            ],
        ];

        $response = json_encode(['name' => 1234]);

        $error = [
            [
                'type' => 'code',
                'message' => 'this is an error about wrong response code',
            ],
            [
                'type' => 'parse',
                'message' => 'this is an error about unable to parse response',
            ],
            [
                'type' => 'schema',
                'data' => [
                    'property' => 'age',
                    'pointer' => '/age',
                    'message' => 'The property age is required',
                    'constraint' => 'required',
                    'context' => 1,
                ],
            ],
            [
                'type' => 'schema',
                'data' => [
                    'property' => 'name',
                    'pointer' => '/name',
                    'message' => 'Integer value found, but a string is required',
                    'constraint' => 'type',
                    'context' => 1,
                ],
            ],
        ];

        $validationResult = new ValidationResult($schema, $response, false, $error);
        $enableColor = false;
        $render = $validationResult->renderReport($enableColor);

        // No ANSI escape sequence allowed
        $this->assertNotRegExp('/(\x9B|\x1B\[)[0-?]*[ -\/]*[@-~]/', $render);
    }
}
