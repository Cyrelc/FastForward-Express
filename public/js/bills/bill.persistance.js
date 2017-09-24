<script>
    $(document).ready(function(){
        //TODO: Remove, for testing only
        $("#keep-options input").each(function(i,e) {
            $(e).removeAttr('disabled');

            $(e).click(function() {
                var keeps = {};

                $("#keep-options input").each(function(index, element) {
                    keeps[$(element).attr('id')] = $(element).is(":checked");
                });

                debugger;
                window.storageService.setValue('bills-keep-options', keeps, 'local');
            });
        });

        var keeps = window.storageService.getValue('bills-keep-options', 'local');

        if (keeps) {
            for (var key in keeps) {
                if (keeps.hasOwnProperty(key)) {

                    if (keeps[key] === true)
                        $("#" + key).prop('checked', 'true');
                }
            }
        }
    });
</script>
