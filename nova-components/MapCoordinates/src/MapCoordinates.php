<?php

namespace JustRaspberry\MapCoordinates;

use GeoJson\Geometry\Point;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Laravel\Nova\Fields\Field;

class MapCoordinates extends Field
{
    use SpatialTrait;
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'map_coordinates';
    protected $spatialFields = [
        'location',
];

    public function initLocation($latitude, $longitude){
        return $this->withMeta([
            'lat' => $latitude,
            'lng' => $longitude,
        ]);
    }

    public function zoom($zoom)
    {
        return $this->withMeta([
            'zoom' => $zoom
        ]);
    }

}
