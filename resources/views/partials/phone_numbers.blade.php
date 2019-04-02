<script type='text/javascript' src='/js/partials/phone_numbers.js?{{config('view.version')}}'></script>

<table id='phone_numbers_table' class='table table-responsive'>
    <thead>
        <tr>
            <td><b>Phone Numbers</b></td>
            <td>Type</td>
            <td>Phone</td>
            <td>Extension</td>
            <td>Primary</td>
        </tr>
    </thead>
    <tbody>
        <input type='hidden' id='primary_phone' name='primary_phone' val='' />
        @foreach($phone_numbers as $phone)
        <tr>
            <input type='hidden' name='phone_number_id[]' value='{{isset($phone->phone_number_id) ? $phone->phone_number_id : null}}' />
            <input type='hidden' name='phone_action[]' value='{{isset($phone->phone_number_id) ? 'update' : 'create'}}' />
            <td><button type='button' class='btn btn-danger' title='Delete Phone' onclick='deletePhone(this)'><i class='fas fa-trash'></i></td>
            <td>
                <select name='phone_type[]' class='form-control selectpicker'>
                    @foreach($phone_types as $type)
                        <option value='{{$type->value}}' {{$phone->type == $type->value ? 'selected' : ''}}>{{$type->name}}</option>
                    @endforeach
                </select>
            </td>
            <td><input type='text' class='form-control phone_number' id='phone[]' name='phone[]' placeholder='Phone Number' value='{{$phone->phone_number}}' /></td>
            <td><input type='text' class='form-control phone_ext' name='extension[]' placeholder='Extension' value='{{$phone->extension_number}}' /></td>
            <td>
                <input type='radio' class='form-control' name='phone_is_primary[]' {{$phone->is_primary ? 'checked' : ''}} />
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td><button type='button' class='btn btn-success' onclick='addPhone(this)'>Add Phone</button></td>
        </tr>
    </tfoot>
</table>
