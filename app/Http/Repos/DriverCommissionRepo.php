<?php
    namespace App\Http\Repos;

    use App\DriverCommission;

    class DriverCommissionRepo {
        public function ListByAccount($accountId) {
            $commissions = DriverCommission::where('account_id', '=', $accountId)->get();

            return $commissions;
        }

        public function GetById($commissionId) {
            $commission = DriverCommission::where('commission_id', '=', $commissionId)
                ->get();
            return $commission;
        }

        public function Insert($commission) {
            $new = new DriverCommission;

            $new = $new->create($commission);

            return $new;
        }

        public function Update($commission) {
            $old = $this->GetById($commission['commission_id']);

            $old->account_id = $commission['account_id'];
            $old->driver_id = $commission['driver_id'];
            $old->commission = $commission['commission'];
            $old->depreciation_amount = $commission['depreciation_amount'];
            $old->years = $commission['years'];
            $old->start_date = $commission['start_date'];

            $old->save();
        }

        public function Delete($commissionId) {
            $commission = $this->GetById($commissionId);
            $commission->delete();
        }
    }
