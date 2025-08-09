<?php

namespace App\Http\Controllers\Api\Map;

use App\Http\Controllers\Controller;
use App\Models\GpMapStreet;
use App\Repositories\Map\StreetRepository;
use App\Repositories\Map\DistrictRepository;
use Illuminate\Http\Request;

class MapGeoController extends Controller
{
    protected $streetsRepository;
    protected $districtsRepository;

    public function __construct(StreetRepository $streetsRepository, DistrictRepository $districtsRepository)
    {
        $this->streetsRepository = $streetsRepository;
        $this->districtsRepository = $districtsRepository;
    }


    // streets
    public function streets(){
        $items = $this->streetsRepository->getItems();
        return response()->json($items);
    }

    public function createStreet(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|array|min:2',
            'points.*.order' => 'required|integer',
            'points.*.lat' => 'required|string',
            'points.*.lng' => 'required|string',
        ]);

        $updated = $this->streetsRepository->create($validatedData);
        
        if (!$updated) {
            return response()->json([
                'message' => 'Street not cretated',
            ], 404);
        }

        return response()->json([
            'message' => 'Street created successfully'
        ], 200);
    }

    public function updateStreet(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|array|min:2',
            'points.*.order' => 'required|integer',
            'points.*.lat' => 'required|string',
            'points.*.lng' => 'required|string',
        ]);

        $updated = $this->streetsRepository->update($id, $validatedData);
        
        if (!$updated) {
            return response()->json([
                'message' => 'Street not found or not updated',
            ], 404);
        }

        return response()->json([
            'message' => 'Street updated successfully'
        ], 200);
    }

    // districts
    public function districts(){
        $items = $this->districtsRepository->getItems();
        return response()->json($items);
    }

    public function createDistrict(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|array|min:2',
            'points.*.order' => 'required|integer',
            'points.*.lat' => 'required|string',
            'points.*.lng' => 'required|string',
        ]);

        $updated = $this->districtsRepository->create($validatedData);
        
        if (!$updated) {
            return response()->json([
                'message' => 'District not cretated',
            ], 404);
        }

        return response()->json([
            'message' => 'District created successfully'
        ], 200);
    }

    public function updateDistrict(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|array|min:2',
            'points.*.order' => 'required|integer',
            'points.*.lat' => 'required|string',
            'points.*.lng' => 'required|string',
        ]);

        $updated = $this->districtsRepository->update($id, $validatedData);
        
        if (!$updated) {
            return response()->json([
                'message' => 'District not found or not updated',
            ], 404);
        }

        return response()->json([
            'message' => 'District updated successfully'
        ], 200);
    }

    public function deleteStreet($id)
    {
        $deleted = $this->streetsRepository->delete($id);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'Street not found or not deleted',
            ], 404);
        }

        return response()->json([
            'message' => 'Street deleted successfully'
        ], 200);
    }

    public function deleteDistrict($id)
    {
        $deleted = $this->districtsRepository->delete($id);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'District not found or not deleted',
            ], 404);
        }

        return response()->json([
            'message' => 'District deleted successfully'
        ], 200);
    }
}
