<?php

declare(strict_types=1);

namespace App\Data;

use RuntimeException;

/**
 * Reads and writes the committed JSON seed data under database/data.
 */
final readonly class DataFiles
{
    public const string LEAGUES = 'leagues.json';

    public const string SEASONS = 'seasons.json';

    public const string GROUNDS = 'grounds.json';

    public const string CLUBS = 'clubs.json';

    public const string CLUB_SEASONS = 'club_seasons.json';

    public const string DISTANCES = 'distances.json';

    public const string OVERRIDES = 'overrides.json';

    public function __construct(private string $directory) {}

    public function path(string $file): string
    {
        return rtrim($this->directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;
    }

    public function exists(string $file): bool
    {
        return is_file($this->path($file));
    }

    public function currentSeason(): SeasonData
    {
        foreach ($this->seasons() as $season) {
            if ($season->isCurrent) {
                return $season;
            }
        }

        throw new RuntimeException('No season is marked as current in '.self::SEASONS.'.');
    }

    /**
     * @return array<array-key, mixed>
     */
    public function read(string $file): array
    {
        $path = $this->path($file);

        if (! is_file($path)) {
            throw new RuntimeException("Data file not found: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException("Data file does not contain a JSON array or object: {$path}");
        }

        return $decoded;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public function write(string $file, array $data): void
    {
        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        );

        file_put_contents($this->path($file), $json.PHP_EOL);
    }

    /**
     * @return list<LeagueData>
     */
    public function leagues(): array
    {
        return array_map(LeagueData::fromArray(...), $this->rows(self::LEAGUES));
    }

    /**
     * @return list<SeasonData>
     */
    public function seasons(): array
    {
        return array_map(SeasonData::fromArray(...), $this->rows(self::SEASONS));
    }

    /**
     * @return list<GroundData>
     */
    public function grounds(): array
    {
        return array_map(GroundData::fromArray(...), $this->rows(self::GROUNDS));
    }

    /**
     * @return list<ClubData>
     */
    public function clubs(): array
    {
        return array_map(ClubData::fromArray(...), $this->rows(self::CLUBS));
    }

    /**
     * @return list<ClubSeasonData>
     */
    public function clubSeasons(): array
    {
        return array_map(ClubSeasonData::fromArray(...), $this->rows(self::CLUB_SEASONS));
    }

    public function distances(): DistanceMatrixData
    {
        /** @var array<string, mixed> $data */
        $data = $this->read(self::DISTANCES);

        return DistanceMatrixData::fromArray($data);
    }

    /**
     * @param  list<LeagueData|SeasonData|GroundData|ClubData|ClubSeasonData>  $items
     */
    public function writeRows(string $file, array $items): void
    {
        $this->write($file, array_map(fn (LeagueData|SeasonData|GroundData|ClubData|ClubSeasonData $item): array => $item->toArray(), $items));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(string $file): array
    {
        $rows = [];

        foreach ($this->read($file) as $row) {
            if (! is_array($row)) {
                throw new RuntimeException("Data file contains a non-object row: {$this->path($file)}");
            }

            /** @var array<string, mixed> $row */
            $rows[] = $row;
        }

        return $rows;
    }
}
