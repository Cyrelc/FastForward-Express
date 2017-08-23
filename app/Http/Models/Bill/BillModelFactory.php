<?php

	namespace App\Http\Models\Bill;

	use App\Http\Repos;
	use App\Http\Models\Bill;

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

		public function GetCreateModel($req) {
			$model = new BillFormModel();
		    $acctRepo = new Repos\AccountRepo();
		    $driversRepo = new Repos\DriverRepo();
		    $interlinersRepo = new Repos\InterlinerRepo();

		    $model->accounts = $acctRepo->ListAll();
		    $model->drivers = $driversRepo->ListAll();
		    $model->interliners = $interlinersRepo->ListAll();
		    $model->bill = new \App\Bill();

		    $model->pickupAddress = new \App\Address();
		    $model->deliveryAddress = new \App\Address();
		    $model->charge_selection_submission = null;
            $model->bill->date = date("U");
		    $model->pickup_use_submission = "account";
		    $model->delivery_use_submission = "account";
		    $model->use_interliner = 'false';
            $model->payment_types = ['Cash', 'Cheque', 'Visa', 'Mastercard', 'American Express'];
		    
			$model = $this->MergeOld($model, $req);

		    return $model;
		}

		public function GetEditModel($id, $req) {
			$model = new BillFormModel();

			$acctRepo = new Repos\AccountRepo();
			$addrRepo = new Repos\AddressRepo();
			$driversRepo = new Repos\DriverRepo();
			$interlinersRepo = new Repos\InterlinerRepo();
			$billRepo = new Repos\BillRepo();

			$model->bill = $billRepo->GetById($id);
            // $model->bill->date = strtotime($model->bill->date);

			if ($model->bill->charge_account_id == 'null') {
				$model->charge_selection_submission = 'pre-paid';
			} else if ($model->bill->charge_account_id == $model->bill->pickup_account_id) {
            	$model->charge_selection_submission = 'pickup_account';
            } else if ($model->bill->charge_account_id == $model->bill->delivery_account_id) {
            	$model->charge_selection_submission = 'delivery_account';
            } else if ($model->bill->charge_account_id) {
            	$model->charge_selection_submission = 'other_account';
            }

            if (isset($model->bill->pickup_account_id))
            	$model->pickup_use_submission = "account";
            else 
            	$model->pickup_use_submission = "address";

            if (isset($model->bill->delivery_account_id))
            	$model->delivery_use_submission = "account";
            else
            	$model->delivery_use_submission = "address";

            if (isset($model->bill->interliner_id))
            	$model->use_interliner = "true";
            else
            	$model->use_interliner = "false";

            $model->pickupAddress = $addrRepo->GetById($model->bill->pickup_address_id);
            $model->deliveryAddress = $addrRepo->GetById($model->bill->delivery_address_id);
            $model->bill->date = strtotime($model->bill->date);

            if($model->bill->charge_account_id !== null)
                $model->charge_reference_name = $acctRepo->GetById($model->bill->charge_account_id)->custom_field;
            if($model->bill->pickup_account_id !== null)
            	$model->pickup_reference_name = $acctRepo->GetById($model->bill->pickup_account_id)->custom_field;
            if($model->bill->delivery_account_id !== null)
            	$model->delivery_reference_name = $acctRepo->GetById($model->bill->delivery_account_id)->custom_field;

            //to-do add this field to table
            $model->payment_types = ['Cash', 'Cheque', 'Visa', 'Mastercard', 'American Express'];
            $model->payment_type = 'Cheque';

			$model->accounts = $acctRepo->ListAll();
			$model->drivers = $driversRepo->ListAll();
			$model->interliners = $interlinersRepo->ListAll();

			$model = $this->MergeOld($model, $req);

			return $model;
		}

		public function MergeOld($model, $req) {
		    $addrCollector = new \App\Http\Collectors\AddressCollector();
		    $billCollector = new \App\Http\Collectors\BillCollector();

			$model->pickupAddress = $addrCollector->ReMerge($req, $model->pickupAddress, 'pickup');
			$model->deliveryAddress = $addrCollector->ReMerge($req, $model->deliveryAddress, 'delivery');

			$model->bill = $billCollector->ReMerge($req, $model->bill);

			$modelVars = array('charge_selection_submission', 'pickup_use_submission', 'delivery_use_submission', 'use_interliner');

			foreach ($modelVars as $modelVar) {
				if($req->old($modelVar) !== null)
					$model->{$modelVar} = $req->old($modelVar);
			}

			return $model;
		}
	}
?>
