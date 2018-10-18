<div class="panel panel-info">
    <div class="panel-heading text-center">
        <h3>{{$title}}</h3>
    </div>
<!-- account selection -->
    <div class="panel-body bottom15">
<!-- date -->
        <div class="col-lg-12 bottom15">
            <div class="input-group" id="time_{{$prefix}}_scheduled">
                <span class="input-group-addon">Estimated {{$title}}: </span>
                <input type='text' class="form-control" name='time_{{$prefix}}_scheduled' placeholder="{{$title}} Date" value="{{isset($date) ? date("F d, Y g:i A", $date) : ''}}"/>
                <span class="input-group-addon">
                    <i class="fa fa-clock"></i>
                </span>
            </div>
        </div>
<!-- select address entry type -->
        <div class='col-md-12 bottom15'>
            <div class='input-group'>
                <span class='input-group-addon'>Input Address By: </span>
                <select id='{{$prefix}}_address_type' class='form-control selectpicker'>
                    <option value='{{$prefix}}_address' {{!isset($account_id) && !$is_new ? 'selected' : ''}}>Address</option>
                    <option value='{{$prefix}}_account' {{isset($account_id) ? 'selected' : ''}}>Account</option>
                </select>
            </div>
        </div>
    </div>
    <div class='panel-footer bottom15 clearfix'>
        <div class='tab-content'>
<!-- address -->
            <div id='{{$prefix}}_address' class='tab-pane fade {{(!$is_new && !isset($account_id)) ? 'in active' : ''}} '>
                @include('partials.address', ['enabled' => true])
            </div>
<!-- account select option -->
            <div id='{{$prefix}}_account' class='col-md-12 tab-pane fade'>
                <div class="input-group bottom15">
                    <span class="input-group-addon">{{$title}} Account: </span>
                    <select id="{{$prefix}}_account_id" class="form-control selectpicker" data-live-search='true' name="{{$prefix}}_account_id" data-reference="{{$prefix}}_reference">
                        <option></option>
                        @foreach($model->accounts as $a)
                            @if(($is_new && Cookie::get('bill_keep_{{$prefix}}_account') == $a->account_id) || (isset($account_id) && $a->account_id == $account_id))
                                <option selected value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->account_number}} - {{$a->name}}</option>
                            @else
                                <option value="{{$a->account_id}}" data-reference-field-name="{{$a->custom_field}}" >{{$a->account_number}} - {{$a->name}}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
<!-- custom tracker -->
                <div id='{{$prefix}}_reference' class="{{$reference_value == '' ? 'hidden' : ''}} bottom15" name="{{$prefix}}_reference">
                    <div class="input-group">
                        <span id="{{$prefix}}_reference_name" class="input-group-addon" >{{$reference_value}}</span>
                        <input id="{{$prefix}}_reference_value" name="{{$prefix}}_reference_value" class="form-control" type="text" disabled value="{{$reference_value}}" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
