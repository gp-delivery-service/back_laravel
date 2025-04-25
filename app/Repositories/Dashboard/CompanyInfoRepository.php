<?php

namespace App\Repositories\Dashboard;

use App\Models\GpCompany;
use Illuminate\Support\Facades\DB;

class CompanyInfoRepository {
    // получение основной информации о компании
    public function getCompanyInfo($companyId) {
        $company = GpCompany::select(
            'gp_companies.id as id',
            'gp_companies.name as name',
        )
        ->where('id', $companyId)
        ->first();

        if (!$company) {
            return null;
        }

        $result = array();
        $result['company'] = $company;
        
        return $result;
    }
}
