<?php

namespace Goez\OpenAPI\Tester\Request;

class UploadedFile
{
    /** @var string */
    private $path;
    /** @var string */
    private $originalName;
    /** @var string */
    private $mimeType;

    /**
     * Constructor
     *
     * @param string $path         Actual file location
     * @param string $originalName Original filename
     * @param string $mimeType     File's Mime Type
     */
    public function __construct(string $path, string $originalName, string $mimeType)
    {
        $this->path = $path;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
    }

    /**
     * Get the original filename before upload (filename on the user's computer)
     *
     * @return string
     */
    public function getClientOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * Get the file's Mime Type
     *
     * @return string
     */
    public function getClientMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get the file path on the server after upload
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
