@extends('layouts.popout')

@section('title', 'Bill ' . $source)

@section('head')
    @parent
    <link rel='stylesheet' type='text/css' href='/css/bill_popout.css' />
@stop

@section('content')
    <form id='billcreate' method='POST' action={{ $action }}>
        {!! csrf_field() !!}
        <table>
            <tbody>
                <tr>
                    <td>
                        <label>Number:</label>
                        <div><input name='number' value='{{ isset($bill) ? $bill->number : "" }}'></div>
                    </td>
                    <td>
                        <label>Date:</label>
                        <div><input type='date' name='date' value='{{ isset($bill) ? explode(" ", $bill->date)[0] : date("Y-m-d") }}'></div>
                    </td>
                </tr>
                <tr>
                    <td colspan='2'>
                        <label>Description:</label>
                        <div><input name='description' value='{{ isset($bill) ? $bill->description : "" }}'></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Customer:</label>
                        <div><input name='customer' value='{{ isset($bill) ? "CUSTOMER NOT MADE" : "" }}'></div>
                    </td>
                    <td>
                        <label>Reference:</label>
                        <div><input name='reference' value='{{
                                isset($bill) ?
                                    ($bill->hasReference() ? $bill->referenceType->name : "")
                                    : ""
                        }}'></div>
                    </td>
                </tr>
                <tr><td><br></td></tr>
                <tr>
                    <td>
                        <label class='large'>Payment Method:</label>
                        <div><select name='payment'>
                            @foreach(App\PaymentType::all() as $payment)
                                <option value={{ $payment->id }} {{ isset($bill) && $bill->paymentType->id == $payment->id ? "selected" : ""}}>{{ $payment->name }}</option>
                            @endforeach
                        </select></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label class='large'>Total Amount:</label>
                        <div><input name='amount' value='{{ isset($bill) ? $bill->amount : "" }}'></div>
                    </td>
                    <td>
                        <label>Taxes:</label>
                        <div><input name='taxes' disabled value='{{ isset($bill) ? $bill->taxes : "" }}  Autocalc this'></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label class='large'>Driver Amount:</label>
                        <div><input name='driver_amount' value='{{ isset($bill) ? $bill->driver_amount : "" }}'></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label class='large'>Interliner Amount:</label>
                        <div><input name='driver_amount' value='{{ isset($bill) ? $bill->driver_amount : "" }}'></div>
                    </td>
                    <td class='centered'>
                        <input type='checkbox' disabled {{ isset($bill) && $bill->hasManifested() ? 'checked' : '' }}><span class="disabled">  Manifested</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
@stop

@section('buttons')
    <button>Submit</button>
    <button>Submit, Keep Customer</button>
    <button>Submit, Keep Driver</button>
    <button>Submit, Keep None</button>
    <button>Submit, Keep All</button>
    <button>Cancel</button>
@stop
