<?php
	namespace App\Http\Repos;

	use App\Account;

	class AccountRepo {

		public function ListAll() {
			$accounts = Account::All();

			return $accounts;
		}
		
	}
	