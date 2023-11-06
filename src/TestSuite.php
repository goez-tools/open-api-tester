<?php

namespace Goez\OpenAPI\Tester;

use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\Reader;
use cebe\openapi\ReferenceContext;
use cebe\openapi\spec\OpenApi;

class TestSuite
{
    private const EXTENSION_NAME = 'x-api-tests';

    /** @var array */
    private $warnings = [];
    /** @var string */
    private $name = '';
    /** @var array */
    private $testCases = [];

    public function __construct(string $name = 'API Tests')
    {
        $this->name = $name;
    }

    public function addTestCase(TestCase $testCase)
    {
        $name = $testCase->getReadableName();
        $originalName = $name;
        $index = 2;

        while (array_key_exists($name, $this->testCases)) {
            $name = $originalName . ' - ' . $index;
            $index++;
        }

        $this->testCases[$name] = $testCase;
    }

    public function addWarning(string $warning)
    {
        $this->warnings[] = $warning;
    }

    /**
     * Get the name of the test suite
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get test cases
     *
     * @return TestCase[]
     */
    public function getTestCases(): array
    {
        return $this->testCases;
    }

    /**
     * Get warning messages
     *
     * @return string[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Parse test items from the YAML-formatted Open API Spec 3 document
     *
     * @param string      $path         Full path to the document
     * @param string      $suiteName    Name of the test suite
     * @param string|null $externalPath Base path for external files
     *
     * @throws IOException
     * @throws TypeErrorException
     * @throws UnresolvableReferenceException
     *
     * @return TestSuite
     */
    public static function loadFromYamlFile(string $path, string $suiteName, string $externalPath = null): self
    {
        $docRoot = Reader::readFromYamlFile(
            realpath($path),
            OpenApi::class,
            ReferenceContext::RESOLVE_MODE_ALL
        );

        $externalPath = $externalPath ?: dirname(realpath($path));
        $testSuite = new self($suiteName);

        foreach ($docRoot->paths->getIterator() as $path => $pathDef) {
            foreach ($pathDef->getOperations() as $method => $operation) {
                $extensions = $operation->getExtensions();

                if (
                    !array_key_exists(static::EXTENSION_NAME, $extensions) ||
                    count($extensions[static::EXTENSION_NAME]) === 0
                ) {
                    $testSuite->addWarning(sprintf('[%s] %s', $method, $path) . ' has no tests.');
                    continue;
                }

                $testConfigs = $extensions[static::EXTENSION_NAME];

                foreach ($testConfigs as $config) {
                    if ($config['type'] !== 'request_test_case') {
                        continue;
                    }

                    $testSpec = $config['value'];
                    $testCase = new TestCase($testSpec, $method, $path, $operation, $externalPath);
                    $testSuite->addTestCase($testCase);
                }
            }
        }

        return $testSuite;
    }
}
