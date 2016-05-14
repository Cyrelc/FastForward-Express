@extends('layouts.popout')

@section('title', 'Bill ' . $source)

@section('head')
    @parent
    <link rel='stylesheet' type='text/css' href='/css/bill_popout.css' />
    <script type="text/javascript">
        function submitData(callback) {
            $.post('{{ $action }}', $('#billcreate').serialize()).always(function(e) {
                try {
                    e = JSON.parse(e);
                }catch(err){
                    $('.error').html(JSON.stringify(e));
                    return;
                }
                $('.success').addClass('hidden');
                if (e) {
                    if (!e.success) {
                        if (e.errors) {
                            var error_div = $('.error');
                            error_div.html('');
                            for (var error in e.errors) {
                                error_div.append('<div>' + e.errors[error] + '</div>');
                            }
                            error_div.removeClass('hidden');
                        }
                        if (e.setforced)
                            $('input[name="force"]').val(1);
                    } else {
                        $('.error').html('');
                        $('.error').addClass('hidden');
                        callback();
                        $('.success').html('Success');
                        $('.success').removeClass('hidden');
                        {{-- Success --}}
                    }
                } else {
                    {{-- Unknown error... --}}
                }
            });
        }
        function submit_close() {
            window.close();
        }
        function submit_customer() {
            $('input[name="force"]').val(0);
            $('input[name="orig_bill"]').val(-1);
            $('input[name="number"]').val('');
            $('input[name="date"]').val('{{ date("Y-m-d") }}');
            $('input[name="description"]').val('');
            $('input[name="reference"]').val('');
            $('input[name="amount"]').val('');
            $('input[name="driver_amount"]').val('');
            $('input[name="int_amount"]').val('');
            $('select[name="payment_id"]').val(1);
        }
        function submit_driver() {
            $('input[name="force"]').val(0);
            $('input[name="orig_bill"]').val(-1);
            $('input[name="number"]').val('');
            $('input[name="date"]').val('{{ date("Y-m-d") }}');
            $('input[name="description"]').val('');
            $('input[name="customer"]').val('');
            $('input[name="reference"]').val('');
            $('input[name="amount"]').val('');
            $('input[name="int_amount"]').val('');
            $('select[name="payment_id"]').val(1);
        }
        function submit_none() {
            $('input[name="force"]').val(0);
            $('input[name="orig_bill"]').val(-1);
            $('input[name="number"]').val('');
            $('input[name="date"]').val('{{ date("Y-m-d") }}');
            $('input[name="description"]').val('');
            $('input[name="customer"]').val('');
            $('input[name="reference"]').val('');
            $('input[name="amount"]').val('');
            $('input[name="driver_amount"]').val('');
            $('input[name="int_amount"]').val('');
            $('select[name="payment_id"]').val(1);
        }
        function submit_all() {
            $('input[name="force"]').val(0);
            $('input[name="orig_bill"]').val(-1);
        }
    </script>
@stop

@section('content')
    <div class='error hidden'></div>
    <div class='success hidden'></div>
    <form id='billcreate' method='POST' action={{ $action }}>
        {!! csrf_field() !!}
        <input type='hidden' name='orig_bill' value='{{ isset($bill) ? $bill->number : -1 }}'>
        <input type='hidden' name='force' value='0'>
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
                        <div><input name='description' maxlength='255' value='{{ isset($bill) ? $bill->description : "" }}'></div>
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
                        <div><select name='payment_id'>
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
                        <div><input name='int_amount' value='{{ isset($bill) ? $bill->driver_amount : "" }}'></div>
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
    <button onclick='submitData(submit_close);'>Submit</button>
    <button onclick='submitData(submit_customer);'>Submit, Keep Customer</button>
    <button onclick='submitData(submit_driver);'>Submit, Keep Driver</button>
    <button onclick='submitData(submit_none);'>Submit, Keep None</button>
    <button onclick='submitData(submit_all);'>Submit, Keep All</button>
    <button>Cancel</button>
@stop
