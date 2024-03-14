<style>
.header {
    width: 100%;
    position: relative;
    top: -18px;
}
.header table {
    font-size: 10px;
    margin: 0 auto;
    border-bottom: 1px solid black;
    width: 90%;
}
</style>
<div class='header'>
    <table>
        <td width:'30%'>
            <h4>Driver: {{$model->employee->contact->first_name}} {{$model->employee->contact->last_name}}<br/>
                Manifest ID: {{$model->manifest->manifest_id}}<br/>
                Date: {{$model->manifest->date_run}}</h4>
        </td>
        <td width:'40%' style='text-align: center;'>
            <h2>Fast Forward Express Ltd.</h2>
        </td>
        <td style='text-align: right' width:'30%'>
            <h4>Box 11117<br/>Edmonton, AB<br/>T5J 3K4</h4>
        </td>
    </table>
</div>
