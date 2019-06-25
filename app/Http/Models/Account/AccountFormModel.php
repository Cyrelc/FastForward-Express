<?php
namespace app\Http\Models\Account;


class AccountFormModel
{
    public $account;
    public $parentAccount;
    public $accounts;
    public $employees;
    public $commissions;
    public $deliveryAddress;
    public $next_id;
    public $prev_id;
    public $parents = array();
    public $sort_options = array();
}
