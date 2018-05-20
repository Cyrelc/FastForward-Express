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
				$employeesRepo = new Repos\EmployeeRepo();
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

					$bill_view_model->pickup_driver = $employeesRepo->GetById($bill->pickup_driver_id);
					$pickup_driver_contact = $contactsRepo->GetById($bill_view_model->pickup_driver->contact_id);
					$bill_view_model->pickup_driver_name = $pickup_driver_contact->first_name . ' ' . $pickup_driver_contact->last_name;

					$bill_view_model->delivery_driver = $employeesRepo->GetById($bill->delivery_driver_id);
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

		public function GetBillAdvancedFiltersModel() {
			$model = new BillAdvancedFiltersModel();

			$driverRepo = new Repos\DriverRepo();
			$model->drivers = $driverRepo->ListAllWithEmployeeAndContact();

			return $model;
		}

		public function GetCreateModel($req) {
			$model = new BillFormModel();
		    $acctRepo = new Repos\AccountRepo();
		    $interlinersRepo = new Repos\InterlinerRepo();
		    $selectionsRepo = new Repos\SelectionsRepo();
		    $employeeRepo = new Repos\EmployeeRepo();
		    $driverRepo = new Repos\DriverRepo();
		    $contactsRepo = new Repos\ContactRepo();

		    $model->accounts = $acctRepo->ListAll();
		    $model->employees = $employeeRepo->ListAllDrivers();
		    foreach ($model->employees as $employee) {
		    	$employee->driver = $driverRepo->GetByEmployeeId($employee->employee_id);
		    	$employee->contact = $contactsRepo->GetById($employee->contact_id);
		    }
		    $model->interliners = $interlinersRepo->ListAll();
		    $model->bill = new \App\Bill();

		    $model->pickupAddress = new \App\Address();
		    $model->deliveryAddress = new \App\Address();
		    $model->charge_selection_submission = null;
            $model->bill->date = date("U");
		    $model->pickup_use_submission = "account";
		    $model->delivery_use_submission = "account";
		    $model->use_interliner = 'false';
		    $model->skip_invoicing = 'false';
		    $model->delivery_types = $selectionsRepo->GetSelectionsByType('delivery_type');
            $model->payment_types = $selectionsRepo->GetSelectionsByType('payment_type');
		    
		    return $model;
		}

		public function GetEditModel($id, $req) {
			$model = new BillFormModel();

			$acctRepo = new Repos\AccountRepo();
			$addrRepo = new Repos\AddressRepo();
			$employeeRepo = new Repos\EmployeeRepo();
			$driverRepo = new Repos\DriverRepo();
			$interlinersRepo = new Repos\InterlinerRepo();
			$billRepo = new Repos\BillRepo();
			$selectionsRepo = new Repos\SelectionsRepo();
			$contactsRepo = new Repos\ContactRepo();
			$packageRepo = new Repos\PackageRepo();

		    $model->employees = $employeeRepo->ListAllDrivers();
		    foreach ($model->employees as $employee) {
		    	$employee->driver = $driverRepo->GetByEmployeeId($employee->employee_id);
		    	$employee->contact = $contactsRepo->GetById($employee->contact_id);
		    }

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

            if (isset($model->bill->skip_invoicing))
            	$model->skip_invoicing = "true";
            else
            	$model->skip_invoicing = "false";

            $model->pickupAddress = $addrRepo->GetById($model->bill->pickup_address_id);
            $model->deliveryAddress = $addrRepo->GetById($model->bill->delivery_address_id);
            $model->bill->date = strtotime($model->bill->date);
            $model->packages = $packageRepo->GetByBillId($model->bill->bill_id);

            if($model->bill->charge_account_id !== null)
                $model->charge_reference_name = $acctRepo->GetById($model->bill->charge_account_id)->custom_field;
            if($model->bill->pickup_account_id !== null)
            	$model->pickup_reference_name = $acctRepo->GetById($model->bill->pickup_account_id)->custom_field;
            if($model->bill->delivery_account_id !== null)
            	$model->delivery_reference_name = $acctRepo->GetById($model->bill->delivery_account_id)->custom_field;

            $model->delivery_types = $selectionsRepo->GetSelectionsByType('delivery_type');
            $model->payment_types = $selectionsRepo->GetSelectionsByType('payment_type');
            $model->payment_type = 'Cheque';

			$model->accounts = $acctRepo->ListAll();
			$model->interliners = $interlinersRepo->ListAll();

			return $model;
		}
	}
?>
