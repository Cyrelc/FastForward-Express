<?php
namespace app\Http\Models\Account;


class AccountFormModel
{
    public $account;
    public $parentAccount;
    public $next_id;
    public $prev_id;
    public $sort_options = array();
}
