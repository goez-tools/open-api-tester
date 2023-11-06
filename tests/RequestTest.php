<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\ReferenceContext;
use Goez\OpenAPI\Tester\Request\Request;
use Goez\OpenAPI\Tester\Request\UploadedFile;

class RequestTest extends PHPUnitTestCase
{
    public function setup(): void
    {
        $this->docPath = realpath(__DIR__ . '/test_subjects/request/api.yaml');
        $this->docBasePath = dirname(realpath($this->docPath));

        $this->docRoot = Reader::readFromYamlFile(
            $this->docPath,
            OpenAPI::class,
            ReferenceContext::RESOLVE_MODE_ALL
        );
    }

    public function test_get_query()
    {
        $pathName = '/v1/users/{userId}';
        $operation = $this->docRoot->paths[$pathName]->get;

        $request = new Request(
            'get',
            $pathName,
            [
                'response' => 200,
            ],
            $operation
        );

        $this->assertEquals(['some_query' => 123], $request->getQuery());
    }

    public function test_get_method()
    {
        $pathName = '/v1/users/{userId}';
        $operation = $this->docRoot->paths[$pathName]->get;

        $request = new Request(
            'get',
            $pathName,
            [
                'response' => 200,
            ],
            $operation
        );

        $this->assertEquals('get', $request->getMethod());
    }

    public function test_get_query_with_override()
    {
        $pathName = '/v1/users/{userId}';
        $operation = $this->docRoot->paths[$pathName]->get;

        $request = new Request(
            'get',
            $pathName,
            [
                'response' => 200,
                'parameters' => [
                    [
                        'name' => 'some_query',
                        'in' => 'query',
                        'value' => 456,
                    ],
                    [
                        'name' => 'other_query',
                        'in' => 'query',
                        'value' => 999,
                    ],
                ],
            ],
            $operation
        );

        $this->assertEquals([
            'some_query' => 456,
            'other_query' => 999,
        ], $request->getQuery());
    }

    public function test_get_header()
    {
        $pathName = '/v1/users/{userId}';
        $operation = $this->docRoot->paths[$pathName]->get;

        $request = new Request(
            'get',
            $pathName,
            [
                'response' => 200,
            ],
            $operation
        );

        $this->assertEquals(['x-some-header' => 123], $request->getHeader());
    }

    public function test_get_header_with_override()
    {
        $pathName = '/v1/users/{userId}';
        $operation = $this->docRoot->paths[$pathName]->get;

        $request = new Request(
            'get',
            $pathName,
            [
                'response' => 200,
                'parameters' => [
                    [
                        'name' => 'x-some-header',
                        'in' => 'header',
                        'value' => 456,
                    ],
                    [
                        'name' => 'x-other-header',
                        'in' => 'header',
                        'value' => 999,
                    ],
                ],
            ],
            $operation
        );

        $this->assertEquals([
            'x-some-header' => 456,
            'x-other-header' => 999,
        ], $request->getHeader());
    }

    public function test_throw_exception_when_response_not_found()
    {
        $pathName = '/v1/posts';
        $operation = $this->docRoot->paths[$pathName]->get;

        // -----

        $this->expectException(InvalidArgumentException::class);

        $request = new Request(
            'get',
            $pathName,
            [
                // 204 does not exist
                'response' => 204,
            ],
            $operation
        );
    }

    public function test_request_body_should_be_null_on_method_get()
    {
        $pathName = '/v1/posts';
        $operation = $this->docRoot->paths[$pathName]->get;

        // -----

        foreach (['get', 'head', 'options'] as $method) {
            $request = new Request(
                $method,
                $pathName,
                ['response' => 200],
                $operation
            );

            $this->assertNull($request->getRequestBody());
        }
    }

    public function test_get_request_body_json()
    {
        $pathName = '/v1/posts';
        $operation = $this->docRoot->paths[$pathName]->post;

        // -----

        $request = new Request(
            'post',
            $pathName,
            [
                'response' => 201,
                'requestBody' => [
                    'type' => 'application/json',
                    'data' => [
                        'title' => 'Hello World',
                        'content' => 'My first blog',
                    ],
                ],
            ],
            $operation
        );

        $body = $request->getRequestBody();

        $this->assertEquals('application/json', $body->getType());
        $this->assertEquals([
            'title' => 'Hello World',
            'content' => 'My first blog',
        ], $body->getStructuredData());
    }

    public function test_get_request_body_urlencoded()
    {
        $pathName = '/v1/posts_urlencoded';
        $operation = $this->docRoot->paths[$pathName]->post;

        // -----

        $request = new Request(
            'post',
            $pathName,
            [
                'response' => 201,
                'requestBody' => [
                    'type' => 'application/x-www-form-urlencoded',
                    'data' => [
                        'title' => 'Hello World',
                        'content' => 'My first blog',
                    ],
                ],
            ],
            $operation
        );

        $body = $request->getRequestBody();

        $this->assertEquals('application/x-www-form-urlencoded', $body->getType());
        $this->assertEquals([
            'title' => 'Hello World',
            'content' => 'My first blog',
        ], $body->getStructuredData());
    }

    public function test_get_request_body_multipart()
    {
        $pathName = '/v1/posts_multipart';
        $operation = $this->docRoot->paths[$pathName]->post;
        $externalBasePath = $this->docBasePath;

        // -----

        $request = new Request(
            'post',
            $pathName,
            [
                'response' => 201,
                'requestBody' => [
                    'type' => 'multipart/form-data',
                    'data' => [
                        'title' => 'Hello World',
                        'content' => 'My first blog',
                        'image' => [
                            'type' => 'file',
                            'path' => './uploaded_image.jpg',
                            'filename' => 'original_name.jpg',
                        ],
                        'array_data' => [
                            'type' => 'array',
                            'data' => [
                                'abc' => 'hello',
                                'def' => [
                                    'unit' => 'abc',
                                    'test' => 'def',
                                ],
                                'ghi' => 'testing',
                                'jkl' => ['a', 'b', 'c'],
                            ],
                        ],
                    ],
                ],
            ],
            $operation,
            $externalBasePath
        );

        $body = $request->getRequestBody();
        $data = $body->getStructuredData();

        $this->assertEquals('multipart/form-data', $body->getType());
        $this->assertEquals('Hello World', $data['title']);
        $this->assertEquals('My first blog', $data['content']);
        $this->assertInstanceOf(UploadedFile::class, $data['image']);
        $this->assertEquals($externalBasePath . '/./uploaded_image.jpg', $data['image']->getPath());
        $this->assertEquals('image/jpeg', $data['image']->getClientMimeType());
        $this->assertEquals('original_name.jpg', $data['image']->getClientOriginalName());
        $this->assertEquals([
            'abc' => 'hello',
            'def' => [
                'unit' => 'abc',
                'test' => 'def',
            ],
            'ghi' => 'testing',
            'jkl' => ['a', 'b', 'c'],
        ], $data['array_data']);
    }

    public function test_get_request_body_unsupported_type()
    {
        $pathName = '/v1/posts';
        $operation = $this->docRoot->paths[$pathName]->post;

        // -----

        $this->expectException(\InvalidArgumentException::class);

        $request = new Request(
            'post_multipart_array',
            $pathName,
            [
                'response' => 201,
                'requestBody' => [
                    'type' => 'application/x-what-is-this',
                    'data' => [
                        'title' => 'Hello World',
                        'content' => 'My first blog',
                    ],
                ],
            ],
            $operation
        );
    }

    public function test_no_request_body_defined()
    {
        $pathName = '/v1/posts';
        $operation = $this->docRoot->paths[$pathName]->post;

        // -----

        $request = new Request(
            'post_json',
            $pathName,
            [
                'response' => 201,
            ],
            $operation
        );

        $this->assertNull($request->getRequestBody());
    }
}
