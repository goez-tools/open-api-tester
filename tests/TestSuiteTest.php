<?php

declare(strict_types=1);

use Goez\OpenAPI\Tester\TestCase;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Goez\OpenAPI\Tester\TestSuite;

class TestSuiteTest extends PHPUnitTestCase
{
    public function test_parse_parse_and_returns_test_cases()
    {
        $testName = __METHOD__;
        $docPath = __DIR__ . '/test_subjects/test_suite/parse_and_returns_test_cases/api.yaml';

        $testSuite = TestSuite::loadFromYamlFile($docPath, $testName);

        $testCases = $testSuite->getTestCases();


        $this->assertEquals($testName, $testSuite->getName());
        $this->assertEquals(3, count($testCases));
        $this->assertEquals([
            '[get] /v1/users/{userId} Get user data: 200',
            '[get] /v1/users/{userId} Get user data: 404 - This is custom description.',
            '[get] /v1/posts Get blog posts: 200',
        ], array_keys($testCases));

        $key = '[get] /v1/users/{userId} Get user data: 200';

        $this->assertInstanceOf(TestCase::class, $testCases[$key]);
        $this->assertEquals('/v1/users/{userId}', $testCases[$key]->getPath());
        $this->assertEquals('get', $testCases[$key]->getMethod());
        $this->assertEquals('200', $testCases[$key]->getCode());
        $this->assertEquals('', $testCases[$key]->getDescription());

        $key = '[get] /v1/users/{userId} Get user data: 404 - This is custom description.';
        $this->assertInstanceOf(TestCase::class, $testCases[$key]);
        $this->assertEquals('/v1/users/{userId}', $testCases[$key]->getPath());
        $this->assertEquals('get', $testCases[$key]->getMethod());
        $this->assertEquals('404', $testCases[$key]->getCode());
        $this->assertEquals('This is custom description.', $testCases[$key]->getDescription());

        $key = '[get] /v1/posts Get blog posts: 200';
        $this->assertInstanceOf(TestCase::class, $testCases[$key]);
        $this->assertEquals('/v1/posts', $testCases[$key]->getPath());
        $this->assertEquals('get', $testCases[$key]->getMethod());
        $this->assertEquals('200', $testCases[$key]->getCode());
        $this->assertEquals('', $testCases[$key]->getDescription());
    }

    public function test_warnings_if_no_tests_defined()
    {
        $testName = __METHOD__;
        $docPath = __DIR__ . '/test_subjects/test_suite/warnings_if_no_tests_defined/api.yaml';

        $testSuite = TestSuite::loadFromYamlFile($docPath, $testName);

        $testCases = $testSuite->getTestCases();

        $this->assertEquals($testName, $testSuite->getName());
        $this->assertEquals(0, count($testCases));
        $this->assertEquals([
            '[get] /v1/posts has no tests.',
        ], $testSuite->getWarnings());
    }

    public function test_tests_with_same_name()
    {
        $testName = __METHOD__;
        $docPath = __DIR__ . '/test_subjects/test_suite/same_name_tests/api.yaml';

        $testSuite = TestSuite::loadFromYamlFile($docPath, $testName);

        $testCases = $testSuite->getTestCases();

        $this->assertEquals($testName, $testSuite->getName());
        $this->assertEquals(2, count($testCases));
    }

    public function test_use_default_external_base_path()
    {
        $testName = __METHOD__;
        $docPath = __DIR__ . '/test_subjects/test_suite/parse_and_returns_test_cases/api.yaml';

        $testSuite = TestSuite::loadFromYamlFile($docPath, $testName);

        $testCases = $testSuite->getTestCases();
        $firstTestCase = array_keys($testCases)[0];

        $this->assertEquals(
            __DIR__ . '/test_subjects/test_suite/parse_and_returns_test_cases',
            $testCases[$firstTestCase]->getExternalBasePath()
        );
    }

    public function test_use_custom_external_base_path()
    {
        $testName = __METHOD__;
        $docPath = __DIR__ . '/test_subjects/test_suite/parse_and_returns_test_cases/api.yaml';

        $testSuite = TestSuite::loadFromYamlFile($docPath, $testName, '/tmp/unit_test');

        $testCases = $testSuite->getTestCases();
        $firstTestCase = array_keys($testCases)[0];

        $this->assertEquals(
            '/tmp/unit_test',
            $testCases[$firstTestCase]->getExternalBasePath()
        );
    }
}
