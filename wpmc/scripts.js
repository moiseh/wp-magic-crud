jQuery(document).ready(function ($) {

    //
    // OneToMany start
    //
    $(document).on("click", ".wpmc-line-add", function (e){
        var group_index = $(".entity-field-row").length;
        var raw_tpl = $("#wpmc-first-line-tpl").val();
        var replaced = raw_tpl.replace(/{index}/g, group_index);
        
        $(".wpmc-onetomany-container-table tbody").append(replaced);
    });

    $(document).on("click", ".wpmc-line-remove", function (e){
        if ($(this).parent().parent().parent().find("tr").length > 1) {
            $(this).parent().parent().remove();
        }
    });
    //
    // OneToMany end
    //
});