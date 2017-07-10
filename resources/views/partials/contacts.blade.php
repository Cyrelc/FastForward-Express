<script src="{{URL::to('/')}}/js/contact.js"></script>

<div class='col-lg-12'>
    <div class="panel panel-default" id="{{$prefix}}-contacts">
        <div class='panel-heading'>
            <h3 class='panel-title'>{{$title}}</h3>
        </div>

        <div class="panel-body">
            <div class='col-lg-2'>
                <ul id="{{$prefix}}-contact-tabs" class="tab nav nav-pills nav-stacked bottom15" role="tablist">
                    <li class="{{ (isset($contacts) && count($contacts) > 0) ? '' : 'active' }}"><a href="#{{$prefix}}-new-contact" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-plus-circle"></i> Add New</a></li>

                    @foreach($contacts as $c)
                        <li class="{{ $c->is_primary == '1' ? 'active' : '' }}" role='presentation'><a data-id='{{ $c->contact_id  }}' href='#{{ $c->contact_id }}-panel' aria-controls='{{ $c->contact_id }}' role='tab' data-toggle='tab'>{!! $c->is_primary == '1' ? '<i class="fa fa-star"></i>' : '' !!} {{ $c->first_name }} {{ $c->last_name }}</a></li>
                    @endforeach
                </ul>
            </div>

            <!-- Contact Tab panes -->
            <div class="col-lg-10">
                <div class="tab-content" id="{{$prefix}}-contact-bodies">
                    <div role="tabpanel" class="tab-pane {{ (isset($contacts) && count($contacts) > 0) ? '' : 'active' }}" id="{{$prefix}}-new-contact">
                        @include('partials.contact', ['multi' => true, 'prefix' => 'new-' . $prefix, 'multi_div_prefix' => $prefix, 'contact' => null, 'showAddress' => $show_address])
                    </div>

                    @foreach($contacts as $c)
                        <div role="tabpanel" class="tab-pane {{$c->is_primary == '1' ? 'active' : ''}}" id="{{$c->contact_id}}-panel">
                            @include('partials.contact', ['multi' => true, 'prefix' => 'contact-' . $c->contact_id, 'multi_div_prefix' => $prefix, 'contact' => $c, 'showAddress' => false])

                            @if ($c->is_primary === true)
                                <input type="hidden" name="contact-action-change-primary" value="{{$c->contact_id}}" />
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>