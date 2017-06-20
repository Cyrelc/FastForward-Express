<?php
    namespace App\Http\Repos;

    use App\Commission;

    class CommissionRepo {
        public function ListByAccount($accountId) {
            $commissions = Commission::where('account_id', '=', $accountId)->get();

            return $commissions;
        }

        public function GetById($commissionId) {
            $commission = Commission::where('commission_id', '=', $commissionId)
                ->get();
            return $commission;
        }

        public function Insert($commission) {
            $new = new Commission;

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
