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
		    //Account
		    if ($req->old("name") !== null)
		        $model->account->name = $req->old("name");

            if ($req->old("rate_type_id") !== null)
                $model->account->rate_type_id = $req->old("rate_type_id");

            if ($req->old("account-number") !== null)
                $model->account->account_number = $req->old("account-number");

            if ($req->old("invoice-interval") !== null)
                $model->account->invoice_interval = $req->old("invoice-interval");

            if ($req->old("comment") !== null)
                $model->account->invoice_comment = $req->old("comment");

            if ($req->old("start-date") !== null)
                $model->account->start_date = strtotime($req->old("start-date"));

            if ($req->old("send-bills") !== null)
                $model->account->send_bills = $req->old("send-bills");

            if ($req->input('parent-account-id') != null && strlen($req->input('parent-account-id')) > 0)
                $model->account->is_master = true;

            if ($req->old("parent-account-id") !== null)
                $model->account->parent_account_id = $req->old("parent-account-id");

            if ($req->input('discount') != null && $req->input('discount') > 0)
                $model->account->gets_discount = true;

            if ($req->old("discount") !== null && $req->old("discount") !== "")
                $model->account->discount = $req->old("discount") / 100;

            if ($req->old("isGstExempt") !== null)
                $model->account->gst_exempt = $req->old("isGstExempt") == "true";

            if ($req->old("shouldChargeInterest") !== null)
                $model->account->charge_interest = $req->old("shouldChargeInterest") == "true";

            if ($req->old("canBeParent") !== null)
                $model->account->can_be_parent = $req->old("canBeParent") == "true";

            if ($req->old("useCustomField") !== null)
                $model->account->uses_custom_field = $req->old("useCustomField") == "true";

            if ($req->old("custom-tracker") !== null)
                $model->account->custom_field = $req->old("custom-tracker");

            if ($req->old("has-fuel-surcharge") !== null && $req->old('has-fuel-surcharge') == 'true' && $req->old("has-fuel-surcharge") !== "")
                $model->account->fuel_surcharge = $req->old("fuel-surcharge") / 100;

		    //Delivery Address
            if ($req->old("delivery-street") !== null)
                $model->deliveryAddress->street = $req->old("delivery-street");

            if ($req->old("delivery-street2") !== null)
                $model->deliveryAddress->street2 = $req->old("delivery-street2");

            if ($req->old("delivery-city") !== null)
                $model->deliveryAddress->city = $req->old("delivery-city");

            if ($req->old("delivery-zip-postal") !== null)
                $model->deliveryAddress->zip_postal = $req->old("delivery-zip-postal");

            if ($req->old("delivery-state-province") !== null)
                $model->deliveryAddress->state_province = $req->old("delivery-state-province");

            if ($req->old("delivery-country") !== null)
                $model->deliveryAddress->country = $req->old("delivery-country");

            //Billing address
            if ($req->old("billing-street") !== null || $req->old("billing-street2") !== null || $req->old("billing-city") !== null ||
                $req->old("billing-zip-postal") !== null || $req->old("billing-state-province") !== null || $req->old("billing-country") !== null)
                $model->billingAddress = new \App\Address();

            if ($req->old("billing-street") !== null)
                $model->billingAddress->street = $req->old("billing-street");

            if ($req->old("billing-street2") !== null)
                $model->billingAddress->street2 = $req->old("billing-street2");

            if ($req->old("billing-city") !== null)
                $model->billingAddress->city = $req->old("billing-city");

            if ($req->old("billing-zip-postal") !== null)
                $model->billingAddress->zip_postal = $req->old("billing-zip-postal");

            if ($req->old("billing-state-province") !== null)
                $model->billingAddress->state_province = $req->old("billing-state-province");

            if ($req->old("billing-country") !== null)
                $model->billingAddress->country = $req->old("billing-country");

            //Contacts
            $newContacts = [];
            $deleteContacts = [];

            $primary = $req->old('contact-action-change-primary') === null ? -1 : $req->old('contact-action-change-primary');

            if ($req->old('contact-action-add') !== null) {
                $newContacts = $req->old('contact-action-add');

                if (!is_array($newContacts))
                    $newContacts = [$newContacts];
            }

            if ($req->old('contact-action-delete') !== null) {
                $deleteContacts = $req->old('contact-action-delete');

                if (!is_array($deleteContacts))
                    $deleteContacts = [$deleteContacts];
            }

            $pnsToDelete = $req->old('pn-action-delete');
            $emsToDelete = $req->old('em-action-delete');

            if ($pnsToDelete === null)
                $pnsToDelete = [];

            if ($emsToDelete === null)
                $emsToDelete = [];
            for($i = 0; $i < count($model->account->contacts); $i++) {
                $contactId = $model->account->contacts[$i]->contact_id;

                if (in_array($contactId, $newContacts))
                    $model->account->contacts[$i]->is_new = true;
                else
                    $model->account->contacts[$i]->is_new = false;

                if (in_array($contactId, $deleteContacts))
                    $model->account->contacts[$i]->delete = true;
                else
                    $model->account->contacts[$i]->delete = false;

                if ($primary !== -1) {
                    if ($contactId == $primary)
                        $model->account->contacts[$i]->is_primary = true;
                    else
                        $model->account->contacts[$i]->is_primary = false;
                }

                if ($req->old("contact-" . $contactId . "-first-name") !== null)
                    $model->account->contacts[$i]->first_name = $req->old("contact-" . $contactId . "-first-name");

                if ($req->old("contact-" . $contactId . "-last-name") !== null)
                    $model->account->contacts[$i]->last_name = $req->old("contact-" . $contactId . "-last-name");

                if ($req->old("contact-" . $contactId . "-phone1") !== null)
                    $model->account->contacts[$i]->primaryPhone->phone_number = $req->old("contact-" . $contactId . "-phone1");

                if ($req->old("contact-" . $contactId . "-phone1-ext") !== null)
                    $model->account->contacts[$i]->primaryPhone->extension_number = $req->old("contact-" . $contactId . "-phone1-ext");

                if ($req->old("contact-" . $contactId . "-email1") !== null)
                    $model->account->contacts[$i]->primaryEmail->email_address = $req->old("contact-" . $contactId . "-email1");

                if ($req->old('pn-action-add-' . $contactId) !== null || $req->input('contact-' . $contactId . '-phone2-id') !== null) {
                    $model->account->contacts[$i]->secondaryPhone = new \App\PhoneNumber();

                    if ($req->old('pn-action-add-' . $contactId) !== null) {
                        $model->account->contacts[$i]->secondaryPhone->is_new = true;
                    }

                    $model->account->contacts[$i]->secondaryPhone->phone_number_id = $req->old('contact-' . $contactId . '-phone2-id');

                    if ($model->account->contacts[$i]->secondaryPhone->phone_number_id !== null && in_array($model->account->contacts[$i]->secondaryPhone->phone_number_id, $pnsToDelete))
                        $model->account->contacts[$i]->secondaryPhone->delete = true;
                    else
                        $model->account->contacts[$i]->secondaryPhone->delete = false;

                    if ($req->old('contact-' . $contactId . '-phone2') !== null)
                        $model->account->contacts[$i]->secondaryPhone->phone_number = $req->old('contact-' . $contactId . '-phone2');

                    $model->account->contacts[$i]->secondaryPhone->extension_number = $req->old('contact-' . $contactId . '-phone2-ext');
                }

                if ($req->old('em-action-add-' . $contactId) !== null || $req->old('contact-' . $contactId . '-email2-id') !== null) {
                    $model->account->contacts[$i]->secondaryEmail = new \App\EmailAddress();

                    if ($req->old('em-action-add-' . $contactId) !== null)
                        $model->account->contacts[$i]->secondaryEmail->is_new = true;

                    $model->account->contacts[$i]->secondaryEmail->email_address_id = $req->old('contact-' . $contactId . '-email2-id');

                    if ($model->account->contacts[$i]->secondaryEmail->email_address_id !== null && in_array($model->account->contacts[$i]->secondaryEmail->email_address_id, $emsToDelete))
                        $model->account->contacts[$i]->secondaryEmail->delete = true;
                    else
                        $model->account->contacts[$i]->secondaryEmail->delete = false;

                    if ($req->old('contact-' . $contactId . '-email2') !== null)
                        $model->account->contacts[$i]->secondaryEmail->email = $req->old('contact-' . $contactId . '-email2');
                }

                if ($req->old('should-give-commission-1') !== null)
                    $model->give_commission_1 = $req->old('should-give-commission-1') === "true";
                if ($req->old('should-give-commission-2') !== null)
                    $model->give_commission_2 = $req->old('should-give-commission-1') === "true";
                if ($req->old('account-id') === null) {
                    if ($req->old('should-give-commission-1') === "true") {
                        $com = new \App\DriverCommission();

                        if ($req->old('commission-1-employee-id') !== null)
                            $com["driver_id"] = $req->old('commission-1-employee-id');
                        if ($req->old('commission-1-percent') !== null)
                            $com["commission"] = $req->old('commission-1-percent') / 100;
                        if ($req->old('commission-1-depreciate-percentage') !== null)
                            $com["depreciation_amount"] = $req->old('commission-1-depreciate-percentage') / 100;
                        if ($req->old('commission-1-depreciate-duration') !== null)
                            $com["years"] = $req->old('commission-1-depreciate-duration');
                        if ($req->old('commission-1-depreciate-start-date') !== null)
                            $com["start_date"] = strtotime($req->old('commission-1-depreciate-start-date'));

                        $model->commissions[0] = $com;
                    }

                    if ($req->old('should-give-commission-2') === "true") {
                        $com = new \App\DriverCommission();

                        if ($req->old('commission-2-employee-id') !== null)
                            $com["driver_id"] = $req->old('commission-2-employee-id');
                        if ($req->old('commission-2-percent') !== null)
                            $com["commission"] = $req->old('commission-2-percent') / 100;
                        if ($req->old('commission-2-depreciate-percentage') !== null)
                            $com["depreciation_amount"] = $req->old('commission-2-depreciate-percentage') / 100;
                        if ($req->old('commission-2-depreciate-duration') !== null)
                            $com["years"] = $req->old('commission-2-depreciate-duration');
                        if ($req->old('commission-2-depreciate-start-date') !== null)
                            $com["start_date"] = strtotime($req->old('commission-2-depreciate-start-date'));


                        $model->commissions[1] = $com;
                    }
                } else {

                    if ($model->give_commission_1) {
                        $com = $model->commissions[0];

                        if ($req->old('commission-1-employee-id') !== null)
                            $com["driver_id"] = $req->old('commission-1-employee-id');
                        if ($req->old('commission-1-percent') !== null)
                            $com["commission"] = $req->old('commission-1-percent') / 100;
                        if ($req->old('commission-1-depreciate-percentage') !== null)
                            $com["depreciation_amount"] = $req->old('commission-1-depreciate-percentage') / 100;
                        if ($req->old('commission-1-depreciate-duration') !== null)
                            $com["years"] = $req->old('commission-1-depreciate-duration');
                        if ($req->old('commission-1-depreciate-start-date') !== null)
                            $com["start_date"] = strtotime($req->old('commission-1-depreciate-start-date'));

                        $model->commissions[0] = $com;
                    }

                    if ($model->give_commission_2) {
                        $com = $model->commissions[1];

                        if ($req->old('commission-2-employee-id') !== null)
                            $com["driver_id"] = $req->old('commission-2-employee-id');
                        if ($req->old('commission-2-percent') !== null)
                            $com["commission"] = $req->old('commission-2-percent') / 100;
                        if ($req->old('commission-2-depreciate-percentage') !== null)
                            $com["depreciation_amount"] = $req->old('commission-2-depreciate-percentage') / 100;
                        if ($req->old('commission-2-depreciate-duration') !== null)
                            $com["years"] = $req->old('commission-2-depreciate-duration');
                        if ($req->old('commission-2-depreciate-start-date') !== null)
                            $com["start_date"] = strtotime($req->old('commission-2-depreciate-start-date'));

                        $model->commissions[1] = $com;
                    }
                }
            }

            //Commissions
            return $model;
        }
	}
