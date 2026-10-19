<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasSpatialColumns
{
    /**
     * Scope query untuk mengonversi kolom geometri PostGIS ke GeoJSON string saat SELECT.
     * Menggunakan ST_AsGeoJSON(kolom) AS kolom sehingga data langsung siap dikonsumsi Leaflet/MapLibre.
     *
     * @param  Builder  $query
     * @param  array|string  $columns
     * @return Builder
     */
    public function scopeWithGeoJson(Builder $query, array|string $columns = ['geom']): Builder
    {
        $columns = (array) $columns;
        $table = $this->getTable();

        // Ambil semua kolom non-geometri terlebih dahulu jika belum ada select spesifik
        if (empty($query->getQuery()->columns)) {
            $query->select("{$table}.*");
        }

        static $hasPostgis = null;
        if ($hasPostgis === null) {
            try {
                $hasPostgis = !empty(DB::select("SELECT 1 FROM pg_extension WHERE extname = 'postgis'"));
            } catch (\Throwable $e) {
                $hasPostgis = false;
            }
        }

        foreach ($columns as $column) {
            if ($hasPostgis) {
                $query->selectRaw("ST_AsGeoJSON({$table}.{$column}) AS {$column}");
            } else {
                $query->selectRaw("{$table}.{$column} AS {$column}");
            }
        }

        return $query;
    }

    /**
     * Scope query untuk memfilter fitur spasial di dalam Bounding Box (BBOX).
     * Sangat efisien menggunakan operator GiST index PostGIS (&&).
     *
     * @param  Builder  $query
     * @param  string  $column
     * @param  float  $minX  Longitude minimum (Barat)
     * @param  float  $minY  Latitude minimum (Selatan)
     * @param  float  $maxX  Longitude maksimum (Timur)
     * @param  float  $maxY  Latitude maksimum (Utara)
     * @return Builder
     */
    public function scopeWithinBBox(Builder $query, string $column, float $minX, float $minY, float $maxX, float $maxY): Builder
    {
        $table = $this->getTable();
        return $query->whereRaw(
            "{$table}.{$column} && ST_MakeEnvelope(?, ?, ?, ?, 4326)",
            [$minX, $minY, $maxX, $maxY]
        );
    }

    /**
     * Scope query untuk memfilter fitur yang berpotongan (intersect) dengan GeoJSON poligon/titik lain.
     *
     * @param  Builder  $query
     * @param  string  $column
     * @param  array|string  $geoJson
     * @return Builder
     */
    public function scopeIntersectsWith(Builder $query, string $column, array|string $geoJson): Builder
    {
        $table = $this->getTable();
        $jsonString = is_array($geoJson) ? json_encode($geoJson) : $geoJson;

        return $query->whereRaw(
            "ST_Intersects({$table}.{$column}, ST_SetSRID(ST_GeomFromGeoJSON(?), 4326))",
            [$jsonString]
        );
    }

    /**
     * Scope query untuk mencari fitur dalam radius jarak tertentu (dalam meter).
     * Menggunakan kalkulasi PostGIS Geography pada ellipsoid WGS84.
     *
     * @param  Builder  $query
     * @param  string  $column
     * @param  float  $longitude
     * @param  float  $latitude
     * @param  float  $maxDistanceMeters
     * @return Builder
     */
    public function scopeDistanceWithinSphere(
        Builder $query,
        string $column,
        float $longitude,
        float $latitude,
        float $maxDistanceMeters
    ): Builder {
        $table = $this->getTable();
        return $query->whereRaw(
            "ST_DWithin({$table}.{$column}::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)",
            [$longitude, $latitude, $maxDistanceMeters]
        );
    }

    /**
     * Mengonversi model ini menjadi format GeoJSON Feature terstandarisasi (RFC 7946).
     *
     * @param  array  $customProperties
     * @param  string  $geometryColumn
     * @return array
     */
    public function toGeoJsonFeature(array $customProperties = [], string $geometryColumn = 'geom'): array
    {
        $geometry = $this->{$geometryColumn};

        // Jika geometry berupa string JSON (misal hasil ST_AsGeoJSON)
        if (is_string($geometry)) {
            $geometry = json_decode($geometry, true);
        }

        $attributes = $this->attributesToArray();
        unset($attributes[$geometryColumn]);

        $properties = !empty($customProperties) ? $customProperties : $attributes;

        return [
            'type' => 'Feature',
            'id' => $this->getKey(),
            'geometry' => $geometry,
            'properties' => $properties,
        ];
    }
}
