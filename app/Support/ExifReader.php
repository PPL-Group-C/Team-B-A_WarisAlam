<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

/**
 * Reads GPS + timestamp from a photo's EXIF block (needs PHP's exif extension).
 * Works for JPEG; PNG files simply have no EXIF and return nulls.
 */
final class ExifReader
{
    /** @return array{lat: ?float, lng: ?float, taken_at: ?Carbon} */
public static function read(UploadedFile $file): array
{
    $empty = ['lat' => null, 'lng' => null, 'taken_at' => null];

    if (! function_exists('exif_read_data')) {
        return $empty;
    }

    $exif = @exif_read_data($file->getRealPath());

    if (! $exif) {
        return $empty;
    }

    $hasGps = ! empty($exif['GPSLatitude']) && ! empty($exif['GPSLongitude']);

    $lat = $hasGps ? self::toDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N') : null;
    $lng = $hasGps ? self::toDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E') : null;

    // 0,0 means the phone wrote empty GPS tags (no satellite fix): treat as "no GPS".
    if ($lat === null || $lng === null || ($lat == 0.0 && $lng == 0.0)) {
        $lat = $lng = null;
    }

    return [
        'lat' => $lat,
        'lng' => $lng,
        'taken_at' => rescue(fn () => isset($exif['DateTimeOriginal'])
            ? Carbon::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal'])
            : null, null, false),
    ];
}
    public static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $rad = M_PI / 180;
        $dLat = ($lat2 - $lat1) * $rad;
        $dLng = ($lng2 - $lng1) * $rad;
        $h = sin($dLat / 2) ** 2 + cos($lat1 * $rad) * cos($lat2 * $rad) * sin($dLng / 2) ** 2;

        return 12742000 * asin(sqrt($h));
    }

    private static function toDecimal(array $dms, string $ref): float
    {
        [$d, $m, $s] = array_map([self::class, 'fraction'], $dms);
        $decimal = $d + $m / 60 + $s / 3600;

        return in_array($ref, ['S', 'W'], true) ? -$decimal : $decimal;
    }

    private static function fraction(string $value): float
    {
        if (! str_contains($value, '/')) {
            return (float) $value;
        }

        [$num, $den] = explode('/', $value);

        return (float) $den !== 0.0 ? (float) $num / (float) $den : 0.0;
    }
}
