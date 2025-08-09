<?php

namespace App\Repositories\Map;

use App\Models\GpMapStreet;
use App\Models\GpMapStreetGeo;
use Illuminate\Support\Facades\DB;

class StreetRepository
{
    public function getItems(){
        $query = GpMapStreet::query();
        $query->select(
            'gp_map_streets.id as id',
            'gp_map_streets.name as name'
        );
        $items = $query->get();
        $ids = $items->pluck('id')->toArray();
        $all_points = $this->getStreetPointsByIds($ids)->toArray();
        $items = $items->map(function ($item) use ($all_points) {
            // Преобразуем модель в массив, добавляя поле points,
            // чтобы избежать предупреждения об undefined property в статическом анализе
            $itemArray = $item->toArray();
            $itemArray['points'] = $all_points[$item->id] ?? [];
            return (object) $itemArray;
        });
        return $items;
    }

    public function getStreetPointsByIds(array $ids = [])
    {
        $query = GpMapStreetGeo::query();
        $query->whereIn('gp_map_street_geos.street_id', $ids);
        $query->select(
            'gp_map_street_geos.id',
            'gp_map_street_geos.street_id',
            'gp_map_street_geos.order',
            'gp_map_street_geos.lat',
            'gp_map_street_geos.lng'
        );
        $query->orderBy('gp_map_street_geos.order');
        $items = $query->get();
        $items = $items->groupBy('street_id');
        $items = $items->map(function ($item) {
            return $item->map(function ($point) {
                return [
                    'id' => $point->id,
                    'order' => $point->order,
                    'lat' => $point->lat,
                    'lng' => $point->lng,
                ];
            });
        });
        return $items;
    }

    public function create(array $data)
    {
        $street = GpMapStreet::create([
            'name' => $data['name'],
        ]);

        if (isset($data['points']) && is_array($data['points'])) {
            foreach ($data['points'] as $point) {
                GpMapStreetGeo::create([
                    'street_id' => $street->id,
                    'order' => $point['order'],
                    'lat' => $point['lat'],
                    'lng' => $point['lng'],
                ]);
            }
        }else{
            return false;
        }

        return true;
    }

    public function update($id, array $data)
    {
        $street = GpMapStreet::find($id);
        if (!$street) {
            return false;
        }

        $street->update([
            'name' => $data['name'] ?? $street->name,
        ]);

        if (isset($data['points']) && is_array($data['points'])) {
            GpMapStreetGeo::where('street_id', $id)->delete();

            foreach ($data['points'] as $point) {
                GpMapStreetGeo::create([
                    'street_id' => $id,
                    'order' => $point['order'],
                    'lat' => $point['lat'],
                    'lng' => $point['lng'],
                ]);
            }
        }else{
            return false;
        }

        return true;
    }

    public function delete($id)
    {
        $street = GpMapStreet::find($id);
        if (!$street) {
            return false;
        }

        // Удаляем все гео-точки улицы
        GpMapStreetGeo::where('street_id', $id)->delete();

        // Удаляем саму улицу
        $street->delete();

        return true;
    }
}
