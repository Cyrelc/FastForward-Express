<style>
.header {
    position: fixed;
    top: -5px;
    left: 20px;
}
.header table {
    font-size: 10px;
    /* width: 90%; */
    margin: 0 auto;
    border-bottom: 1px solid black;
}
.billInfo {
    text-align: left;
    width: 10%;
}
.fastForwardTitle {
    text-align: center;
    width: 15%;
}
.companyAddress {
    text-align: right;
    width: 10%;
}
h2, h3, h4, td, table {
    margin-bottom: 0px;
    padding: 0px;
}
</style>

<div class='header'>
    <table style="width: 50%; float: left">
        <th class='billInfo'>
            <h4>
                Bill: B{{$model->bill->bill_id}}
            </h4>
        </th>
        <td class='fastForwardTitle'>
            Fast Forward Express Ltd.
            <h3>Fast Forward Express Ltd.</h3>
        </td>
        <td class='companyAddress'>
            <h4>
                Box 11117<br/>
                Edmonton, AB<br/>
                T5J 3K4
            </h4>
        </td>
    </table>
    <table style="width: 50%; float: right">
        <th class='billInfo'>
            <h4>
                Bill: {{$model->bill->bill_id}}
            </h4>
        </th>
        <td class='fastForwardTitle'>
            <h3>Fast Forward Express Ltd.</h3>
        </td>
        <td class='companyAddress'>
            <h4>
                Box 11117<br/>
                Edmonton, AB<br/>
                T5J 3K4
            </h4>
        </td>
    </table>
</div>
