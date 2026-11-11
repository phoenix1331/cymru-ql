<?php

declare(strict_types=1);

namespace App\GraphQL;

/**
 * The example queries preloaded into GraphiQL, read from graphql/examples.
 * A first line of `# headers: {...}` sets the tab's request headers.
 */
final readonly class ExampleQueries
{
    public function __construct(private string $directory) {}

    /**
     * @return list<array{query: string, headers?: string}>
     */
    public function all(): array
    {
        $files = glob(rtrim($this->directory, '/').'/*.graphql') ?: [];
        sort($files);

        return array_map(fn (string $file): array => $this->tab((string) file_get_contents($file)), $files);
    }

    /**
     * @return array{query: string, headers?: string}
     */
    private function tab(string $contents): array
    {
        if (preg_match('/^# headers: (\{.*\})\R/', $contents, $matches) !== 1) {
            return ['query' => $contents];
        }

        return [
            'query' => substr($contents, strlen($matches[0])),
            'headers' => json_encode(json_decode($matches[1], true), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        ];
    }
}
