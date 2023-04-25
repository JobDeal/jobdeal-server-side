<?php

namespace App\Nova;

use JustRaspberry\MapCoordinates\MapCoordinates;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;

class Job extends Resource
{
    /**
     * The model the resource corresponds to. d
     *
     * @var string
     */
    public static $model = 'App\Job';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name'
    ];





    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),

            Text::make('Name', 'name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Description', 'description')
                ->hideFromIndex()
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Address', 'address')
                ->hideFromIndex()
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Status', 'status')
                ->sortable()
                ->rules('required', 'max:255'),
            Number::make('Price')
                ->step(0.01)
                ->rules('required'),
            BelongsTo::make('User')
                ->searchable()
                ->rules('required'),

            BelongsTo::make('Category')
                ->rules('required'),

            Text::make('Country'),
            DateTime::make('Expire At')->hideFromIndex(),
            MapCoordinates::make('Location String')->zoom(13)->hideFromIndex(),
            Boolean::make('Boost', 'is_boost'),

            Boolean::make('Speedy', 'is_speedy'),

            Boolean::make('Active', 'is_active'),

            Boolean::make('Is offensive', 'is_offensive'),

            HasMany::make('Reports'),

            HasMany::make('Payments'),


        ];
    }

//    $job->location->getLat()
//
//    $job->location->getLng()

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
