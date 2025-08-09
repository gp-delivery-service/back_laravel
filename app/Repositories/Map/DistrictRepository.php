<?php

namespace App\Repositories\Map;

use App\Models\GpMapDistrict;
use App\Models\GpMapDistrictGeo;
use Illuminate\Support\Facades\DB;

class DistrictRepository
{
    public function getItems(){
        $query = GpMapDistrict::query();
        $query->select(
            'gp_map_districts.id as id',
            'gp_map_districts.name as name'
        );
        $query->orderBy('gp_map_districts.name');
        $items = $query->get();
        $ids = $items->pluck('id')->toArray();
        $all_points = $this->getStreetPointsByIds($ids)->toArray();
        $items->map(function ($item) use ($all_points) {
            $item->points = $all_points[$item->id] ?? null;
            return $item;
        });
        return $items;
    }

    public function getStreetPointsByIds(array $ids = [])
    {
        $query = GpMapDistrictGeo::query();
        $query->whereIn('gp_map_district_geos.district_id', $ids);
        $query->select(
            'gp_map_district_geos.id',
            'gp_map_district_geos.district_id',
            'gp_map_district_geos.order',
            'gp_map_district_geos.lat',
            'gp_map_district_geos.lng'
        );
        $query->orderBy('gp_map_district_geos.order');
        $items = $query->get();
        $items = $items->groupBy('district_id');
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
        $street = GpMapDistrict::create([
            'name' => $data['name'],
        ]);

        if (isset($data['points']) && is_array($data['points'])) {
            foreach ($data['points'] as $point) {
                GpMapDistrictGeo::create([
                    'district_id' => $street->id,
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
        $street = GpMapDistrict::find($id);
        if (!$street) {
            return false;
        }

        $street->update([
            'name' => $data['name'] ?? $street->name,
        ]);

        if (isset($data['points']) && is_array($data['points'])) {
            GpMapDistrictGeo::where('district_id', $id)->delete();

            foreach ($data['points'] as $point) {
                GpMapDistrictGeo::create([
                    'district_id' => $id,
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
        $district = GpMapDistrict::find($id);
        if (!$district) {
            return false;
        }

        // Удаляем все гео-точки района
        GpMapDistrictGeo::where('district_id', $id)->delete();
        
        // Удаляем сам район
        $district->delete();

        return true;
    }
}