<script type='text/javascript' src='/js/partials/emails.js?{{config('view.version')}}'></script>

<table id='email_address_table' style='width:100%' class='table table-responsive'>
    <thead>
        <tr>
            <td><b>Emails</b></td>
            @if($email_types != null)
                <td>Type</td>
            @endif
            <td>Email Address</td>
            <td>Primary</td>
        </tr>
    </thead>
    <tbody>
        @foreach($emails as $email)
        <tr>
            <input type='hidden' name='email_address_id[]' value='{{isset($email->email_address_id) ? $email->email_address_id : null}}' />
            <input type='hidden' name='email_action[]' value='{{isset($email->email_address_id) ? 'update' : 'create'}}' />
            <td><button type='button' class='btn btn-danger' onclick='deleteEmail(this)' title='Delete Email Address'><i class='fas fa-trash'></i></button></td>
            @if($email_types != null)
            <td>
                <select name='email_type[]' class='form-control selectpicker'>
                    <option></option>
                    @foreach($email_types as $type)
                        <option value='{{$type->value}}' {{$type->value == $email->type ? 'selected' : ''}}>{{$type->name}}</option>
                    @endforeach()
                </select>
            </td>
            @endif
            <td><input type='text' class='form-control email_address' name='email[]' placeholder='Email Address' value='{{$email->email}}' /></td>
            <td><input type='radio' class='form-control' name='email_is_primary[]' {{$email->is_primary ? 'checked' : ''}} value='off'/></td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td><button type='button' class='btn btn-success' onclick='addEmail(this)'>Add Email</button></td>
        </tr>
    </tfoot>
</table>

