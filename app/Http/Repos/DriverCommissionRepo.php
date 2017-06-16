<?php
    namespace App\Http\Repos;

    use App\DriverCommission;

    class DriverCommissionRepo {
        public function ListByAccount($accountId) {
            $commissions = DriverCommission::where('account_id', '=', $accountId)->get();

            return $commissions;
        }
    }
