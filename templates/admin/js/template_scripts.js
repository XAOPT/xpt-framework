$(document).ready(function() {

    // добавим ко всем аякс запросам соответствующий заголовок
    $.ajaxSetup({
        headers: {"IS_AJAX": "true"}
    });

    TABLE_COMPRESSOR.init();
    EDIT_ON_PLACE.init();
    FIELDS_AND_PARAMS.init();
    MULTIPLE_EDIT.init();

    $('[data-toggle="tooltip"]').tooltip({html: true});

    (function() {
        var header = {};

        $("th.sorter-false").each(function(i){
            var e = $(this).index();
            header[e] = {sorter:false};
        });

        $(".divo_table").tablesorter({
                headers:header
        });

    })();


    $(document).on('click', "#show_debug_info", function(){
        $("#debug_info").slideToggle();
        return false;
    } );
    //$('textarea').autosize();

    $(document).on('click', 'button', function(){
        if ($(this).find('a').length != 0)

        document.location = $(this).find('a').attr('href');
    })

    $(".divo_table TR").each(function() {
        if ($(this).prev().hasClass('graytr'))
            $(this).addClass('lightgraytr');
        else
            $(this).addClass('graytr');
    });

    (function(){
        var hightlight = $.cookie('sys_highlight');

        if (hightlight) {
            $( "tr[data-highlight='"+hightlight+"']" ).effect( "highlight", 2000 );
            $.removeCookie('sys_highlight', { path: '/' });
        }
    })();

    $("#min_use_timestamp, #max_use_timestamp").datepicker({
        dateFormat: 'yy-mm-dd 00:00:00',
        monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
        dayNamesShort: ['Вскр', 'Пнд', 'Вт', 'Ср', 'Чтв', 'Птн', 'Сбт'],
        dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб']
    });

    $(document).on('click', '.show_confirm', function(){
        var href = $(this).attr('href');
        var text = $(this).attr('data-confirm-reason');

        $('.modal-body p').html(text);
        $('.confirm_yes').attr('href', href);
        $('#modal_confirm').modal('show');
        return false;
    });

    //$(".sortable").tableDnD();
    $(".sortable tbody").sortable({
        update: function(event, ui)
        {
            var table = $(this).closest('.sortable');
            var url = table.data('sortable-url');

            var order = [];
            table.find("tbody tr").each(function(){
                var id = $(this).data('row-id');
                order.push(id);
            });

            $.ajax({
                type: "POST",
                url: url,
                data: "weight="+order.join()
            });
        }
    });


    //<a class="remove"
    $('a.remove').click(function(e){
        var href = $(this).attr('href');
        e.preventDefault();
        $('#modal_remove').modal('show');
        $('#modal_remove a').attr('href', href);
    });

    $('#modal_remove #cancel_remove').click(function(){
            $('#modal_remove').modal('hide');
    });

});


/* ---------------------------------------------------------------
    MULTIPLE EDIT
 --------------------------------------------------------------- */

var MULTIPLE_EDIT = {
    init: function()
    {
        $(".multiple_operations").on("keypress", function(event){
            return event.keyCode != 13;
        });

        $(".multiple_operations TABLE TR").find("TD:first input[type='checkbox']").click(function(){
            if ($(this).is(":checked"))
                $(this).closest("TR").addClass('success');
            else
                $(this).closest("TR").removeClass('success');
        });

        //$(".multiple_operations TABLE TR").find("TH:first input[type='checkbox']").click(function(){
        $(document).on('click', ".multiple_operations TH input[type='checkbox']", function(){
            if ($(this).is(":checked"))
                $(this).closest(".multiple_operations").find("[type='checkbox']").prop('checked', true);
            else
                $(this).closest(".multiple_operations").find("[type='checkbox']").prop('checked', false);
        });/*$(".multiple_operations").each(function(){
            var table = $(this);
            table.find("tr").each(function() {
                if ($(this).find(":nth-child(1)").is('td'))
                    $(this).prepend("<td><input type='checkbox' name='group[]'></td>");
                else if ($(this).find(":nth-child(1)").is('th'))
                    $(this).prepend("<th></th>");
            });
        });*/
    }
};

 /* ---------------------------------------------------------------
    EDIT ON PLACE
 --------------------------------------------------------------- */
var EDIT_ON_PLACE = {

    init: function()
    {
        $(document).on('click', '[data-eop]', function() {
            var td = $(this);

            if(!$(this).closest('[data-url]').attr('data-url'))
            {
                return;
            }

            if (typeof td.parent().attr('data-eop-locked') !== 'undefined') {
                $('#modal_alert .modal-body').html(td.parent().attr('data-eop-reason'));
                $('#modal_alert').modal('show');

                return;
            }

            var current_value = (typeof td.attr('data-eop-original') !== 'undefined')?td.attr('data-eop-original'):td.html();
            //var td_width = td.outerWidth() ;
            var td_width = current_value.length * 6;
            if (td_width < 50) td_width = 50;

            if (td.find("input").length == 0) {
                td.html("<input class='form-control input-sm' type='text' name='eop_new' style='width: "+td_width+"px; display: inline;' value='"+current_value+"'>");
                td.find("[name='eop_new']").focus();

                if (typeof td.attr('data-eop-type') !== 'undefined' && td.attr('data-eop-type') == 'date') {
                    td.find( "[name='eop_new']" ).datepicker({
                        dateFormat: 'yy-mm-dd 00:00:00',
                        monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
                        dayNamesShort: ['Вскр', 'Пнд', 'Вт', 'Ср', 'Чтв', 'Птн', 'Сбт'],
                        dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб']
                    });
                    td.find( "[name='eop_new']" ).datepicker('show');
                }
            }
        });

        var eop_clicked;

        $(document).mousedown(function(e) {
            // слегка закостилым, чтобы форма не отправлялась, когда выбирается дата по дейтпикеру
            eop_clicked = $(e.target);
        });

        $(document).on('blur keyup', '[name="eop_new"]', function(event) {
            if (event.type == 'keyup' && event.keyCode != 13)
                return;

            if(eop_clicked.closest("#ui-datepicker-div").length > 0 && !eop_clicked.hasClass('ui-state-default'))
                return;

            setTimeout(function(event, obj){

                var input = $(obj);
                input.attr('disabled', 'true');
                var value = input.val();
                var params = input.closest("[data-eop]").attr("data-eop");
                var url = input.closest("[data-url]").attr("data-url");

                if (typeof input.datepicker.settings !== 'undefined')
                    input.datepicker('hide');

                $.ajax({
                    type: "POST",
                    url: url,
                    data: "value="+encodeURIComponent(value)+"&params="+params,
                    dataType: 'JSON',
                    success: function(data) {
                        if (typeof data.success !== 'undefined' &&
                            typeof data.value !== 'undefined' &&
                            data.success
                            )
                        {
                            if (typeof input.closest("[data-eop]").attr('data-eop-original') !== 'undefined')
                                input.closest("[data-eop]").attr('data-eop-original', data.value)

                            input.closest("[data-eop]").html(data.value);
                        }
                    }
                });
            }, 100, event, this);
        });

    }
};


/* ---------------------------------------------------------------
    TABLE_COMPRESSOR
 --------------------------------------------------------------- */

TABLE_COMPRESSOR = {
    init: function()
    {
        this.table = $(".onscreen");

        if (this.table.length > 0) {
            this.table_width = this.table.outerWidth();
            this.available_space = $(window).width() - 220;      // вот здесь какая-то дрянь с забитой шириной левого меню.
            this.original_table = this.table.html();

            // если таблица действительно не умещается на экране - надо её сжать
            if (this.available_space < this.table_width) {
                TABLE_COMPRESSOR.compress();
            }

            $(document).on('click', '.onscreen_toggler', TABLE_COMPRESSOR.repare_table )
        }
    },
    repare_table: function()
    {
        // восстанавливаем исходный вид таблицы до обработки компрессором

        TABLE_COMPRESSOR.table.html(TABLE_COMPRESSOR.original_table);
    },
    compress: function()
    {
        var width_limit = this.available_space - 100;

        var summary_width = this.table.find("tr:first").find("th:last").outerWidth();

        TABLE_COMPRESSOR.column_count = 0;

        this.table.find("tr:first").find("th").each(function(){
            var t = $(this).outerWidth();

            summary_width += t;

            if (summary_width < width_limit)
                TABLE_COMPRESSOR.column_count++;
        });

        var limit = TABLE_COMPRESSOR.table.find("tr:first").find("th").length;
        var j = TABLE_COMPRESSOR.column_count;

        for (var i = TABLE_COMPRESSOR.column_count; i<limit; i++)
        {
            if (i+1 != limit)
            {
                TABLE_COMPRESSOR.table.find("th:nth-child(" + j + ")").remove();
                TABLE_COMPRESSOR.table.find("td:nth-child(" + j + ")").remove();
            }
            else
            {
                TABLE_COMPRESSOR.table.find("th:nth-child(" + j + ")").html('').addClass('onscreen_toggler');
                TABLE_COMPRESSOR.table.find("td:nth-child(" + j + ")").removeAttr('data-eop').html('').addClass('onscreen_toggler');
            }
        }
    }
};


/* ---------------------------------------------------------------
    Fields manipulating
 --------------------------------------------------------------- */

FIELDS_AND_PARAMS = {
    temp: {
        multi_row_pattern: ''
    },
    init: function()
    {
        $("select[name='type']").change(function() {
            val = $(this).val();

            $("[data-fields='temp_rows']").remove();

            if (val == 'select')
            {
                FIELDS_AND_PARAMS.processSelect();
            }

            if (val == 'array')
            {
                $(".by_default_holder").hide();
                $(".parent_holder").hide();
            }
            else
            {
                $(".by_default_holder").show();
                $(".parent_holder").show();
            }
        });

        $(document).on('click', '.field_select_add_more', function() {
            FIELDS_AND_PARAMS.processSelect();
        });

        if ($('.multi_row_pattern').length > 0) {
            FIELDS_AND_PARAMS.temp.multi_row_pattern = '<tr>'+$('.multi_row_pattern').html()+'</tr>';
        }

        $(document).on('click', '.multi_row_add_more', function(){
            $(this).html('X').removeClass('multi_row_add_more').addClass('multi_row_remove');

            $(this).closest('tbody').append(FIELDS_AND_PARAMS.temp.multi_row_pattern);

            return false;
        })

        $(document).on('click', '.multi_row_remove', function() {
            $(this).closest('tr').remove();
            return false;
        });

        $(".multi_row_form").submit(function(){
            return FIELDS_AND_PARAMS.processSubmit();
        });
    },
    processSelect: function()
    {
        var row = '\
            <div data-fields="temp_rows">\
                <dt></dt>\
                <dd><input name="field_value[]" type="text"> <a href="#" class="field_select_add_more">add more</a></dd>\
            </div>\
        ';
        if ($("[data-fields='temp_rows']").length == 0)
            $(row).insertAfter('#field_type_select')
        else
        {
            $('.field_select_add_more').remove();

            $(row).insertAfter("[data-fields='temp_rows']:last");
        }
    },
    processSubmit: function()
    {
        $('.multi_row_table').each(function(){
            var table = $(this);

            var counter = 0;
            table.find('tr').each(function(){
                var tr = $(this);

                tr.find('[name]').each(function(){
                    var input = $(this);
                    var name = input.attr('name');
                    name = name.replace(/_\d+$/,"");
                    console.log(name);
                    input.attr('name', name+'_'+counter);
                });

                counter++;
            });
        });

        return true;
    }
};