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
.invoiceInfo {
    text-align: left;
    width: 30%;
}
.fastForwardTitle {
    text-align: center;
    width: 40%;
}
.companyAddress {
    text-align: right;
    width: 30%;
}
h2, h4, td, table {
    margin-bottom: 0px;
    padding-bottom: 0px;
}
</style>

<div class='header'>
    <table>
        <td class='invoiceInfo'>
            <h4>
                @if(!isset($printBillAsInvoice))
                    Account Number: {{$model->parent->account_number}}<br/>
                    Invoice ID: {{$model->invoice->invoice_id}}<br/>
                @endif
                Date: {{$model->invoice->bill_end_date}}
            </h4>
        </td>
        <td class='fastForwardTitle'>
            <h2>Fast Forward Express Ltd.</h2>
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
