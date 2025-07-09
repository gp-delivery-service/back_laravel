<?php

namespace App\Repositories\Driver;

use App\Models\GpPickup;
use App\Models\GpOrder;
use App\Models\GpPickupOrder;
use Illuminate\Support\Facades\DB;
use App\Constants\GpPickupStatus;
use App\Repositories\Manager\ManagerPickupRepository;
use Illuminate\Validation\ValidationException;

class DriverPickupRepository
{
    public function availablePickups()
    {
        $query = GpPickup::query();
        $query->where('driver_id', null);
        $query->whereIn('status', GpPickupStatus::openForDrivers());
        $pickupIds = $query->pluck('id')->toArray();
        $pickupRepository = new ManagerPickupRepository();
        $pickups = $pickupRepository->getItemsByIds($pickupIds);
        return $pickups;
    }

    public function driverActivePickups($driverId)
    {
        $query = GpPickup::query();
        $query->where('driver_id', $driverId);
        $query->whereIn('status', GpPickupStatus::activeStatuses());
        $pickupIds = $query->pluck('id')->toArray();
        $pickupRepository = new ManagerPickupRepository();
        $pickups = $pickupRepository->getItemsByIds($pickupIds);
        return $pickups;
    }

    public function closedPaginated($driverId)
    {
        $query = GpPickup::query();
        $query->where('driver_id', $driverId);
        $query->whereIn('status', GpPickupStatus::closedStatuses());
        
        $pickupIds = $query->pluck('id')->toArray();
        $pickupRepository = new ManagerPickupRepository();
        $pickups = $pickupRepository->getItemsByIds($pickupIds);
        return $pickups;
    }

    public function getPickupById($pickupId, $driverId)
    {
        $query = GpPickup::query();
        $query->where('id', $pickupId);
        $query->where('driver_id', $driverId);
        $pickup = $query->first();

        if (!$pickup) {
            return null;
        }

        $pickupRepository = new ManagerPickupRepository();
        $pickup = $pickupRepository->getItemById($pickupId);
        if (!$pickup) {
            return null;
        }

        return $pickup;
    }
}
