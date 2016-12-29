<?php
	namespace App\Http\Repos;

	use App\Account;

	class AccountRepo {

		public function List() {
			$accounts = Account::All();

			return $accounts;
		}
		
	}
	