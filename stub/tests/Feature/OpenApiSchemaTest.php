<?php

namespace Tests\Feature;

use Goez\OpenAPI\Tester\Request\Request;
use Goez\OpenAPI\Tester\Request\UploadedFile;
use Goez\OpenAPI\Tester\TestCase as TesterTestCase;
use Goez\OpenAPI\Tester\TestSuite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile as IlluminateUploadedFile;
use Tests\TestCase;

class OpenApiSchemaTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * @test
     *
     * @dataProvider endpointProvider
     */
    public function it_should_get_valid_response(TesterTestCase $testCase)
    {
        $this->callHooks($testCase->getSetUpHook());

        // Retrieve the request information for testing
        $exampleRequest = $testCase->getRequest();
        [$body, $files, $server] = $this->extractRequestInfo($exampleRequest);

        // Send a simulated request to Laravel
        $response = $this
            ->call(
                $exampleRequest->getMethod(),
                $exampleRequest->getPath() . '?' . http_build_query($exampleRequest->getQuery()),
                $body,
                [],
                $files,
                $server,
                ''
            );

        // Validate the result
        $result = $testCase->validate(
            $response->getStatusCode(),
            $response->getContent() ?: 'null'
        );

        $this->assertTrue($result->isValid(), $result->renderReport());

        $this->callHooks($testCase->getTearDownHook());
    }

    public static function endpointProvider(): array
    {
        $docPaths = [
            __DIR__ . '/../../docs/api/v1/api.yaml',
        ];

        $endpoints = [];

        foreach ($docPaths as $docPath) {
            $testSuite = TestSuite::loadFromYamlFile($docPath, 'API Schema Test', __DIR__ . '/test_results');
            $warnings = $testSuite->getWarnings();

            if (count($warnings) > 0) {
                // Print out the warnings
                echo implode("\n", $warnings);
            }

            // The result should match the format of PHPUnit's dataProvider:
            // ex: [ 'test case 1' => [case], 'test case 2' => [case], ... ]
            $endpoints += array_map(function ($testCase) {
                return [$testCase];
            }, $testSuite->getTestCases());
        }

        return $endpoints;
    }

    public function extractRequestInfo(Request $request): array
    {
        $files = [];
        $body = [];

        $server = $this->transformHeadersToServerVars($request->getHeader());
        $requestBody = $request->getRequestBody();

        if ($requestBody === null) {
            return [$body, $files, $server];
        }

        $body = collect($requestBody->getStructuredData())
            ->map(function ($item, $name) use (&$files) {
                if (is_a($item, UploadedFile::class)) {
                    // Convert the `UploadedFile` object into the `Illuminate\Http\UploadedFile`
                    $uploadedFile = new IlluminateUploadedFile(
                        $item->getPath(),
                        $item->getClientOriginalName(),
                        $item->getClientMimeType(),
                        null,
                        true
                    );

                    $files[$name] = $uploadedFile;

                    return $uploadedFile;
                }

                return $item;
            })->toArray();

        return [$body, $files, $server];
    }

    private function callHooks($hook)
    {
        if (is_callable($hook)) {
            call_user_func($hook);
        }
    }
}
