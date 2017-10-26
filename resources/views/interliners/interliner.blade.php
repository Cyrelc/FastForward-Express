@extends ('layouts.app')

@section ('script')

@parent
@endsection

@section ('style')

@parent
@endsection

@section ('content')

@if (isset($model->interliner->interliner_id))
    <h2>Edit Interliner</h2>
@else
    <h2>New Interliner</h2>
@endif

<form method="POST" action="/interliners/store">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
	<div class="clearfix well">
    <pre id='errors' class='hidden'></pre>
    @if(!empty($errors) && $errors->count() > 0)
        <br />
        <div class="col-lg-12">
            <div class="alert alert-danger">
                <p>The following errors occurred on submit:</p>
                <ul>
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <input type="hidden" name="interliner_id" value="{{$model->interliner->interliner_id}}" />

    <div class="col-lg-12 bottom15">
        <div class="input-group">
            <span class="input-group-addon" >Interliner Name</span>
    	    <input type="text" name="name" class="form-control" value="{{$model->interliner->name}}" />
        </div>
    </div>
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4> Address </h4>
            </div>
            <div id="address" class="panel-body" >
                @include('partials.address', ['prefix' => 'address', 'address' => $model->interliner->address, 'enabled' => true])
            </div>
        </div>
    </div>
    <div class='text-center'>
        <button type='submit' class='btn btn-primary'>Submit</button>
    </div>
</form>
@parent
@endsection

@section('advFilter')

@parent
@endsection
