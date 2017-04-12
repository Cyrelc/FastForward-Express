<?php
namespace App\Http\Repos;

use App\Account;

class AccountRepo {

    public function ListAll() {
        $accounts = Account::All();

        return $accounts;
    }

    public function ListParents() {
        $accounts = Account::where('is_master', '=', true)->get();

        return $accounts;
    }

    public function GetById($id) {
        $account = Account::where('account_id', '=', $id)->first();

        return $account;
    }

    public function Insert($acct, $primaryId, $secondaryIds) {
        $new = new Account;

        $new = $new->create($acct);

        $new->contacts()->attach($primaryId);

        foreach($secondaryIds as $secondaryId)
            $new->contacts()->attach($secondaryId);

        return $new;
    }

    public function Edit($acct) {
        $acct->save();
    }
}
