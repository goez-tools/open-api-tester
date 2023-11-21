<?php

namespace Goez\OpenAPI\Tester;

class ValidationResult
{
    public const ERROR_TYPE_SCHEMA = 'schema';
    public const ERROR_TYPE_CODE = 'code';
    public const ERROR_TYPE_PARSE = 'parse';

    private array $schema;
    private string $response;
    private bool $_isValid;
    private array $errors;

    public function __construct(array $schema, string $response, bool $isValid, array $errors)
    {
        $this->schema = $schema;
        $this->response = $response;
        $this->_isValid = $isValid;
        $this->errors = $errors;
    }

    public function isValid(): bool
    {
        return $this->_isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function createDumpFiles(string $name, string $data)
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'open_api_tester_' . $name);
        file_put_contents($tempFilePath, $data);

        return $tempFilePath;
    }

    public function createReport(): array
    {
        $report = [];
        $problemMessages = [];

        $report['is_valid'] = $this->isValid();
        $report['response'] = $this->response;
        $report['schema'] = json_encode(
            $this->schema,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        foreach ($this->errors as $error) {
            switch ($error['type']) {
                case self::ERROR_TYPE_SCHEMA:
                    $problemMessages[] = sprintf(
                        '[Schema] %s (%s): %s',
                        $error['data']['pointer'] ?: '.',
                        $error['data']['constraint'],
                        $error['data']['message']
                    );
                    break;
                case self::ERROR_TYPE_CODE:
                case self::ERROR_TYPE_PARSE:
                    $problemMessages[] = sprintf(
                        '%s',
                        $error['message']
                    );
                    break;
            }
        }

        $report['problems'] = $problemMessages;

        return $report;
    }

    public function renderReport(bool $enableColor = true): string
    {
        if ($enableColor) {
            $RED = "\x1b[31m";
            $GREEN = "\x1b[32m";
            $RESET = "\x1b[0m";
            $BOLD = "\x1b[1m";
        } else {
            $RED = '';
            $GREEN = '';
            $RESET = '';
            $BOLD = '';
        }

        $report = $this->createReport();
        $responseDumpFile = $this->createDumpFiles('response', $report['response']);
        $schemaDumpFile = $this->createDumpFiles('schema', $report['schema']);

        $text = '';

        if ($report['is_valid']) {
            $text .= <<<EOL

            ${BOLD}${GREEN}Validation Passed${RESET}

            ${BOLD}Response:${RESET}
                ${responseDumpFile}

            ${BOLD}Schema:${RESET}
                ${schemaDumpFile}

            EOL;

            return $text;
        }

        $problemMessagesRendered = array_reduce($report['problems'], function (string $carry, string $line) {
            return $carry . '    - ' . $line . PHP_EOL;
        }, '');

        $text .= <<<EOL

        ${BOLD}${RED}Validation Failed${RESET}

        ${BOLD}Problems:${RESET}
        ${problemMessagesRendered}
        ${BOLD}Response:${RESET}
            ${responseDumpFile}

        ${BOLD}Schema:${RESET}
            ${schemaDumpFile}

        EOL;

        return $text;
    }
}
