<script type="text/javascript">
    $(document).ready(function(){
        dateInput('{{$prefix}}-depreciate-start-date');
    });
</script>

<div class="col-lg-4 bottom15" id="{{$prefix}}-div" data-hide="true">
    <div class="well">
        <h3 class="panel-title bottom15">{{$title}}</h3>
        @if (isset($commission->commission_id) && $commission->commission_id > 0)
            <input type="hidden" name="{{$prefix}}-id" value="{{$commission->commission_id}}" />
        @endif
        <div class="col-lg-6 bottom15">
            <select id="employee-2-select" class="form-control" name='{{$prefix}}-employee-id'>
                <option></option>
                @foreach($employees as $e)
                    @if (count($model->commissions) > 1 && $e->employee_id == $commission->driver_id)
                        <option selected="selected" value="{{$d->employee_id}}">{{$e->contact->first_name . ' ' . $e->contact->last_name}}</option>
                    @else
                        <option value="{{$e->employee_id}}">{{$e->contact->first_name . ' ' . $e->contact->last_name}}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="col-lg-6 bottom15">
            <div class="input-group">
                <input class='form-control' min=0 max=100 type='number' name='{{$prefix}}-percent' placeholder="Commission %" value="{{count($model->commissions) > 1 ? $commission->commission * 100 : "" }}"/>
                <span class="input-group-addon">%</span>
            </div>
        </div>
        <h5>Depreciation rules</h5>
        <hr>
        <div class="input-group bottom15">
            <span class="input-group-addon">Depreciate by</span>
            <input class="form-control" min=0 max=100 type='number' name='{{$prefix}}-depreciate-percentage' placeholder="Depreciation %" value="{{count($model->commissions) > 1 ? $commission->depreciation_amount * 100 : "" }}">
            <span class="input-group-addon"> % </span>
        </div>
        <div class="input-group bottom15">
            <span class="input-group-addon"> for </span>
            <input class="form-control" min=0 max=100 type='number' name='{{$prefix}}-depreciate-duration' placeholder="Depreciation duration" value="{{count($model->commissions) > 0 ? $commission->years : "" }}"/>
            <span class="input-group-addon"> years </span>
        </div>
        <div class="input-group bottom15">
            <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i> starting </span>
            <input type='text' id="{{$prefix}}-depreciate-start-date" name="{{$prefix}}-depreciate-start-date" class="form-control" placeholder="Depreciation start date" value="{{count($model->commissions) > 0 ? date("l, F d Y", $commission->start_date) : "" }}"/>
        </div>
    </div>
</div>