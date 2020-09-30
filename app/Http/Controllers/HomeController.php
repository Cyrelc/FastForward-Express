<?php

namespace App\Http\Controllers;

use App\Http\Repos;
use App\Http\Requests;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function contactUs(Request $req){
        try {
            \Mail::send('emails.feedback', array('title' => $req->input('title'), 'text' => $req->input('text'), 'type' => $req->input('type')), function($m) use($req) {
                $m->from('fastforwardexpressfeedback@gmail.com', 'FastForward Express Feedback');
                $m->to(env('MAIL_USERNAME'))->subject('Feedback from FFE: ' . $req->input('type'));
            });

            return response()->json([
                'success' => true
            ]);
        } catch(Exception $e){
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getList($type, $parameter = null) {
        switch($type) {
            case 'accounts':
                $accountRepo = new Repos\AccountRepo();
                $accounts = $accountRepo->GetAccountList();
                return json_encode($accounts);
                break;
            case 'activeDrivers':
                $employeeRepo = new Repos\EmployeeRepo();
                $drivers = $employeeRepo->GetDriverList();
                return json_encode($drivers);
                break;
            case 'drivers':
                $employeeRepo = new Repos\EmployeeRepo();
                $drivers = $employeeRepo->GetDriverList(false);
                return json_encode($drivers);
                break;
            case 'employees':
                $employeeRepo = new Repos\EmployeeRepo();
                $employees = $employeeRepo->GetEmployeesList();
                return json_encode($employees);
                break;
            case 'interliners':
                $interlinerRepo = new Repos\InterlinerRepo();
                $interliners = $interlinerRepo->GetInterlinersList();
                return json_encode($interliners);
                break;
            case 'parent_accounts':
                $accountRepo = new Repos\AccountRepo();
                $parentAccounts = $accountRepo->GetParentAccountsList();
                return json_encode($parentAccounts);
                break;
            case 'payment_types':
                $paymentRepo = new Repos\PaymentRepo();
                $paymentTypes = $paymentRepo->GetPaymentTypesList();
                return json_encode($paymentTypes);
                break;
            case 'selections':
                $selectionsRepo = new Repos\SelectionsRepo();
                $selections = $selectionsRepo->GetSelectionsListByType($parameter);
                return json_encode($selections);
                break;
            default:
                throw new Exception('Unable to retrieve the requested list. Please contact support');
                break;
        }
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

}
