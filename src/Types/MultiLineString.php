<?php

namespace Grimzy\LaravelMysqlSpatial\Types;

use GeoJson\GeoJson;
use GeoJson\Geometry\MultiLineString as GeoJsonMultiLineString;
use Grimzy\LaravelMysqlSpatial\Exceptions\InvalidGeoJsonException;

class MultiLineString extends GeometryCollection implements \Stringable
{
    /**
     * The minimum number of items required to create this collection.
     *
     * @var int
     */
    protected $minimumCollectionItems = 1;

    /**
     * The class of the items in the collection.
     *
     * @var string
     */
    protected $collectionItemType = LineString::class;

    public function getLineStrings()
    {
        return $this->items;
    }

    public function toWKT()
    {
        return sprintf('MULTILINESTRING(%s)', (string) $this);
    }

    public static function fromString($wktArgument, $srid = 0)
    {
        $str = preg_split('/\)\s*,\s*\(/', substr(trim((string) $wktArgument), 1, -1));
        $lineStrings = array_map(fn($data) => LineString::fromString($data), $str);

        return new static($lineStrings, $srid);
    }

    public function __toString(): string
    {
        return implode(',', array_map(fn(LineString $lineString) => sprintf('(%s)', (string) $lineString), $this->getLineStrings()));
    }

    public function offsetSet($offset, $value)
    {
        $this->validateItemType($value);

        parent::offsetSet($offset, $value);
    }

    public static function fromJson($geoJson)
    {
        if (is_string($geoJson)) {
            $geoJson = GeoJson::jsonUnserialize(json_decode($geoJson, null, 512, JSON_THROW_ON_ERROR));
        }

        if (!is_a($geoJson, GeoJsonMultiLineString::class)) {
            throw new InvalidGeoJsonException('Expected '.GeoJsonMultiLineString::class.', got '.$geoJson::class);
        }

        $set = [];
        foreach ($geoJson->getCoordinates() as $coordinates) {
            $points = [];
            foreach ($coordinates as $coordinate) {
                $points[] = new Point($coordinate[1], $coordinate[0]);
            }
            $set[] = new LineString($points);
        }

        return new self($set);
    }

    /**
     * Convert to GeoJson Point that is jsonable to GeoJSON.
     *
     * @return \GeoJson\Geometry\MultiLineString
     */
    public function jsonSerialize()
    {
        $lineStrings = [];

        foreach ($this->items as $lineString) {
            $lineStrings[] = $lineString->jsonSerialize();
        }

        return new GeoJsonMultiLineString($lineStrings);
    }
}
