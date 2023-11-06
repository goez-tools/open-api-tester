<?php

namespace Goez\OpenAPI\Tester\Request;

use cebe\openapi\spec\Operation;

class Request
{
    private string $pathPattern;
    private string $method;
    private array $testSpec;
    private Operation $operation;
    private array $parametersDefinition;
    private string $externalsBase;

    private string $path;
    private ?RequestBody $requestBody;
    private array $parametersValues;

    public function __construct(
        string $method,
        string $pathPattern,
        array $testSpec,
        Operation $operation,
        string $externalsBase = ''
    ) {
        $this->pathPattern = $pathPattern;
        $this->method = strtolower($method);
        $this->testSpec = $testSpec;
        $this->operation = $operation;
        $this->parametersDefinition = $this->operation->parameters;
        $this->externalsBase = $externalsBase;

        $this->path = '';
        $this->requestBody = null;
        $this->parametersValues = [];

        $this->initialize();
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        return $this->parametersValues['query'] ?? [];
    }

    /**
     * @return array
     */
    public function getHeader(): array
    {
        return $this->parametersValues['header'] ?? [];
    }

    /**
     * @return RequestBody|null
     */
    public function getRequestBody(): ?RequestBody
    {
        return $this->requestBody;
    }

    /**
     * 取得請求路徑
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    private function initialize(): void
    {
        if (!$this->operation->responses->hasResponse($this->testSpec['response'])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot found response "%s" in [%s] %s.',
                    $this->testSpec['response'],
                    $this->method,
                    $this->pathPattern,
                )
            );
        }

        $this->initExampleValues();
        $this->buildRequestBody();
        $this->buildPath();
    }

    private function buildPath(): void
    {
        $path = $this->pathPattern;
        $pathParams = $this->parametersValues['path'] ?? [];

        foreach ($pathParams as $name => $value) {
            $path = str_replace('{' . $name . '}', $value, $path);
        }

        $this->path = $path;
    }

    private function buildRequestBody(): void
    {
        if (in_array($this->method, ['get', 'head', 'options'])) {
            $this->requestBody = null;

            return;
        }

        if (!isset($this->testSpec['requestBody'])) {
            $this->requestBody = null;

            return;
        }

        $requestType = $this->testSpec['requestBody']['type'];

        switch ($requestType) {
            case RequestBody::TYPE_MULTIPART_FORM_DATA:
                $this->requestBody = new RequestBody(
                    $requestType,
                    $this->buildMultipart($this->testSpec['requestBody']['data'])
                );

                break;
            case RequestBody::TYPE_WWW_FORM_URLENCODED:
            case RequestBody::TYPE_JSON:
                $this->requestBody = new RequestBody(
                    $requestType,
                    $this->testSpec['requestBody']['data']
                );

                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported request body type "%s".', $requestType));
        }
    }

    private function buildMultipart(array $data): array
    {
        $result = [];

        foreach ($data as $name => $value) {
            if (is_string($value)) {
                $result[$name] = $value;
            } elseif ($value['type'] === 'array') {
                $result[$name] = (array) $value['data'];
            } elseif ($value['type'] === 'file') {
                $fullPath = $this->externalsBase . '/' . $value['path'];
                $finfoHandle = finfo_open(FILEINFO_MIME_TYPE);
                $contentType = finfo_file($finfoHandle, $fullPath);
                finfo_close($finfoHandle);
                $result[$name] = new UploadedFile(
                    $fullPath,
                    $value['filename'] ?? basename($fullPath),
                    $contentType,
                );
            }
        }

        return $result;
    }

    private function initExampleValues(): void
    {
        $result = [
            'query' => [],
            'header' => [],
            'cookie' => [],
            'path' => [],
        ];

        foreach ($this->parametersDefinition as $def) {
            $result[$def->in][$def->name] = $def->example;
        }

        foreach ($this->testSpec['parameters'] ?? [] as $def) {
            $result[$def['in']][$def['name']] = $def['value'];
        }

        $this->parametersValues = $result;
    }
}
