<?php

namespace Grimzy\LaravelMysqlSpatial\Types;

use GeoJson\GeoJson;
use GeoJson\Geometry\MultiPoint as GeoJsonMultiPoint;
use Grimzy\LaravelMysqlSpatial\Exceptions\InvalidGeoJsonException;

class MultiPoint extends PointCollection implements \Stringable
{
    /**
     * The minimum number of items required to create this collection.
     *
     * @var int
     */
    protected $minimumCollectionItems = 1;

    public function toWKT()
    {
        return sprintf('MULTIPOINT(%s)', (string) $this);
    }

    public static function fromWkt($wkt, $srid = 0)
    {
        $wktArgument = Geometry::getWKTArgument($wkt);

        return static::fromString($wktArgument, $srid);
    }

    public static function fromString($wktArgument, $srid = 0)
    {
        $matches = [];
        preg_match_all('/\(\s*(\d+\s+\d+)\s*\)/', trim((string) $wktArgument), $matches);

        $points = array_map(fn($pair) => Point::fromPair($pair), $matches[1]);

        return new static($points, $srid);
    }

    public function __toString(): string
    {
        return implode(',', array_map(fn(Point $point) => sprintf('(%s)', $point->toPair()), $this->items));
    }

    public static function fromJson($geoJson)
    {
        if (is_string($geoJson)) {
            $geoJson = GeoJson::jsonUnserialize(json_decode($geoJson, null, 512, JSON_THROW_ON_ERROR));
        }

        if (!is_a($geoJson, GeoJsonMultiPoint::class)) {
            throw new InvalidGeoJsonException('Expected '.GeoJsonMultiPoint::class.', got '.$geoJson::class);
        }

        $set = [];
        foreach ($geoJson->getCoordinates() as $coordinate) {
            $set[] = new Point($coordinate[1], $coordinate[0]);
        }

        return new self($set);
    }

    /**
     * Convert to GeoJson MultiPoint that is jsonable to GeoJSON.
     *
     * @return \GeoJson\Geometry\MultiPoint
     */
    public function jsonSerialize()
    {
        $points = [];
        foreach ($this->items as $point) {
            $points[] = $point->jsonSerialize();
        }

        return new GeoJsonMultiPoint($points);
    }
}
