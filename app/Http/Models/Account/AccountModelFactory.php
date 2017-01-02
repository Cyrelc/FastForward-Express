<?php

	namespace App\Http\Models\Account;

	use App\Http\Repos;
	use App\Http\Models\Account;

	class AccountModelFactory {

		public function List() {
			$model = new AccountsModel();

			$acctsRepo = new Repos\AccountRepo();
			$addrRepo = new Repos\AddressRepo();

			$accounts = $acctsRepo->List();

			$avms = array();

			foreach($accounts as $a) {
				$avm = new Account\AccountViewModel();

				$avm->account = $a;
				$addr = $addrRepo->GetById($a->shipping_address_id);
				$avm->address = $addr->street . ', ' . $addr->city . ', ' . $addr->zip_postal;
				//TODO: Get contacts
				$avm->contacts = array();

				array_push($avms, $avm);
			}

			$model->accounts = $avms;

			return $model;
		}
	}