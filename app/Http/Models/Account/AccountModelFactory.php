<?php

	namespace App\Http\Models\Account;

	use App\Http\Repos;
	use App\Http\Models;
	use App\Http\Models\Account;

	class AccountModelFactory {

		public function ListAll() {
            $model = new AccountsModel();
            
			try {
                $acctsRepo = new Repos\AccountRepo();
                $addrRepo = new Repos\AddressRepo();
                $contactRepo = new Repos\ContactRepo();
                $phoneRepo = new Repos\PhoneNumberRepo();

                $accounts = $acctsRepo->ListAll();
            }
            catch(Exception $e) {
			    //TODO: Error-specific friendly messages
                $model->friendlyMessage = 'Sorry, but an error has occurred. Please contact support.';
			    $model->errorMessage = $e;
            }

			return $accounts;
		}

		public function GetById($id) {
            $acctsRepo = new Repos\AccountRepo();
            $addrRepo = new Repos\AddressRepo();

            $model = new AccountViewModel();
            $a = $acctsRepo->GetById($id);
            $model->account = $a;
            // $model->contacts = $a->contacts()->get();

            return $model;
        }

        public function GetCreateModel() {
		    $model = new AccountFormModel();
		    $acctRepo = new Repos\AccountRepo();
            $selectionsRepo = new Repos\SelectionsRepo();
            $ratesheetRepo = new Repos\RatesheetRepo();
            $contactModelFactory = new \App\Http\Models\Partials\ContactModelFactory();

		    $model->accounts = $acctRepo->ListParents();
            $model->contact = $contactModelFactory->GetCreateModel();
            $model->account = new \App\Account();
            $model->deliveryAddress = new \App\Address();
            $model->billingAddress = new \App\Address();
            $model->account->start_date = date("U");
            $model->commissions = [];
            $model->give_commission_1 = false;
            $model->give_commission_2 = false;
            $model->account->send_bills = 1;
            $model->account->send_invoices = 1;
            $model->ratesheets = $ratesheetRepo->ListAllNameAndId();

            $model->invoice_intervals = $selectionsRepo->GetSelectionsByType('invoice_interval');

		    return $model;
        }

        public function GetEditModel($id) {
            $model = new AccountFormModel();

            $acctRepo = new Repos\AccountRepo();
            $activityLogRepo = new Repos\ActivityLogRepo();
            $addressRepo = new Repos\AddressRepo();
            $employeesRepo = new Repos\EmployeeRepo();
            $invoiceRepo = new Repos\InvoiceRepo();
            $paymentRepo = new Repos\PaymentRepo();
            $ratesheetRepo = new Repos\RatesheetRepo();
            $selectionsRepo = new Repos\SelectionsRepo();

            $contactsModelFactory = new Models\Partials\ContactsModelFactory();

            $model->account = $acctRepo->GetById($id);
            $model->account->start_date = strtotime($model->account->start_date);
            $model->invoice_intervals = $selectionsRepo->GetSelectionsByType('invoice_interval');
            $model->deliveryAddress = $addressRepo->GetById($model->account->shipping_address_id);
            $model->billingAddress = $addressRepo->GetById($model->account->billing_address_id);
            $model->ratesheets = $ratesheetRepo->ListAllNameAndId();
            $model->paymentTypes = $paymentRepo->GetPrepaidPaymentTypes();
            $model->accountPaymentType = $paymentRepo->GetPaymentTypeByName('Account');
            // $model->commissions = $dcRepo->ListByAccount($id);
            // $model->give_commission_1 = false;
            // $model->give_commission_2 = false;
            // if ($model->commissions == null)
            //     $model->commissions = [];

            // if (count($model->commissions) > 0)
            //     $model->give_commission_1 = true;

            // if (count($model->commissions) > 1)
            //     $model->give_commission_2 = true;

            //make dates actual dates for client-side formatting
            // for($i = 0; $i < count($model->commissions); $i++)
            //     $model->commissions[$i]->start_date = strtotime($model->commissions[$i]->start_date);

            if (isset($model->account->parent_account_id))
                $model->parentAccount = $acctRepo->GetById($model->account->parent_account_id);

            $model->accounts = $acctRepo->ListParents();
            $model->employees = $employeesRepo->ListAll();
            $model->balance_owing = $invoiceRepo->CalculateAccountBalanceOwing($id);

			$parent_id = $model->account->parent_account_id;

			while(isset($parent_id)) {
				$parent_name = $acctRepo->GetById($parent_id)->name;
				array_push($model->parents, $parent_name);
				$parent_id = $acctRepo->GetById($parent_id)->parent_account_id;
			}

            $model->sort_options = $invoiceRepo->GetSortOrderById($id);

            $model->activity_log = $activityLogRepo->GetAccountActivityLog($model->account->account_id);
			foreach($model->activity_log as $key => $log)
				$model->activity_log[$key]->properties = json_decode($log->properties);
            
            $model->prev_id = $acctRepo->GetPrevActiveById($id);
            $model->next_id = $acctRepo->GetNextActiveById($id);

            return $model;
        }
	}
