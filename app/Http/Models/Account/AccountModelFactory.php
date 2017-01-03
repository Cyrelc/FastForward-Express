<?php

	namespace App\Http\Models\Account;

	use App\Http\Repos;
	use App\Http\Models\Account;

	class AccountModelFactory {

		public function ListAll() {
			$model = new AccountsModel();

			try {
                $acctsRepo = new Repos\AccountRepo();
                $addrRepo = new Repos\AddressRepo();

                $accounts = $acctsRepo->ListAll();

                $avms = array();

                foreach ($accounts as $a) {
                    $avm = new Account\AccountViewModel();

                    $avm->account = $a;
                    $addr = $addrRepo->GetById($a->shipping_address_id);
                    $avm->address = $addr->street . ', ' . $addr->city . ', ' . $addr->zip_postal;
                    $avm->contacts = $a->contacts()->get();

                    array_push($avms, $avm);
                }

                $model->accounts = $avms;
                $model->success = true;
            }
            catch(Exception $e) {
			    //TODO: Error-specific friendly messages
                $model->friendlyMessage = 'Sorry, but an error has occurred. Please contact support.';
			    $model->errorMessage = $e;
            }

			return $model;
		}

		public function GetById($id) {
            $acctsRepo = new Repos\AccountRepo();
            $addrRepo = new Repos\AddressRepo();

            $model = new AccountViewModel();
            $a = $acctsRepo->GetById($id);
            $model->account = $a;
            $model->contacts = $a->contacts()->get();

            return $model;
        }
	}
