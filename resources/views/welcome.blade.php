@extends('layouts.app')

@section('style')
<link rel='stylesheet' type='text/css' href='/css/welcome/welcome.css' />
@parent
@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading">Welcome</div>

                <div class="panel-body">
                    <table>
                        <thead>
                            <tr>
                                <td>Version</td>
                                <td>Info</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1.148</td>
                                <td>
                                    <ul>
                                        <li>Added ability to mass print invoices</li>
                                        <li>Invoice table now asynchronous</li>
                                        <li>Added missing link to view invoices from table</li>
                                        <li>Fixed delivery type missing from invoice view</li>
                                        <li></li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td>1.147</td>
                                <td>
                                    <ul>
                                        <li>Adjusted invoice create/edit page to remove unnecessary code.</li>
                                        <li>Fixed issue with multiple submissions of Chargebacks creating duplicate entries</li>
                                        <li>Chargebacks are now updated and created as necessary, and tied to manifests when manifests are run</li>
                                        <li>Chargebacks are now shown on manifests, as is the total</li>
                                        <li>Manifests can now be printed individually</li>
                                        <li>Manifest list view can now be accesssed, and is asynchronously generated</li>
                                        <li>Font Awesome updated to version 5.0.8, allowing access to new icons</li>
                                        <li>Chargbacks now use a join table when generated for manifests. NOTE: Requires DB alterations</li>
                                        <li>Able to add minimum invoice amount onto accounts on creation and update</li>
                                        <li>Minimum Invoice Amount now overrides bill total in the event that minimum invoice amount is greater and is set</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td>1.145</td>
                                <td>
                                    <ul>
                                        <li>added invoice sort options to automatic seeders</li>
                                        <li>added bill count to invoice views</li>
                                        <li>bills no longer allow editing after they have been manifested</li>
                                        <li>fixed number formatting on manifest view to always show two decimal places</li>
                                        <li>Invoice generate view now automatically refreshes accounts list based on settings</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td>1.144</td>
                                <td>
                                    <ul>
                                        <li>Fixed disabling of accounts from accounts list page</li>
                                        <li>Accounts list view now asynchronous</li>
                                        <li>Security improvements on account list page (only required information is returned from the server)</li>
                                        <li>Changed "parent name" in list view to link to parent account</li>
                                        <li>Changed "account name" in list view to link to account edit</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td>1.143</td>
                                <td>
                                    <ul>
                                        <li>Fix bill pickup/delivery commission</li>
                                        <li>Correct invoice deletion route</li>
                                        <li>Removed obsolete invoice options table and adjusted seeders</li>
                                        <li>New accounts now autofill billing address name based on Company name (will require ctrl + F5 first time)</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td>1.142</td>
                                <td>
                                    <ul>
                                        <li>Manifest creation view added</li>
                                        <li>Manifest preview basic view added</li>
                                        <li>Added logic for creation of and management of Manifests</li>
                                        <li>Changed pickup and delivery drivers from optional to mandatory fields on bill object</li>
                                        <li>Adjusted manifests table to accurately reflect date created</li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
