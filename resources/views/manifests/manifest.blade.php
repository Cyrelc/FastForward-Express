@extends ('layouts.app')

@section ('script')
@parent
@endsection

@section ('style')
@parent
@endsection

@section ('content')
<div class="col-lg-11">
	<div>
		@include('manifests.manifest_pdf_layout')
	</div>
</div>
@endsection

@section ('advFilter')
<div class="well form-group" style='margin-top:50px'>
	<div class='text-center'>
		<a class='btn btn-info' href='/manifests/print/{{$model->manifest->manifest_id}}' target='blank'><i class='fa fa-print'>Create PDF</i></a>
	</div>
</div>
@endsection
