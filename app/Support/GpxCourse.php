<?php

namespace App\Support;

use InvalidArgumentException;
use SimpleXMLElement;

/**
 * Reads a GPX file into what the course block of an event page needs: the
 * length, the climb and descent, the highest point, a line light enough for a
 * map and an elevation profile.
 *
 * The points of all track segments are read in order; a file without a track
 * falls back to its route points. Elevation is optional: without it the course
 * has a length and a line but no profile.
 */
class GpxCourse
{
    /** The most points the map line keeps. */
    public const TRACK_POINTS = 1000;

    /** The number of samples along the elevation profile. */
    public const PROFILE_POINTS = 200;

    /**
     * Changes in height smaller than this, in metres, are treated as GPS noise
     * when climb and descent are added up; otherwise a flat course would show
     * hundreds of metres of climbing.
     */
    public const ELEVATION_THRESHOLD = 5.0;

    private const EARTH_RADIUS = 6371008.8;

    /**
     * @return array{distance_meters: int, elevation_gain_m: int|null, elevation_loss_m: int|null, highest_point_m: int|null, track: list<array{0: float, 1: float}>, profile: list<array{0: float, 1: int}>|null}
     *
     * @throws InvalidArgumentException when the text is not GPX with at least two points
     */
    public static function parse(string $xml): array
    {
        $points = self::points($xml);

        if (count($points) < 2) {
            throw new InvalidArgumentException('The GPX file has no track.');
        }

        // Distance from the start to every point.
        $along = [0.0];

        for ($i = 1, $count = count($points); $i < $count; $i++) {
            $along[$i] = $along[$i - 1] + self::distance($points[$i - 1], $points[$i]);
        }

        $total = end($along);

        if ($total < 1) {
            throw new InvalidArgumentException('The GPX track has no length.');
        }

        $elevations = array_column($points, 2);
        $hasElevation = count(array_filter($elevations, fn ($elevation) => $elevation !== null)) >= 2;
        [$gain, $loss] = $hasElevation ? self::climb($elevations) : [null, null];

        return [
            'distance_meters' => (int) round($total),
            'elevation_gain_m' => $gain,
            'elevation_loss_m' => $loss,
            'highest_point_m' => $hasElevation ? (int) round(max(array_filter($elevations, fn ($elevation) => $elevation !== null))) : null,
            'track' => self::track($points, $along, $total),
            'profile' => $hasElevation ? self::profile($elevations, $along, $total) : null,
        ];
    }

    /**
     * The points of the file as [lat, lon, elevation|null].
     *
     * @return list<array{0: float, 1: float, 2: float|null}>
     */
    private static function points(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);

        try {
            // No network access and no entity expansion: the file comes from a form.
            $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($document === false || $document->getName() !== 'gpx') {
            throw new InvalidArgumentException('The file is not GPX.');
        }

        $namespace = $document->getNamespaces()[''] ?? null;
        if ($namespace !== null) {
            $document->registerXPathNamespace('g', $namespace);
        }

        $prefix = $namespace !== null ? 'g:' : '';
        $query = fn (string $path) => $document->xpath(str_replace('~', $prefix, $path)) ?: [];
        $nodes = $query('//~trk/~trkseg/~trkpt') ?: $query('//~rte/~rtept');

        $points = [];

        foreach ($nodes as $node) {
            $lat = (string) $node['lat'];
            $lon = (string) $node['lon'];

            if (! is_numeric($lat) || ! is_numeric($lon) || abs((float) $lat) > 90 || abs((float) $lon) > 180) {
                continue;
            }

            $children = $namespace !== null ? $node->children($namespace) : $node->children();
            $elevation = isset($children->ele) && is_numeric((string) $children->ele) ? (float) (string) $children->ele : null;

            $points[] = [(float) $lat, (float) $lon, $elevation];
        }

        return $points;
    }

    /**
     * Metres between two points on the sphere (haversine).
     *
     * @param  array{0: float, 1: float, 2: float|null}  $from
     * @param  array{0: float, 1: float, 2: float|null}  $to
     */
    private static function distance(array $from, array $to): float
    {
        $lat1 = deg2rad($from[0]);
        $lat2 = deg2rad($to[0]);
        $h = sin(($lat2 - $lat1) / 2) ** 2 + cos($lat1) * cos($lat2) * sin(deg2rad($to[1] - $from[1]) / 2) ** 2;

        return 2 * self::EARTH_RADIUS * asin(min(1.0, sqrt($h)));
    }

    /**
     * Climb and descent in metres, counting a change only once it passes the
     * noise threshold from the last height that counted.
     *
     * @param  list<float|null>  $elevations
     * @return array{0: int, 1: int}
     */
    private static function climb(array $elevations): array
    {
        $gain = 0.0;
        $loss = 0.0;
        $anchor = null;

        foreach ($elevations as $elevation) {
            if ($elevation === null) {
                continue;
            }

            if ($anchor === null) {
                $anchor = $elevation;

                continue;
            }

            $change = $elevation - $anchor;

            if (abs($change) >= self::ELEVATION_THRESHOLD) {
                $change > 0 ? $gain += $change : $loss -= $change;
                $anchor = $elevation;
            }
        }

        return [(int) round($gain), (int) round($loss)];
    }

    /**
     * The line for the map: points spread evenly along the course, always with
     * the start and the finish, rounded to about a metre.
     *
     * @param  list<array{0: float, 1: float, 2: float|null}>  $points
     * @param  array<int, float>  $along
     * @return list<array{0: float, 1: float}>
     */
    private static function track(array $points, array $along, float $total): array
    {
        $step = $total / (self::TRACK_POINTS - 1);
        $track = [];
        $next = 0.0;
        $last = count($points) - 1;

        foreach ($points as $i => $point) {
            if ($i === 0 || $i === $last || $along[$i] >= $next) {
                $track[] = [round($point[0], 5), round($point[1], 5)];

                while ($next <= $along[$i]) {
                    $next += $step;
                }
            }
        }

        return $track;
    }

    /**
     * The elevation profile: [kilometres, metres] at even steps along the
     * course, the height interpolated between the nearest points that have one.
     *
     * @param  list<float|null>  $elevations
     * @param  array<int, float>  $along
     * @return list<array{0: float, 1: int}>
     */
    private static function profile(array $elevations, array $along, float $total): array
    {
        $known = [];

        foreach ($elevations as $i => $elevation) {
            if ($elevation !== null) {
                $known[] = [$along[$i], $elevation];
            }
        }

        $profile = [];
        $j = 0;
        $last = count($known) - 1;

        for ($k = 0; $k < self::PROFILE_POINTS; $k++) {
            $at = $total * $k / (self::PROFILE_POINTS - 1);

            while ($j < $last && $known[$j + 1][0] < $at) {
                $j++;
            }

            [$d1, $e1] = $known[$j];
            [$d2, $e2] = $known[min($j + 1, $last)];
            $height = $d2 > $d1 && $at > $d1 ? $e1 + ($e2 - $e1) * min(1, ($at - $d1) / ($d2 - $d1)) : $e1;

            $profile[] = [round($at / 1000, 2), (int) round($height)];
        }

        return $profile;
    }
}
