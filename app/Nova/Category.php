<?php

namespace App\Nova;

use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use MrMonat\Translatable\Translatable;

class Category extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\Category';

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
        'id','name'
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

            Translatable::make('Name')->locales([
                'en' => 'English',
                'se' => 'Sweden',
            ])
                ->singleLine()
                ->sortable()
                ->rules('required', 'max:255'),

//            Text::make('Name', 'name')
//                ->sortable()
//                ->rules('required', 'max:255'),

            Select::make('Color', 'color')->options([
                '#4286f4' => 'Blue',
                '#2ce192' => 'Green',
                '#e0e21b' => 'Yellow',
                '#f4b541' => 'Orange',
                '#f44941' => 'Red',
                '#e24a91' => 'Purple',
                '#3325c6' => 'Dark blue',
                '#6aba77' => 'Dark green',
                '#b71007' => 'Dark red',
                '#aa9400' => 'Dark yellow',
                '#9e1756' => 'Dark purple',
                '#ff7777' => 'Light red',
                '#c87de8' => 'Light purple',
                '#41ccf4' => 'Light blue',
                '#82f780' => 'Light green',
                '#fdff87' => 'Light yellow',
                '#f9c27f' => 'Light orange',
                '#54b21e' => 'Darker green',
                '#19249e' => 'Darker blue',
                '#461689' => 'Darker purple',
                '#115d70' => 'Dark aqua',
                '#7fe9f9' => 'Aqua',
                '#c0f97f' => 'Lemon'
            ])->displayUsingLabels()
                ->hideFromIndex(),


            Image::make('Image', 'image')->disk('category-images')
                ->hideFromIndex(),


            Translatable::make('Description')->locales([
                'en' => 'English',
                'se' => 'Sweden',
            ])
                ->sortable()
                ->rules('required', 'max:255')
                ->hideFromIndex(),

//            Textarea::make('Description')
//                ->sortable()
//                ->rules('required', 'max:255'),
            HasMany::make('Jobs','jobs'),
        ];
    }

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
