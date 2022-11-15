<?php

namespace Whitecube\NovaFlexibleContent\Value;

use Illuminate\Support\Collection;
use Whitecube\NovaFlexibleContent\Layouts\Collection as LayoutsCollection;
use Whitecube\NovaFlexibleContent\Layouts\Layout;

class Resolver implements ResolverInterface
{

    /**
     * Set the field's value
     *
     * @param  mixed  $model
     * @param  string $attribute
     * @param  Illuminate\Support\Collection $groups
     * @return string
     */
    public function set($model, $attribute, $groups)
    {
        return $model->$attribute = $groups->map(function($group) {
            return [
                'layout' => $group->name(),
                'key' => $group->key(),
                'attributes' => $group->getAttributes()
            ];
        });
    }

    /**
     * get the field's value
     *
     * @param  mixed  $resource
     * @param  string $attribute
     * @param  Whitecube\NovaFlexibleContent\Layouts\Collection $layouts
     * @return Illuminate\Support\Collection
     */
    public function get($resource, $attribute, $layouts)
    {
        $value = $this->extractValueFromResource($resource, $attribute);

        return collect($value)->map(function($item) use ($layouts) {
            $layout = $layouts->find($item instanceof Layout ? $item->name() : $item->layout);

            if(!$layout) return;

            $key = $item instanceof Layout ? $item->key() : $item->key;
            $attributes = $item instanceof Layout ? $item->getAttributes() : (array) $item->attributes;

            return $layout->duplicateAndHydrate($key, $attributes);
        })->filter()->values();
    }

    /**
     * Find the attribute's value in the given resource
     *
     * @param  mixed  $resource
     * @param  string $attribute
     * @return array
     */
    protected function extractValueFromResource($resource, $attribute)
    {
        $value = data_get($resource, str_replace('->', '.', $attribute)) ?? [];

        if ($value instanceof LayoutsCollection) {
            $value = $value->all();
        } elseif ($value instanceof Collection) {
            $value = $value->toArray();
        } else if (is_string($value)) {
            $value = json_decode($value) ?? [];
        }

        // Fail silently in case data is invalid
        if (!is_array($value)) return [];

        return array_map(function($item) {
            return is_array($item) ? (object) $item : $item;
        }, $value);
    }

}
