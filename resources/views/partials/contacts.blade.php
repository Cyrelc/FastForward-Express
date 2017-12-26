<script src="{{URL::to('/')}}/js/partials/contacts.js"></script>

<div class='col-lg-12'>
    <input type='hidden' id='{{$prefix}}-current-primary' name='{{$prefix}}-current-primary' value='contact-0'>
    <div class="panel panel-default" id="{{$prefix}}-contacts">
        <div class='panel-heading'>
            <h3 class='panel-title'><button type='button' onclick='addContact("{{$prefix}}","{{$show_address}}");'><i class='fa fa-plus'></i></button>&nbsp&nbsp{{$title}}</h3>
        </div>

        <div class="panel-body">
            <div class='col-lg-2'>
                <ul id="{{$prefix}}-contact-tabs" class="tab nav nav-pills nav-stacked bottom15" role="tablist">
                    @php $contact_view_id = 0 @endphp
                    @if(count($contacts) > 0)
                        @foreach($contacts as $contact)
                            <li id='contact-{{$contact_view_id}}-tab' role='presentation'>
                                <a data-id='contact-{{$contact_view_id}}-panel' href='#contact-{{$contact_view_id}}-panel' aria-controls='{{ $contact_view_id }}' role='tab' data-toggle='tab'>{{$contact->first_name}} {{$contact->last_name}}</a>
                            </li>
                            @php $contact_view_id++; @endphp
                        @endforeach
                    @endif
                </ul>
            </div>

            <!-- Contact Tab panes -->
            <div class="col-lg-10">
                <div class="tab-content" id="{{$prefix}}-contact-bodies">
                    @php $contact_view_id = 0 @endphp
                    @if(count($contacts) > 0)
                        @foreach($contacts as $contact)
                            <div role='tabpanel' class='tab-pane {{$contact->is_primary == '1' ? 'active' : ''}}' id='contact-{{$contact_view_id}}-panel'>
                                @include('partials.contact', ['prefix' => 'contact-' . $contact_view_id, 'contact' => $contact, 'showAddress' => false, 'parent_prefix' => $prefix, 'multi' => true])
                            </div>
                            @if($contact->is_primary)
                                <script type='text/javascript'>makePrimary('{{$prefix}}','contact-{{$contact_view_id}}')</script>
                            @endif
                            @php $contact_view_id++; @endphp
                        @endforeach
                        <input type="hidden" id="{{$prefix}}-new-contact-id" name="{{$prefix}}-new-contact-id" value='{{$contact_view_id}}' />
                    @else
                        <input type="hidden" id="{{$prefix}}-new-contact-id" name="{{$prefix}}-new-contact-id" value='{{$contact_view_id}}' />
                        <script type='text/javascript'>addContact('{{$prefix}}', '{{$show_address}}', true)</script>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
