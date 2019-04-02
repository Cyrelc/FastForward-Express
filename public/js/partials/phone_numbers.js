function addPhone(button) {
    var tbody = $(button).closest('table').find('tbody');
    var clone = $(tbody).find('tr:first').clone();
    clone.show();
    clone.find('input:text').val('');
    clone.find('input:radio').prop('checked', false);
    clone.find('[name*="_id[]"]').val('');
    clone.find('[name*="_action[]"]').val('create');
    clone.find('.bootstrap-select').replaceWith(function() {return $('select', this);});
    clone.find('option:selected').prop('selected', false);
    clone.appendTo($(tbody));
    $(tbody).find('tr:last .selectpicker').selectpicker();
    cleave();
}

function deletePhone(deleteButton) {
    $tr = $(deleteButton).closest('tr');
    if($(deleteButton).closest('tbody').find('tr:visible').length > 1) {
        $tr.hide();
        $tr.find('[name="phone_action[]"]').val('delete');
        $tr.find('[name="phone[]"]').val('');
        $tr.find('input:radio').prop('checked', false);
    }
}

