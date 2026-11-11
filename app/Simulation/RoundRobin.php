<?php

declare(strict_types=1);

namespace App\Simulation;

use InvalidArgumentException;

/**
 * Double round robin schedule using the circle method: every club plays every
 * other club once at home and once away, with home and away alternating as
 * evenly as the format allows.
 */
final class RoundRobin
{
    /**
     * @template T of int|string
     *
     * @param  list<T>  $clubs
     * @return list<list<array{0: T, 1: T}>> rounds of [home, away] pairings
     */
    public static function double(array $clubs): array
    {
        if (count($clubs) < 2 || count($clubs) % 2 !== 0) {
            throw new InvalidArgumentException('A round robin needs an even number of clubs, at least two.');
        }

        $firstHalf = self::single($clubs);
        $secondHalf = array_map(
            fn (array $round): array => array_map(fn (array $pair): array => [$pair[1], $pair[0]], $round),
            $firstHalf,
        );

        return [...$firstHalf, ...$secondHalf];
    }

    /**
     * @template T of int|string
     *
     * @param  list<T>  $clubs
     * @return list<list<array{0: T, 1: T}>>
     */
    private static function single(array $clubs): array
    {
        $count = count($clubs);
        $fixed = $clubs[0];
        $rotating = array_slice($clubs, 1);
        $rounds = [];

        for ($round = 0; $round < $count - 1; $round++) {
            $line = [$fixed, ...$rotating];
            $pairs = [];

            for ($i = 0; $i < $count / 2; $i++) {
                $a = $line[$i];
                $b = $line[$count - 1 - $i];

                // Alternate the fixed club's venue each round, and flip the
                // other pairings by position so venues spread evenly.
                $homeFirst = $i === 0 ? $round % 2 === 0 : ($i + $round) % 2 === 0;
                $pairs[] = $homeFirst ? [$a, $b] : [$b, $a];
            }

            $rounds[] = $pairs;
            $rotating = [array_pop($rotating), ...$rotating];
        }

        return $rounds;
    }
}
