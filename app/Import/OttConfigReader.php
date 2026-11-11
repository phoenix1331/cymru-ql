<?php

declare(strict_types=1);

namespace App\Import;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Evaluates the Over the Terraces Cymru config module with Node and returns
 * its exported CLUBS array as plain PHP arrays.
 */
final readonly class OttConfigReader
{
    private const string SCRIPT = <<<'JS'
        const { pathToFileURL } = await import('node:url');
        const module = await import(pathToFileURL(process.argv[1]).href);
        if (!Array.isArray(module.CLUBS)) throw new Error('config does not export a CLUBS array');
        process.stdout.write(JSON.stringify(module.CLUBS));
        JS;

    public function __construct(private string $nodeBinary = 'node') {}

    /**
     * @return list<array<string, mixed>>
     */
    public function read(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Over the Terraces config not found: {$path}");
        }

        $result = Process::run([$this->nodeBinary, '--input-type=module', '-e', self::SCRIPT, $path]);

        if ($result->failed()) {
            throw new RuntimeException('Could not read the Over the Terraces config: '.trim($result->errorOutput()));
        }

        $clubs = json_decode($result->output(), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($clubs) || ! array_is_list($clubs)) {
            throw new RuntimeException('The Over the Terraces config did not produce a list of clubs.');
        }

        /** @var list<array<string, mixed>> $clubs */
        return $clubs;
    }
}
