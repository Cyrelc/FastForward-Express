@section('script')
<script type='text/javascript' src='/DataTables/media/js/jquery.dataTables.min.js'></script>
<script type='text/javascript' src='/DataTables/media/js/dataTables.bootstrap.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/dataTables.buttons.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.bootstrap.min.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.colVis.js'></script>
<script type='text/javascript' src='/DataTables/extensions/Buttons/js/buttons.print.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/js/bootstrap-multiselect.js'></script>
<script type='text/javascript' src='/js/partials/activity_log.js?{{config('view.version')}}'></script>
@parent
@endsection

@section('style')
<link rel='stylesheet' type='text/css' href='/DataTables/media/css/jquery.dataTables.min.css'/>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.dataTables.min.css'/>
<link rel='stylesheet' type='text/css' href='/DataTables/extensions/Buttons/css/buttons.bootstrap.min.css'/>
<link rel="stylesheet" href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/css/bootstrap-multiselect.css' />
<link rel='stylesheet' type='text/css' href='/css/tables.css' />
@parent
@endsection

<table id='activity_log_table' style='width: 100%' class='table table-striped'>
    <thead>
        <tr>
            <td>Date Modified</td>
            <td>Object</td>
            <td>Object ID</td>
            <td>Modified By</td>
            <td>Type</td>
            <td>Properties</td>
            {{-- <td>Old</td> --}}
        </tr>
    </thead>
    <tbody>
        @foreach($model->activity_log as $line)
            <tr>
                <td>{{$line->updated_at}}</td>
                <td>{{$line->subject_type}}</td>
                <td>{{$line->subject_id}}</td>
                <td>{{$line->user_name}}</td>
                <td>{{$line->description}}</td>
                <td>
                    <table class='table-condensed table-bordered' style='width: 100%'>
                        <thead>
                            <tr>
                                <td>Field</td>
                                <td>Old</td>
                                <td>New</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($line->properties->attributes as $key => $attribute)
                                @if($line->description != 'created' || $attribute != '')
                                    <tr>
                                        <td><label>{{$key}}</label></td>
                                        @if(isset($line->properties->old))
                                        <td>{{$line->properties->old->$key}}</td>
                                        @else
                                        <td>''</td>
                                        @endif
                                        <td>{{$attribute}}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

