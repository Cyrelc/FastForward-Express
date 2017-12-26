<script type='text/javascript' src='{{URL::to('/')}}/js/partials/phone_numbers.js'></script>

<div class="input-group">
    <table id="{{$prefix}}-table" class="table">
        <thead>
            <tr>
                <td><button type="button" class="btn btn-xs" onclick="addPhone('{{$prefix}}');" style="margin-right:10px"><i class="fa fa-plus"></i></button>Phone Numbers</td>
            </tr>
        </thead>
        <tbody>
            @php $phone_view_id = 0 @endphp
            @if(isset($phoneNumbers))
                @foreach($phoneNumbers as $phone)
                    <tr><td>
                        @include('partials.phone_number',['prefix' => $prefix . '-' . $phone_view_id, 'phone' => $phone, 'types' => $phoneNumbers->types])
                    </td></tr>
                    @php $phone_view_id++ @endphp
                @endforeach
                <input type='hidden' id='{{$prefix}}-next-id' name='{{$prefix}}-next-id' value='{{$phone_view_id}}' />
            @else
                <input type='hidden' id='{{$prefix}}-next-id' name='{{$prefix}}-next-id' value='{{$phone_view_id}}' />
                <script type="text/javascript">addPhone('{{$prefix}}');</script>
            @endif
        </tbody>
    </table>
</div>
