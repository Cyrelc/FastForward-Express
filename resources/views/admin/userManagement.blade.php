<form id='password-form'>
    <div class='col-md-4'>
        <input type='text' name='password' id='password' class='form-control'/>
    </div>
</form>
<div class='col-md-2'>
    <button type='button' onclick='generateHash()' class='btn btn-default'>Generate Password Hash</button>
</div>
<div class='col-md-6'>
    <div class='input-group mb-3'>
        <input type='text' name='password-hash' id='password-hash' class='form-control' readonly />
		<div class='input-group-append'>
	        <button type='button' class='btn btn-outline-secondary' onclick='copyHashToClipboard()'>Copy</button>
		</div>
    </div>
</div>
<div class='input-group'>
	<input type='text' class='form-control' >
	<div class='input-group-append'>
		<button class='btn btn-outline-secondary' type='button'>Button</button>
	</div>
</div>
<div class='col-md-11'>
	<table id='table'>
		<thead>
			<tr>
				<td></td>
				<td>User ID</td>
				<td>Username</td>
				<td>Email</td>
				<td>Is Locked</td>
			</tr>
		</thead>
	</table>
</div>
