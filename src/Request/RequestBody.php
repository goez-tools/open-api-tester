<?php

namespace Goez\OpenAPI\Tester\Request;

class RequestBody
{
    public const TYPE_RAW = 'raw';
    public const TYPE_JSON = 'application/json';
    public const TYPE_MULTIPART_FORM_DATA = 'multipart/form-data';
    public const TYPE_WWW_FORM_URLENCODED = 'application/x-www-form-urlencoded';

    private string $type;
    private array $structuredData;

    public function __construct(string $type, array $structuredData)
    {
        $this->type = $type;
        $this->structuredData = $structuredData;
    }

    /**
     * Retrieve the request format (represented as a Mime-Type string)
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Retrieve structured data
     *
     * Express the request content in the form of a PHP array.
     * The actual conversion to an HTTP request needs to be implemented externally.
     *
     * @return array
     */
    public function getStructuredData(): array
    {
        return $this->structuredData;
    }
}
