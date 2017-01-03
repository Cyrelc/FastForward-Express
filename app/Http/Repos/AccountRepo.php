<?php
	namespace App\Http\Repos;

	use App\Account;

	class AccountRepo {

		public function ListAll() {
			$accounts = Account::All();

			return $accounts;
		}


		public function GetById($id) {
		    $account = Account::where('account_id', '=', $id)->first();

		    return $account;
        }

        public function Insert($acct, $primaryId, $secondaryId) {
		    $new = new Account;

		    $new = $new->create($acct);

		    $new->contacts()->attach($primaryId);
		    $new->contacts()->attach($secondaryId);

		    return $new;
        }

        public function Edit($acct) {
            $old = GetById($acct['account_id']);

            $old->account_number = $acct['acct_number'];
            $old->invoice_interval = $acct['invoice_interval'];
            $old->stripe_id = $acct['stripe_id'];
            $old->name = $acct['name'];
            $old->send_bills = $acct['send_bills'];

            $old->save();
        }
	}
