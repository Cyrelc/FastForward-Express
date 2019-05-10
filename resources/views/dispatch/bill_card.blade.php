<i class='fas fa-arrows-alt handle'></i>
Bill: <a href='/bills/edit/{{$bill->bill_id}}' target='none'>{{$bill->bill_id}}</a>&emsp;{{$bill->delivery_type}}
<button type='button' class='btn btn-xs btn-default' data-toggle='collapse' data-target='#details_{{$bill->bill_id}}' style='float:right' title='expand/collapse'><i class='fas fa-chevron-down'></i></button>
<div class='col-md-12 collapse {{$collapse == true ? '' : 'in'}}' id='details_{{$bill->bill_id}}'>
    <div class='progress-bar progress-bar-info' role='progressbar aria-valuenow='{{$bill->percentage_complete * 100}} style='width:{{$bill->percentage_complete * 100}}%'>{{$bill->percentage_complete * 100}}</div>
    <div>
        <h4>Pickup</h4>
        <pre><strong>Location: </strong>{{$bill->pickup_address_name}}<br/><strong>Scheduled: </strong>{{$bill->time_pickup_scheduled}}<br/><strong>Actual: </strong>{{$bill->time_picked_up}}</pre>
        <hr/>
        <h4>Delivery</h4>
        <pre><label>Location: </label>{{$bill->delivery_address_name}}<br/><label>Scheduled: </label>{{$bill->time_delivery_scheduled}}<br/><label>Actual: </label>{{$bill->time_delivered}}</pre>
    </div>
</div>
