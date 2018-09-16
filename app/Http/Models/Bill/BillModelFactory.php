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
			$model->bill->pickup_date_scheduled = $model->bill->delivery_date_scheduled = date("U");
		    $model->skip_invoicing = 'false';
		    $model->delivery_types = $selectionsRepo->GetSelectionsByType('delivery_type');
			$model->payment_types = $selectionsRepo->GetSelectionsByType('payment_type');
			$model->view_only = false;
			
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

            $model->pickupAddress = $addrRepo->GetById($model->bill->pickup_address_id);
            $model->deliveryAddress = $addrRepo->GetById($model->bill->delivery_address_id);
			$model->bill->pickup_date_scheduled = strtotime($model->bill->pickup_date_scheduled);
			$model->bill->delivery_date_scheduled = strtotime($model->bill->delivery_date_scheduled);
			$model->bill->pickup_driver_commission *= 100;
			$model->bill->delivery_driver_commission *= 100;
            $model->packages = $packageRepo->GetByBillId($model->bill->bill_id);
			$model->view_only = $billRepo->IsEditable($model->bill);

            $model->delivery_types = $selectionsRepo->GetSelectionsByType('delivery_type');
            $model->payment_types = $selectionsRepo->GetSelectionsByType('payment_type');

			$model->accounts = $acctRepo->ListAll();
			$model->interliners = $interlinersRepo->ListAll();

			return $model;
		}
	}
?>
