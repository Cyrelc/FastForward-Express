<div class="panel panel-info">
    <div class="panel-heading clearfix text-center">
        <h3>{{$title}}</h3>
    </div>
<!-- account selection -->
    <div class="panel-body bottom15 clearfix">
<!-- date -->
        <div class="col-lg-8 bottom15">
            <div class="input-group">
                <span class="input-group-addon">{{$title}} Date: </span>
                <input type='text' id="{{$prefix}}_date_scheduled" class="form-control" name='{{$prefix}}_date_scheduled' placeholder="{{$title}} Date" value="{{date("l, F d Y", $date)}}"/>
                <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </span>
            </div>
        </div>
<!-- time -->
        <div class='col-md-4 bottom15'>
            <div class='input-group date' id='{{$prefix}}_time_expected' name='{{$prefix}}_time_expected'>
                <input type='text' class='form-control' />
                <span class='input-group-addon'><i class='fa fa-clock'></i></span>
            </div>
        </div>
<!-- account select option -->
        <div class="col-lg-12 bottom15">
            <div class="input-group">
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
        </div>
<!-- custom tracker -->
        <div id='{{$prefix}}_reference' class="col-lg-12 {{$model->bill->pickup_reference_value == '' ? 'hidden' : ''}} bottom15" name="{{$prefix}}_reference">
            <div class="input-group">
                <span id="{{$prefix}}_reference_name" class="input-group-addon" >{{$model->pickup_reference_name}}</span>
                <input id="{{$prefix}}_reference_value" name="{{$prefix}}_reference_value" class="form-control" type="text" disabled value="{{$model->bill->pickup_reference_value}}" />
            </div>
        </div>
<!-- address -->
        <button type='button' data-toggle='collapse' data-target='#{{$prefix}}_address'>Address Details</button>

        <div id="{{$prefix}}_address" class="collapse in" >
            <div class='panel-footer clearfix'>
                @include('partials.address', ['enabled' => true])
            </div>
        </div>
    </div>
</div>
