<?php

namespace Goez\OpenAPI\Tester;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Response;
use Exception;
use Goez\OpenAPI\Tester\Request\Request;
use InvalidArgumentException;
use JsonSchema\Validator;

class TestCase
{
    public const FORMAT_JSON = 'json';
    public const FORMAT_PARSED = 'parsed';

    /** @var int|mixed|null */
    private $code;
    /** @var string */
    private $method;
    /** @var string|mixed */
    private $mediaType;
    /** @var string */
    private $path;
    /** @var Operation */
    private $operation;
    /** @var array */
    private $testSpec;
    /** @var string */
    private $externalBasePath;

    /** @var Request */
    private $Request;
    /** @var array */
    private $schema;
    /** @var array */
    private $mocks;

    /** @var mixed */
    private $setUpHook;

    /** @var mixed|null */
    private $tearDownHook;

    public function __construct(
        array $testSpec,
        string $method,
        string $path,
        Operation $operation,
        string $externalBasePath
    ) {
        $this->testSpec = $testSpec;
        $this->code = $testSpec['response'] ?? null;
        $this->mediaType = $testSpec['media-type'] ?? 'application/json';
        $this->method = strtolower($method);
        $this->path = $path;
        $this->operation = $operation;
        $this->externalBasePath = $externalBasePath;

        if (!$this->operation->responses->hasResponse($this->testSpec['response'])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot found response "%s" in [%s] %s.',
                    $this->testSpec['response'],
                    $this->method,
                    $this->path
                )
            );
        }

        $this->Request = new Request($method, $path, $this->testSpec, $operation, $this->externalBasePath);
        $this->schema = $this->extractJsonSchema($operation->responses[$this->code] ?? null);
        $this->mocks = $this->testSpec['mocks'] ?? [];
        $this->setUpHook = $this->testSpec['setUp'] ?? null;
        $this->tearDownHook = $this->testSpec['tearDown'] ?? null;
    }

    /**
     * Retrieve the request object corresponding to this test.
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->Request;
    }

    /**
     * Get the expected HTTP response code for this test.
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Get the description message of this test.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->testSpec['description'] ?? '';
    }

    /**
     * Get the API path under test.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the HTTP request method of the API under test.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the expected JSON Schema definition of the response.
     *
     * @return array
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * Get Mock definitions.
     *
     * @return array
     */
    public function getMocks(): array
    {
        return $this->mocks;
    }

    /**
     * Get setUp definition.
     *
     * @return mixed
     */
    public function getSetUpHook()
    {
        return $this->setUpHook;
    }

    /**
     * Get tearDown definition.
     *
     * @return mixed
     */
    public function getTearDownHook()
    {
        return $this->tearDownHook;
    }

    /**
     * Get the base path of external files.
     *
     * @return string
     */
    public function getExternalBasePath(): string
    {
        return $this->externalBasePath;
    }

    /**
     * Validate if the given response matches the expectation of this test.
     *
     * If the response content is a JSON string, validate as:
     *   ->validate(200, $jsonString, TestCase::FORMAT_JSON)
     *
     * If the response content is a parsed PHP array:
     *   ->validate(200, $responseArray, TestCase::FORMAT_PARSED)
     *
     * @param int          $code     The received HTTP response code
     * @param string|array $response The received response content
     * @param string       $format   Format of the response content
     *
     * @return ValidationResult
     */
    public function validate(int $code, $response, string $format = self::FORMAT_JSON): ValidationResult
    {
        $errors = [];
        $isValid = true;

        try {
            $data = null;
            $stringData = '';

            switch ($format) {
                case self::FORMAT_JSON:
                    $data = @json_decode($response, false, 512);
                    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception();
                    }
                    $stringData = $response;
                    break;
                case self::FORMAT_PARSED:
                    $data = $this->toStdClassObject($response);
                    $stringData = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    break;
                default:
                    throw new InvalidArgumentException(sprintf('Unknown format "%s".', $format));
            }
        } catch (Exception $ex) {
            $isValid = false;
            $errors[] = [
                'type' => ValidationResult::ERROR_TYPE_PARSE,
                'message' => 'Unable to parse response as JSON. ' . $ex->getMessage(),
            ];
        }

        $schema = $this->getSchema();
        $expectedCode = $this->getCode();

        if ($code !== $expectedCode) {
            $isValid = false;
            $errors[] = [
                'type' => ValidationResult::ERROR_TYPE_CODE,
                'message' => sprintf('%d does not match expected status code %d.', $code, $expectedCode),
            ];
        }

        $validator = new Validator();
        $validator->validate($data, $schema);
        $isSchemaValid = $validator->isValid();
        $schemaErrors = array_map(function ($error) {
            return [
                'type' => ValidationResult::ERROR_TYPE_SCHEMA,
                'data' => $error,
            ];
        }, $validator->getErrors() ?: []);

        return new ValidationResult(
            $schema,
            $stringData,
            $isValid && $isSchemaValid,
            array_merge($errors, $schemaErrors)
        );
    }

    /**
     * Get the readable name of this test.
     *
     * Typically used in Test reports for developers to read, simply describes the test API path and response code.
     *
     * @return string
     */
    public function getReadableName(): string
    {
        $description = $this->getDescription();

        if ($description) {
            return sprintf(
                '[%s] %s %s: %d - %s',
                $this->method,
                $this->path,
                $this->operation->summary,
                $this->code,
                $description
            );
        } else {
            return sprintf(
                '[%s] %s %s: %d',
                $this->method,
                $this->path,
                $this->operation->summary,
                $this->code
            );
        }
    }

    private function extractJsonSchema(?Response $response)
    {
        return $response && isset($response->content[$this->mediaType]) ?
            // 轉換成純陣列
            json_decode(
                json_encode(
                    $response->content[$this->mediaType]
                        ->schema
                        ->getSerializableData()
                ),
                true
            ) :
            [];
    }

    /**
     * Convert associative array to stdClass objects,
     * so JsonSchema\Validator can correctly validate it.
     *
     * @param array $data
     *
     * @return object
     */
    private function toStdClassObject(array $data): object
    {
        return json_decode(json_encode($data), false);
    }
}
