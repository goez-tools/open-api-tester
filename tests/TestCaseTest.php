<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\ReferenceContext;
use Goez\OpenAPI\Tester\TestCase;
use Goez\OpenAPI\Tester\ValidationResult;

class TestCaseTest extends PHPUnitTestCase
{
    public function setup(): void
    {
        $this->docPath = realpath(__DIR__ . '/test_subjects/test_case/api.yaml');
        $this->docBasePath = dirname(realpath($this->docPath));

        $this->docRoot = Reader::readFromYamlFile(
            $this->docPath,
            OpenAPI::class,
            ReferenceContext::RESOLVE_MODE_ALL
        );
    }

    public function test_throw_exception_when_response_not_found()
    {
        $operation = $this->docRoot->paths['/v1/posts']->get;

        // -----

        $this->expectException(InvalidArgumentException::class);

        new TestCase(
            [
                'response' => 204,
            ],
            'get',
            '/v1/posts',
            $operation,
            $this->docBasePath
        );
    }

    public function test_get_mocks()
    {
        $operation = $this->docRoot->paths['/v1/posts']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
                'mocks' => [
                    ['type' => 'guzzle', 'value' => 123],
                    ['type' => 'other', 'value' => 456],
                ],
            ],
            'get',
            '/v1/posts',
            $operation,
            $this->docBasePath
        );

        // -----

        $this->assertEquals([
            ['type' => 'guzzle', 'value' => 123],
            ['type' => 'other', 'value' => 456],
        ], $testCase->getMocks());
    }

    public function test_get_setup_hook()
    {
        $operation = $this->docRoot->paths['/v1/posts']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
                'setUp' => 'ExampleClass::setUp',
            ],
            'get',
            '/v1/posts',
            $operation,
            $this->docBasePath
        );

        // -----

        $this->assertEquals('ExampleClass::setUp', $testCase->getSetUpHook());
    }

    public function test_get_teardown_hook()
    {
        $operation = $this->docRoot->paths['/v1/posts']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
                'tearDown' => 'ExampleClass::tearDown',
            ],
            'get',
            '/v1/posts',
            $operation,
            $this->docBasePath
        );

        // -----

        $this->assertEquals('ExampleClass::tearDown', $testCase->getTearDownHook());
    }

    public function test_get_path()
    {
        $operation = $this->docRoot->paths['/v1/posts']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
            ],
            'get',
            '/v1/posts',
            $operation,
            $this->docBasePath
        );

        // -----

        $this->assertEquals('/v1/posts', $testCase->getPath());
    }

    public function test_get_method()
    {
        $operation = $this->docRoot->paths['/v1/posts']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
            ],
            'get',
            '/v1/posts',
            $operation,
            $this->docBasePath
        );

        // -----

        $this->assertEquals('get', $testCase->getMethod());
    }

    public function test_get_code()
    {
        $operation = $this->docRoot->paths['/v1/posts']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
            ],
            'get',
            '/v1/posts',
            $operation,
            $this->docBasePath
        );

        // -----

        $this->assertEquals(200, $testCase->getCode());
    }

    public function test_get_description()
    {
        $operation = $this->docRoot->paths['/v1/posts']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
                'description' => 'Get all published blog posts',
            ],
            'get',
            '/v1/posts',
            $operation,
            $this->docBasePath
        );

        // -----

        $this->assertEquals('Get all published blog posts', $testCase->getDescription());
    }

    public function test_get_readable_name_with_description()
    {
        $operation = $this->docRoot->paths['/v1/posts']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
                'description' => 'Get all published blog posts',
            ],
            'get',
            '/v1/posts',
            $operation,
            $this->docBasePath
        );

        // -----

        $this->assertEquals(
            '[get] /v1/posts Get blog posts: 200 - Get all published blog posts',
            $testCase->getReadableName()
        );
    }

    public function test_get_readable_name_without_description()
    {
        $operation = $this->docRoot->paths['/v1/posts']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
            ],
            'get',
            '/v1/posts',
            $operation,
            $this->docBasePath
        );

        // -----

        $this->assertEquals(
            '[get] /v1/posts Get blog posts: 200',
            $testCase->getReadableName()
        );
    }

    public function test_get_schema_default_media_type()
    {
        $operation = $this->docRoot->paths['/v1/posts']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
                'description' => 'Get all published blog posts',
            ],
            'get',
            '/v1/posts',
            $operation,
            $this->docBasePath
        );

        // -----

        $this->assertEquals([
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'title' => [
                        'type' => 'string',
                    ],
                    'likes' => [
                        'type' => 'number',
                    ],
                ],
            ],
        ], $testCase->getSchema());
    }

    public function test_get_schema_custom_media_type()
    {
        $operation = $this->docRoot->paths['/v1/posts']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
                'media-type' => 'application/xml',
            ],
            'get',
            '/v1/posts',
            $operation,
            $this->docBasePath
        );

        // -----

        $this->assertEquals([
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'title' => [
                        'type' => 'string',
                    ],
                    'likes' => [
                        'type' => 'number',
                    ],
                    'content_xml' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ], $testCase->getSchema());
    }

    public function test_get_request()
    {
        $operation = $this->docRoot->paths['/v1/users/{userId}']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
                'description' => 'Get user info',
            ],
            'get',
            '/v1/users/{userId}',
            $operation,
            $this->docBasePath
        );

        // -----

        $testRequest = $testCase->getRequest();
        $this->assertEquals('/v1/users/123456', $testRequest->getPath());
    }


    public function test_validation_success_json()
    {
        $operation = $this->docRoot->paths['/v1/users/{userId}']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
                'description' => 'Get user info',
            ],
            'get',
            '/v1/users/{userId}',
            $operation,
            $this->docBasePath
        );

        // -----

        $result = $testCase->validate(200, json_encode([
            'name' => 'John Doe',
            'age' => 420,
        ]));

        $this->assertTrue($result->isValid());
    }

    public function test_validation_success_parsed()
    {
        $operation = $this->docRoot->paths['/v1/users/{userId}']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
                'description' => 'Get user info',
            ],
            'get',
            '/v1/users/{userId}',
            $operation,
            $this->docBasePath
        );

        // -----

        $result = $testCase->validate(200, [
            'name' => 'John Doe',
            'age' => 420,
        ], TestCase::FORMAT_PARSED);

        $this->assertTrue($result->isValid());
    }

    public function test_validation_with_broken_json()
    {
        $operation = $this->docRoot->paths['/v1/users/{userId}']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
                'description' => 'Get user info',
            ],
            'get',
            '/v1/users/{userId}',
            $operation,
            $this->docBasePath
        );

        // -----

        $result = $testCase->validate(200, '{ "broken json', TestCase::FORMAT_JSON);

        $this->assertFalse($result->isValid());
        $firstError = $result->getErrors()[0];
        $this->assertEquals(ValidationResult::ERROR_TYPE_PARSE, $firstError['type']);
        $this->assertStringStartsWith('Unable to parse response as JSON.', $firstError['message']);
    }

    public function test_validation_with_unmatched_code()
    {
        $operation = $this->docRoot->paths['/v1/users/{userId}']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
                'description' => 'Get user info',
            ],
            'get',
            '/v1/users/{userId}',
            $operation,
            $this->docBasePath
        );

        // -----

        $result = $testCase->validate(204, [
            'name' => 'John Doe',
            'age' => 69,
        ], TestCase::FORMAT_PARSED);

        $this->assertFalse($result->isValid());
        $this->assertEquals(ValidationResult::ERROR_TYPE_CODE, $result->getErrors()[0]['type']);
        $this->assertEquals('204 does not match expected status code 200.', $result->getErrors()[0]['message']);
    }

    public function test_validation_with_response_not_match_schema()
    {
        $operation = $this->docRoot->paths['/v1/users/{userId}']->get;

        $testCase = new TestCase(
            [
                'response' => 200,
                'description' => 'Get user info',
            ],
            'get',
            '/v1/users/{userId}',
            $operation,
            $this->docBasePath
        );

        // -----

        $result = $testCase->validate(200, [
            'name' => 1234,
        ], TestCase::FORMAT_PARSED);

        $this->assertFalse($result->isValid());
        $this->assertEquals(ValidationResult::ERROR_TYPE_SCHEMA, $result->getErrors()[0]['type']);
    }
}
