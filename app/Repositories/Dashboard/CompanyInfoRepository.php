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
            'gp_companies.balance as balance',
            'gp_companies.credit_balance as credit_balance',
            'gp_companies.agregator_side_balance as agregator_side_balance',
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
