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

                $avms = array();

                foreach ($accounts as $a) {
                    $avm = new Account\AccountViewModel();

                    $avm->account = $a;
                    $addr = $addrRepo->GetById($a->shipping_address_id);
                    $avm->shippingAddress = $addr->street . ', ' . $addr->city . ', ' . $addr->zip_postal;
                    if(isset($avm->account->billing_address_id)) {
                        $addr = $addrRepo->GetById($a->billing_address_id);
                        $avm->billingAddress = $addr->street . ', ' . $addr->city . ', ' . $addr->zip_postal;
                    }
                    $avm->contacts = $a->contacts()->get();

                    if (isset($avm->account->parent_account_id))
                        $avm->account->parent_account = $acctsRepo->GetById($avm->account->parent_account_id)->name;

                    $primaryContact = $contactRepo->GetById($acctsRepo->GetAccountPrimaryContactId($a->account_id)->contact_id);
                    $avm->primaryContact = $primaryContact->first_name . ' ' . $primaryContact->last_name;

                    $avm->primaryPhone = $phoneRepo->GetContactPrimaryPhone($primaryContact->contact_id)->phone_number;

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

        public function GetCreateModel($request) {
		    $model = new AccountFormModel();
		    $acctRepo = new Repos\AccountRepo();
		    $driversRepo = new Repos\DriverRepo();

		    $model->accounts = $acctRepo->ListParents();
		    $model->drivers = $driversRepo->ListAll();
            $model->account = new \App\Account();
            $model->deliveryAddress = new \App\Address();
            $model->billingAddress = new \App\Address();
            $model->account->start_date = date("U");
            $model->commissions = [];
            $model->give_commission_1 = false;
            $model->give_commission_2 = false;

            $model->invoice_intervals = [
                "weekly",
                "monthly"
            ];

            $model = $this->MergeOld($model, $request);
		    return $model;
        }

        public function GetEditModel($id, $request) {
            $model = new AccountFormModel();
            $model->invoice_intervals = [
                "weekly",
                "monthly"
            ];

            $acctRepo = new Repos\AccountRepo();
            $driversRepo = new Repos\DriverRepo();
            $addRepo = new Repos\AddressRepo();
            $dcRepo = new Repos\CommissionRepo();
            $contactsModelFactory = new Models\Partials\ContactsModelFactory();

            $model->account = $acctRepo->GetById($id);
            $model->account->start_date = strtotime($model->account->start_date);

            $model->deliveryAddress = $addRepo->GetById($model->account->shipping_address_id);
            $model->billingAddress = $addRepo->GetById($model->account->billing_address_id);
            $model->commissions = $dcRepo->ListByAccount($id);
            $model->give_commission_1 = false;
            $model->give_commission_2 = false;
            if ($model->commissions == null)
                $model->commissions = [];

            if (count($model->commissions) > 0)
                $model->give_commission_1 = true;

            if (count($model->commissions) > 1)
                $model->give_commission_2 = true;

            //make dates actual dates for client-side formatting
            for($i = 0; $i < count($model->commissions); $i++)
                $model->commissions[$i]->start_date = strtotime($model->commissions[$i]->start_date);

            if (isset($model->account->parent_account_id))
                $model->parentAccount = $acctRepo->GetById($model->account->parent_account_id);

            $model->accounts = $acctRepo->ListParents();
            $model->drivers = $driversRepo->ListAll();

            $model->account->contacts = $contactsModelFactory->GetEditModel($acctRepo->ListAccountContacts($id), false);

            $model = $this->MergeOld($model, $request);
            return $model;
        }

        public function MergeOld($model, $req) {
		    $acctCollector = new \App\Http\Collectors\AccountCollector();
		    $addrCollector = new \App\Http\Collectors\AddressCollector();
		    $contactCollector = new \App\Http\Collectors\ContactCollector();
            $commissionCollector = new \App\Http\Collectors\CommissionCollector();

		    //Account
		    $model = $acctCollector->Remerge($req, $model);

		    //Delivery Address
            $model->deliveryAddress = $addrCollector->Remerge($req, $model->deliveryAddress, 'delivery');

            //Billing address
            if ($req->old("billing-street") !== null || $req->old("billing-street2") !== null || $req->old("billing-city") !== null ||
                $req->old("billing-zip-postal") !== null || $req->old("billing-state-province") !== null || $req->old("billing-country") !== null)
                $model->billingAddress = new \App\Address();
            $model->billingAddress = $addrCollector->Remerge($req, $model->billingAddress, 'billing');

            //Contacts
            $model->account->contacts = $contactCollector->Remerge($req, $model->account->contacts, false);

            //Commissions
            $model = $commissionCollector->Remerge($req, $model);

            return $model;
        }
	}
