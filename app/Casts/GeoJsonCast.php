<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GeoJsonCast implements CastsAttributes
{
    /**
     * Cast the given value from database storage to PHP array (GeoJSON geometry).
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<string, mixed>  $attributes
     * @return array|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        // Jika value sudah berupa array GeoJSON
        if (is_array($value)) {
            return $value;
        }

        // Jika value adalah string JSON (misal hasil ST_AsGeoJSON)
        if (is_string($value)) {
            $trimmed = trim($value);
            if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['type'])) {
                    return $decoded;
                }
            }

            // Jika formatnya WKB Hexadesimal (PostGIS raw query)
            // Lakukan decoding cepat jika diperlukan
            return [
                'type' => 'GeometryHex',
                'hex' => $value,
            ];
        }

        return null;
    }

    /**
     * Prepare the given value for storage in PostGIS.
     * Menerima array GeoJSON, string GeoJSON, atau Expression DB raw.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<string, mixed>  $attributes
     * @return mixed
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        static $hasPostgis = null;
        if ($hasPostgis === null) {
            try {
                $hasPostgis = !empty(DB::select("SELECT 1 FROM pg_extension WHERE extname = 'postgis'"));
            } catch (\Throwable $e) {
                $hasPostgis = false;
            }
        }

        // Jika modul PostGIS belum terpasang di PostgreSQL server, simpan sebagai GeoJSON string / JSONB
        if (!$hasPostgis) {
            return is_array($value) ? json_encode($value) : $value;
        }

        // Jika value adalah array GeoJSON
        if (is_array($value)) {
            $json = json_encode($value);
            return DB::raw("ST_SetSRID(ST_GeomFromGeoJSON(" . DB::getPdo()->quote($json) . "), 4326)");
        }

        // Jika value adalah string JSON GeoJSON
        if (is_string($value) && str_starts_with(trim($value), '{')) {
            return DB::raw("ST_SetSRID(ST_GeomFromGeoJSON(" . DB::getPdo()->quote($value) . "), 4326)");
        }

        // Jika sudah berupa DB::raw expression atau WKT
        return $value;
    }
}
