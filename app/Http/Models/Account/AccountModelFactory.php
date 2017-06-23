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

                    if (isset($avm->account->parent_account_id))
                        $avm->account->parent_account = $acctsRepo->GetById($avm->account->parent_account_id)->name;

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
            $pnRepo = new Repos\PhoneNumberRepo();
            $emRepo = new Repos\EmailAddressRepo();
            $addRepo = new Repos\AddressRepo();
            $dcRepo = new Repos\CommissionRepo();

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

            //Find the primary contact, remove primary contact from list of all contacts
            $accountContacts = $acctRepo->ListAccountContacts($id);
            $primary = -1;

            //Find primary contact
            foreach($accountContacts as $ac)
            {
                if ($ac->is_primary == 1)
                    $primary = $ac->contact_id;
            }

            for($i = 0; $i < count($model->account->contacts); $i++) {
                if ($model->account->contacts[$i]->contact_id == $primary)
                    $model->account->contacts[$i]->is_primary = true;
                else
                    $model->account->contacts[$i]->is_primary = false;

                $primaryPhones = $pnRepo->ListByContactId($model->account->contacts[$i]->contact_id);
                foreach($primaryPhones as $pp){
                    if ($pp["is_primary"])
                        $model->account->contacts[$i]->primaryPhone = $pp;
                    else
                        $model->account->contacts[$i]->secondaryPhone = $pp;
                }

                $primaryEmails = $emRepo->ListByContactId($model->account->contacts[$i]->contact_id);
                foreach($primaryEmails as $pe){
                    if ($pe["is_primary"])
                        $model->account->contacts[$i]->primaryEmail = $pe;
                    else
                        $model->account->contacts[$i]->secondaryEmail = $pe;
                }

                for($i = 0; $i < count($model->account->contacts); $i++) {
                    $pns = $pnRepo->ListByContactId($model->account->contacts[$i]->contact_id);
                    foreach($pns as $pp){
                        if ($pp["is_primary"])
                            $model->account->contacts[$i]->primaryPhone = $pp;
                        else
                            $model->account->contacts[$i]->secondaryPhone = $pp;
                    }

                    $primaryEmails = $emRepo->ListByContactId($model->account->contacts[$i]->contact_id);
                    foreach($primaryEmails as $pe){
                        if ($pe["is_primary"])
                            $model->account->contacts[$i]->primaryEmail = $pe;
                        else
                            $model->account->contacts[$i]->secondaryEmail = $pe;
                    }
                }
            }
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
            $model = $addrCollector->Remerge($req, $model, 'delivery', 'deliveryAddress');

            //Billing address
            if ($req->old("billing-street") !== null || $req->old("billing-street2") !== null || $req->old("billing-city") !== null ||
                $req->old("billing-zip-postal") !== null || $req->old("billing-state-province") !== null || $req->old("billing-country") !== null)
                $model->billingAddress = new \App\Address();
            $model = $addrCollector->Remerge($req, $model, 'billing', 'billingAddress');

            //Contacts
            $model = $contactCollector->Remerge($req, $model);

            //Commissions
            $model = $commissionCollector->Remerge($req, $model);

            return $model;
        }
	}
