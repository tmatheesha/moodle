define(['jqueryui', 'jquery'], function(jqui, $) {


    return {

        init: function(deliverytext) {
            console.log(deliverytext);

            $(".mod_lesson_page_element").draggable();
            // $(".mod_lesson_page_element").resizable();

            $(".mod_lesson_page_element").click(function() {
                var elementid = this.id;
                var pageids = elementid.split('_');
                var pageid = pageids[4];
                var offset = $(this).offset();
                deliverytext[pageid].x = offset.left;
                deliverytext[pageid].y = offset.top;
                console.log(deliverytext[pageid]);
                // console.log($(this).height());
            });
        }
    };
});