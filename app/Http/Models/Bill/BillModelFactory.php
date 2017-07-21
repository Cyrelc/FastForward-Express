<?php

	namespace App\Http\Models\Bill;

	use App\Http\Repos;
	use App\Http\Models\Account;

	class BillModelFactory{

		public function ListAll() {
			$model = new BillsModel();

			try {
				$billsRepo = new Repos\BillRepo();
				$accountsRepo = new Repos\AccountRepo();
				$driversRepo = new Repos\DriverRepo();
				$contactsRepo = new Repos\ContactRepo();

				$bills = $billsRepo->ListAll();

				$bill_view_models = Array();

				foreach ($bills as $bill){
					$bill_view_model = new BillViewModel();

					$bill_view_model->bill = $bill;
					$bill_view_model->account = $accountsRepo->GetById($bill->charge_account_id);
					if ($bill_view_model->account === null) {
                        $bill_view_model->account = new \App\Account();
                        $bill_view_model->account->name = "Cash";
                    }

					$bill_view_model->pickup_driver = $driversRepo->GetById($bill->pickup_driver_id);
					$pickup_driver_contact = $contactsRepo->GetById($bill_view_model->pickup_driver->contact_id);
					$bill_view_model->pickup_driver_name = $pickup_driver_contact->first_name . ' ' . $pickup_driver_contact->last_name;

					$bill_view_model->delivery_driver = $driversRepo->GetById($bill->delivery_driver_id);
					$delivery_driver_contact = $contactsRepo->GetById($bill_view_model->delivery_driver->contact_id);
					$bill_view_model->delivery_driver_name = $delivery_driver_contact->first_name . ' ' . $delivery_driver_contact->last_name;

					array_push($bill_view_models, $bill_view_model);
				}

				$model->bills = $bill_view_models;
				$model->success = true;
			}
            catch(Exception $e) {
			    //TODO: Error-specific friendly messages
                $model->friendlyMessage = 'Sorry, but an error has occurred. Please contact support.';
			    $model->errorMessage = $e;
            }

            return $model;
		}

		public function GetById() {

		}

		public function GetCreateModel() {
			$model = new BillFormModel();
		    $acctRepo = new Repos\AccountRepo();
		    $driversRepo = new Repos\DriverRepo();
		    $interlinersRepo = new Repos\InterlinerRepo();

		    $model->accounts = $acctRepo->ListAll();
		    $model->drivers = $driversRepo->ListAll();
		    $model->interliners = $interlinersRepo->ListAll();
		    $model->bill = new \App\Bill();
		    $model->bill->pickup_use_submission = "account";
		    $model->bill->delivery_use_submission = "account";
            $model->payment_types = ['Cash', 'Cheque', 'Visa', 'Mastercard', 'American Express'];
		    
		    return $model;
		}

		public function GetEditModel() {

		}

		public function MergeOld($model, $req) {

		}
	}
?>
