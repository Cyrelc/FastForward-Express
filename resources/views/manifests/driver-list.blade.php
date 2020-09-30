<h3 style='text-align: center'>The following drivers fit the chosen criteria, and have bills that are yet to be manifested:</h5>
<hr/>
<div class="col-lg-12 bottom15">
    @if(isset($model->drivers[0]))
    <table id="driver_preview_table" name="driver_preview_table" style="width:100%">
        <thead>
            <tr>
                <th>Manifest?</th>
                <th>Employee Number</th>
                <th>Driver</th>
                <th>Number of Bills Matched</th>
            </tr>
        </thead>
        <tbody>
            @foreach($model->drivers as $driver)
                <tr>
                    <td><input type='checkbox' checked name='checkboxes[{{$driver->employee_id}}]' value='{{$driver->employee_id}}' /></td>
                    <td>{{$driver->employee_number}}</td>
                    <td>{{$driver->contact->first_name}} {{$driver->contact->last_name}}</td>
                    <td>{{$driver->bill_count}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <h3 style='text-align: center; color: red'>No drivers fit the current criteria. Please enter different dates and try again.</h3>
    @endif
</div>
