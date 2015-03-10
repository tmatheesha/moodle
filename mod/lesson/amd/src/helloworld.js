define(['jqueryui', 'jquery'], function(jqui, $) {

    var lessonobjects = null;
    var lessonobjectid = 9999;

    var drawline = function(pagefrom, pageto) {
        if (pageto === 0) {
            return;
        }
        if (!document.getElementById('mod_lesson_page_element_' + pagefrom)) {
            return;
        }
        if (!document.getElementById('mod_lesson_page_element_' + pageto)) {
            return;
        }

        var fromoffset = $('#mod_lesson_page_element_' + pagefrom).offset();
        var tooffset = $('#mod_lesson_page_element_' + pageto).offset();

        var fromx = fromoffset.left + $('#mod_lesson_page_element_' + pagefrom).width();
        var fromy = fromoffset.top + $('#mod_lesson_page_element_' + pagefrom).height();

        var length = Math.sqrt(((tooffset.left - fromx) * (tooffset.left - fromx)) +
                ((tooffset.top - fromy) * (tooffset.top - fromy)));
        var angle = Math.atan2((fromy - tooffset.top), (fromx - tooffset.left)) * (180 / Math.PI);
        var cx = ((fromx + tooffset.left) / 2) - (length / 2);
        var cy = ((fromy + tooffset.top) / 2) - 1;
        var htmlline = "<div class='lessonline' style='left:" + cx + "px; top:" + cy + "px; width:" + length + "px; -moz-transform:rotate(" + angle + "deg); -webkit-transform:rotate(" + angle + "deg); -o-transform:rotate(" + angle + "deg); -ms-transform:rotate(" + angle + "deg); transform:rotate(" + angle + "deg);' />";
        $('body').append(htmlline);
    };

    var drawalllines = function() {
        $('.lessonline').remove();
        for (var lessonpageid in lessonobjects) {
            var currentlessonobject = lessonobjects[lessonpageid];
            var i = 0, nextpageid = 0;
            var jumpname = "jumpto[" + i + "]";
            while (currentlessonobject.hasOwnProperty(jumpname)) {
            // var nextjumpid = currentlessonobject[jumpname];
            // console.log(currentlessonobject[jumpname]);
                if(currentlessonobject[jumpname] === "-1") {
                    nextpageid = currentlessonobject.nextpageid;
                } else {
                    nextpageid = currentlessonobject[jumpname];
                }

                if (currentlessonobject.qtype === "31") {
                    drawline(currentlessonobject.clusterid, nextpageid);
                } else if (nextpageid === "-9") {
                    drawline(currentlessonobject.id, currentlessonobject.id + '1');
                } else if (currentlessonobject.location !== "cluster") {
                    drawline(currentlessonobject.id, nextpageid);
                }
                i += 1;
                jumpname = "jumpto[" + i + "]";
            }
        } 
    };

    var addEOL = function() {
        for (var lessonpageid in lessonobjects) {

            var i = 0, nextpageid = 0;
            var jumpname = "jumpto[" + i + "]";
            while(lessonobjects[lessonpageid].hasOwnProperty(jumpname)) {
                if(lessonobjects[lessonpageid][jumpname] === "-1") {
                    nextpageid = lessonobjects[lessonpageid].nextpageid;
                } else {
                    nextpageid = lessonobjects[lessonpageid][jumpname];
                }

                if (nextpageid === "-9") {
                    // Add the html to the screen.
                    // var currentobject = lessonobjects[lessonpageid];
                    var htmleol = "<div class='mod_lesson_page_element' id='mod_lesson_page_element_" + lessonpageid + "1'><header id='mod_lesson_page_element_" + lessonpageid + "1'>End Of Lesson</header>";
                    htmleol += "<div class='mod_lesson_page_body' id='mod_lesson_page_element_" + lessonpageid + "1'></div></div>";
                    $('#mod_lesson_page_element_' + lessonpageid).append(htmleol);
                    // Add an End Of Lesson object to the lessonobjects.
                    var newid = lessonpageid + '1';
                    var temp = {
                        id: newid,
                        prevpageid: lessonpageid,
                        nextpageid: 'none',
                        qtype: '-1',
                        eolid: newid
                    };
                    lessonobjects[newid] = temp;
                }
                i += 1;
                jumpname = "jumpto[" + i + "]";
            }
        }
    };

    var formatClusters = function() {
        for (var lessonpageid in lessonobjects) {
            var currentobject = lessonobjects[lessonpageid];
            if (lessonobjects[lessonpageid].qtype === "30") {
                // We have a cluster. Now count how many children it has.
                // console.log('children ' + currentobject.clusterchildrenids.length);
                var childwidth = 270;
                var childheight = 100;
                var clusterwidth = $("#mod_lesson_page_element_" + lessonpageid).width();
                var clusterheight = $("#mod_lesson_page_element_" + lessonpageid).height();
                var newwidth = 0;
                var newheight = 0;
                
                if  (currentobject.clusterchildrenids.length > 3) {
                    newwidth = childwidth * 3;
                    newheight = clusterheight + (Math.ceil(currentobject.clusterchildrenids.length / 3) * childheight);
                } else {
                    newwidth = childwidth * currentobject.clusterchildrenids.length;
                    newheight = clusterheight + (childheight * 1);
                }

                // Adjust the cluster width.
                $("#mod_lesson_page_element_" + lessonpageid).width(newwidth);
                // Adjust the cluster height.
                $("#mod_lesson_page_element_" + lessonpageid).height(newheight);
                // Position children in the cluster.
                var originalx = $("#mod_lesson_page_element_" + lessonpageid).offset().left + 10;
                var startx = originalx;
                var starty = $("#mod_lesson_page_element_" + lessonpageid).offset().top + 80;
                for (var key in currentobject.clusterchildrenids) {
                    
                    if ((key % 3) === 0 && key !== "0") {
                        starty = starty + 110;
                        $("#mod_lesson_page_element_" + currentobject.clusterchildrenids[key]).offset({top: starty, left: originalx});
                        startx = originalx + $("#mod_lesson_page_element_" + currentobject.clusterchildrenids[key]).width() + 30;
                    } else {
                        $("#mod_lesson_page_element_" + currentobject.clusterchildrenids[key]).offset({top: starty, left: startx});
                        startx = startx + $("#mod_lesson_page_element_" + currentobject.clusterchildrenids[key]).width() + 30;
                    }

                }
            }
        }
    };

    var replacecontent = function() {
        var htmlelement = "<div class='mod_lesson_menu_item'>Content</div>";
        $('.mod_lesson_menu').append(htmlelement);
        $(".mod_lesson_menu_item").draggable({
             stop: function(event, ui) {

                if (!ui.helper.hasClass('mod_lesson_page_element')) {
                    var lastoffset = ui.helper.offset();
                    // console.log(lastoffset);
                    ui.helper.addClass('mod_lesson_page_element');
                    ui.helper.removeClass('mod_lesson_menu_item');
                    ui.helper.attr('id', 'mod_lesson_page_element_' + lessonobjectid);
                    var htmlelement = '<header id="mod_lesson_page_element_' + lessonobjectid + '">Content</header>';
                    htmlelement += '<div class="mod_lesson_page_body" id="mod_lesson_page_element_' + lessonobjectid + '"></div>';
                    $("#mod_lesson_page_element_" + lessonobjectid).html(htmlelement);

                    ui.helper.detach();
                    $('.mod_lesson_pages').append(ui.helper);
                    $("#mod_lesson_page_element_" + lessonobjectid).offset(lastoffset);
                    lessonobjectid = lessonobjectid + 1;
                    // $(".mod_lesson_page_element").draggable();    
                }
            }
        });
        // console.log('put another content object in the menu');
    };


    return {

        init: function(deliverytext) {
            console.log(deliverytext);
            lessonobjects = deliverytext;
            // Add end of lesson objects.
            addEOL();
            // Format clusters.
            formatClusters();
            // Draw lines between all of the objects.
            drawalllines();

            // addMenu();



            $(".mod_lesson_page_element").draggable({
                // drag: onDragStop
                drag: drawalllines
            });

            $(".mod_lesson_menu_item").draggable({
                stop: function(event, ui) {

                    console.log('still bound');
                    var lastoffset = ui.helper.offset();
                    ui.helper.addClass('mod_lesson_page_element');
                    ui.helper.removeClass('mod_lesson_menu_item');
                    ui.helper.attr('id', 'mod_lesson_page_element_' + lessonobjectid);
                    var htmlelement = '<header id="mod_lesson_page_element_' + lessonobjectid + '">Content</header>';
                    htmlelement += '<div class="mod_lesson_page_body" id="mod_lesson_page_element_' + lessonobjectid + '"></div>';
                    $("#mod_lesson_page_element_" + lessonobjectid).html(htmlelement);

                    ui.helper.detach();
                    $('.mod_lesson_pages').append(ui.helper);
                    $("#mod_lesson_page_element_" + lessonobjectid).offset(lastoffset);
                    lessonobjectid = lessonobjectid + 1;
                    // $(".mod_lesson_page_element").draggable();
                }
            });

            $(".mod_lesson_menu").droppable({
                out: replacecontent,
                drop: function(event, ui) {
                    // console.log(ui.draggable);
                    ui.draggable.remove();
                }
            });

            // $(".mod_lesson_page_element").resizable();

            $(".mod_lesson_pages").on('click', '.mod_lesson_page_element', function() {
                var elementid = this.id;
                var pageids = elementid.split('_');
                var pageid = pageids[4];
                var offset = $(this).offset();
                deliverytext[pageid].x = offset.left;
                deliverytext[pageid].y = offset.top;
                // console.log(deliverytext[pageid]);
                // drawlines(15, 23);
                // console.log($(this).height());
            });
        }
    };



});