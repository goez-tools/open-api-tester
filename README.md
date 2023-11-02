# Open API Tester

Test API output format using Open API Spec 3 document.

## Branches

- `1.x`: Supports PHP 7.2.
- `2.x`: Supports PHP 7.4 and above.

## Installation

```sh
composer require goez/open-api-tester --dev
```

## Basic Usage

### Define a Test Case

In the operation level of the document, add an `x-api-tests` extended field. Inside it, list one or more test cases in an array format.

The format of a test case is as follows:

```yaml
  - type: request_test_case
    value:

      # Which response this test corresponds to
      response: 200

      # Description of this test (optional)
      description: "Test post user data"

      # Overriding parameter values (optional)
      # Defaults are taken from the parameters field of the operation
      # But if listed here, it overrides the default
      parameters:
        - name: some_parameter
          in: query
          value: some_value

      # Mock object outputs (optional)
      # Used to declare the output content of certain objects; multiple can be declared
      # Only its value will be retrieved in the test, requires additional handling through code
      mocks:
        - type: guzzle
          value: post_200_v1_login

      # Pre-test hook (optional)
      # Called before the test is executed, can be given a string or an array of PHP callable definitions
      setUp: ExampleClass::setUp

      # Post-test hook (optional)
      # Called after the test is executed, can be given a string or an array of PHP callable definitions
      tearDown: ExampleClass::tearDown

      # Request body (optional)
      #
      # Request format. This will affect how the request content should be encoded before sending.
      # It will also generate the corresponding Header.
      # Supports application/json, multipart/form-data, application/x-www-form-urlencoded
      requestBody:
        type: application/json
        data:
          # Directly write structured request content in the format of `key: string`.
          username: "John"
          # Or use `key: { type: file }` to upload a file
          image:
            type: file
            path: ./uploaded_image.jpg
            filename: original_name.jpg
          # Or use `key: { type: array, data }` to represent an array
          array_data:
            type: array
            data:
              some_key: 1
              some_other_key:
                - a
                - b
                - c
```

### Retrieving Test Cases

```php
use Goez\OpenAPI\Tester\TestSuite;
use Goez\OpenAPI\Tester\TestCase;

// Parse the entire API document to get the test suite
$testSuite = TestSuite::loadFromYamlFile(
  __DIR__ . '/docs/api.yaml', // Document location
  'My API Tests',             // Test suite name
  __DIR__ . '/test_files/'    // External file path (Where to find the test files for testing uploads)
);

// Get warnings from the parsing process, which can be echoed to notify developers.

// Currently, warnings are generated when:
//   - No tests are defined for an Operation
$warnings = $testSuite->getWarning();

// Retrieve the test cases
// An array of Goez\OpenAPI\Tester\TestCase
$testCases = $testSuite->getTestCases();

foreach ($testCases as $testName => $testCase) {
  // Use the information contained in `$request` to make an HTTP call to your API or mock the Controller directly.
  $request = $testCase->getRequest();

  // Here, we use the fictitious function `callMyApi` to represent it. You'll need to implement it yourself.
  // If you're using Laravel, more information is provided below.
  $response = callMyApi($request);

  // Here are some commonly used methods:
  //
  // $request->getPath();
  // $request->getMethod();
  // $request->getQuery();
  // $request->getRequestBody();
  // $request->getHeader();

  // Verify if the actual response matches the definition
  $result = $testCase->validate($response->code, $response->body, TestCase::FORMAT_JSON);

  // Check if the validation was successful
  assert($result->isValid(), true);

  // Print out the validation details
  echo $result->renderReport();
}
```

## Using Laravel/Lumen with PHPUnit

### Define a dataProvider

```php
public function endpointProvider()
{
    $docPath = __DIR__ . '/docs/api.yaml';
    $testSuite = \Goez\OpenAPI\Tester\TestSuite::loadFromYamlFile($docPath, 'API Schema Test', __DIR__ . '/test_files/');
    $warnings = $testSuite->getWarnings();

    if (count($warnings) > 0) {
      // Print out the warnings
        echo implode("\n", $warnings);
    }

    // The result should match the format of PHPUnit's dataProvider:
    // ex: [ 'test case 1' => [case], 'test case 2' => [case], ... ]
    return array_map(function ($testCase) {
        return [$testCase];
    }, $testSuite->getTestCases());
}
```

### Organizing the Request Format

Laravel doesn't actually send out HTTP requests, but instead creates a Request object and feeds it directly to the Laravel app for processing.

So, it's important to note the following:

- The request content doesn't need to be compiled into the actual format (JSON, urlencoded, etc); it can be directly inserted into the `$parameters` parameter in the form of a PHP array.
- For file uploads, use the `Illuminate\Http\UploadedFile` object. Both the `$parameters` and `$files` fields should contain a copy.
- You can use the `transformHeadersToServerVars` method built into Laravel TestCase to convert the header part of the request into the `$server` variable.
In this example, we convert the test request into a format that Laravel can easily digest:

```php
public function extractRequestInfo(\Goez\OpenAPI\Tester\Request\Request $request): array
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
            if (is_a($item, \Goez\OpenAPI\Tester\Request\UploadedFile::class)) {
                // Convert the `UploadedFile` object into the `Illuminate\Http\UploadedFile`
                $uploadedFile = new \Illuminate\Http\UploadedFile(
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
```

### Define a PHPUnit Test Case

```php
/**
 * @test
 * @dataProvider endpointProvider
 */
public function it_should_get_valid_response(\Goez\OpenAPI\Tester\TestCase $testCase)
{
    $this->callHooks($testCase->getSetUpHook());

    // Retrieve test request information
    $exampleRequest = $testCase->getRequest();
    [$body, $files, $server] = $this->extractRequestInfo($exampleRequest);

    // Create a mock user
    $user = User::factory()->create();

    // Send a simulated request to Laravel
    $response = $this->actingAs($user)->call(
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
```
