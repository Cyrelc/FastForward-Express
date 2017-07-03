<?php

	namespace App\Http\Models\Bill;

	use App\Http\Repos;
	use App\Http\Models\Account;

	class BillModelFactory{

		public function ListAll() {
			$model = new BillsModel();

			try {
				$billsRepo = new Repos\BillRepo();

				$bills = $billsRepo->ListAll();

				$bill_view_models = Array();

				foreach ($bills as $bill){
					$bill_view_model = new BillViewModel();

					$bill_view_model->bill = $bill;

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
		    
		    return $model;
		}

		public function GetEditModel() {

		}

		public function MergeOld() {

		}
	}
?>
