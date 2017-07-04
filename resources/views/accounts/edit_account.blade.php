@extends ('layouts.app')

@section ('script')

@parent
@endsection

@section ('style')

@parent
@endsection

@section ('content')

<h2>Edit Account</h2>
<form onsubmit="return validate()" method="POST" action="/accounts/store">

</form>

@endsection

@section ('advFilter')

@endsection
