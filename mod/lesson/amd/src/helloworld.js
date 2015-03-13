define(['jqueryui', 'jquery'], function(jqui, $) {

    var lessonobjects = null;
    var lessonobjectid = 9999;
    var lessonid = 0;
    var ajaxlocation = 'ajax.php';

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
        var htmlline = "<div class='lessonline' style='left:" + cx + "px; top:" + cy + "px; width:" + length + "px;";
        htmlline += " -moz-transform:rotate(" + angle + "deg); -webkit-transform:rotate(" + angle + "deg);";
        htmlline += " -o-transform:rotate(" + angle + "deg); -ms-transform:rotate(" + angle + "deg);";
        htmlline += " transform:rotate(" + angle + "deg);' />";
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
                    var htmleol = "<div class='mod_lesson_page_element' id='mod_lesson_page_element_" + lessonpageid + "1'>";
                    htmleol += "<header id='mod_lesson_page_element_" + lessonpageid + "1'>End Of Lesson</header>";
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

    var attachElement = function(event, ui) {
        var ischild = $(this).find(ui.helper).length;
        if (!ischild) {
            ui.helper.detach();
            $(this).append(ui.helper);
            // formatClusters();
        }
    };

    var detachElement = function(event, ui) {
        var ischild = $(this).find(ui.helper).length;
        // var lastoffset = ui.helper.offset();
        if (ischild) {
            ui.helper.detach();
            $(".mod_lesson_pages").append(ui.helper);
            ui.helper.offset({left: event.pageX, top: event.pageY});
            // formatClusters();
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
                // var clusterwidth = $("#mod_lesson_page_element_" + lessonpageid).width();
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
                        $("#mod_lesson_page_element_" + currentobject.clusterchildrenids[key]).offset({
                            top: starty,
                            left: originalx
                        });
                        startx = originalx + $("#mod_lesson_page_element_" + currentobject.clusterchildrenids[key]).width() + 30;
                    } else {
                        $("#mod_lesson_page_element_" + currentobject.clusterchildrenids[key]).offset({top: starty, left: startx});
                        startx = startx + $("#mod_lesson_page_element_" + currentobject.clusterchildrenids[key]).width() + 30;
                    }

                }
                $("#mod_lesson_page_element_" + lessonpageid).droppable({
                    drop: attachElement,
                    out: detachElement
                });
            }
        }
    };

    var replacecontent = function() {
        var htmlelement = "<div class='mod_lesson_menu_item'>Content</div>";
        $('.mod_lesson_menu').append(htmlelement);
        $(".mod_lesson_menu_item").draggable({
             stop: createLessonObject
        });
        // console.log('put another content object in the menu');
    };

    var createLessonObject = function(event, ui) {
        if (!ui.helper.hasClass('mod_lesson_page_element')) {
            var lastoffset = ui.helper.offset();
            // console.log(lastoffset);
            ui.helper.addClass('mod_lesson_page_element');
            ui.helper.removeClass('mod_lesson_menu_item');

            var defaultdata = {
                title: 'Default title',
                contents: '',
                qtype: "20",
                // jumpto[0]: "-1", Need to find another way.
                lessonid: lessonid,
                location: "normal",
                previouspageid: "0",
                nextpageid: "0",
                positionx: Math.round(lastoffset.left),
                positiony: Math.round(lastoffset.top)
            };

            // Try some ajax here.
            $.ajax({
                method: "POST",
                url: ajaxlocation,
                dataType: "json",
                data: {
                    action: "createcontent",
                    lessonid: lessonid,
                    lessondata: defaultdata
                }
            })
                .done(function(newobjectid) {
                    // alert("Data Saved: " + msg);
                    console.log(newobjectid);
                    ui.helper.attr('id', 'mod_lesson_page_element_' + newobjectid);
                    var htmlelement = '<header id="mod_lesson_page_element_' + newobjectid + '">Content</header>';
                    htmlelement += '<div class="mod_lesson_page_body" id="mod_lesson_page_element_' + newobjectid + '"></div>';
                    $("#mod_lesson_page_element_" + newobjectid).html(htmlelement);

                    // Add default information to the main lesson object.
                    lessonobjects[newobjectid] = {
                        title: 'Default title',
                        contents: '',
                        qtype: "20",
                        // jumpto[0]: "-1", Need to find another way.
                        lessonid: lessonid,
                        location: "normal",
                        previouspageid: "0",
                        nextpageid: "0"
                    };

                    ui.helper.detach();
                    $('.mod_lesson_pages').append(ui.helper);
                    $("#mod_lesson_page_element_" + newobjectid).offset(lastoffset);
                    
                })

                .fail(function(e) {
                    console.log(e);
                })
        }
        resetListeners();

    };

    var openEditor = function(event) {
        event.stopPropagation();
        var elementid = this.id;
        var pageids = elementid.split('_');
        var pageid = pageids[4];
   
        // Create a page for editing the content.
        var pageeditor = '<div class="mod_lesson_page_editor">';
        pageeditor += '<h3>Edit this lesson page </h3>';
        pageeditor += '<div>Page title</div>';
        pageeditor += '<div><input type="text" id="mod_lesson_title" value="' + lessonobjects[pageid].title + '" /></div>';
        pageeditor += '<div>Page contents</div>';
        pageeditor += '<div><textarea id="mod_lesson_contents">' + lessonobjects[pageid].contents + '</textarea></div>';
        pageeditor += '</div>';
        $('.mod_lesson_pages').append(pageeditor);
        $('.mod_lesson_page_editor').dblclick(function() {
            $(this).remove();
        });
    };

    var resetListeners = function() {
        $(".mod_lesson_page_element").draggable({
            drag: drawalllines
        });

        // Remove handler so that we don't double up with other elements.
        $(".mod_lesson_page_element").unbind('dblclick');

        $(".mod_lesson_page_element").on({
            dblclick: openEditor
        });

        $(".mod_lesson_menu_item").draggable({
            stop: createLessonObject
        });

    };


    return {

        init: function(deliverytext) {
            console.log(deliverytext);
            lessonobjects = deliverytext;
            var firstelementid;
            for (firstelementid in lessonobjects) {
                break;    
            } 
            lessonid = lessonobjects[firstelementid].lessonid;
            // Add end of lesson objects.
            addEOL();
            // Format clusters.
            formatClusters();
            // Draw lines between all of the objects.
            drawalllines();

            // addMenu();
            resetListeners();

            $(".mod_lesson_menu").droppable({
                out: replacecontent,
                drop: function(event, ui) {
                    // console.log(ui.draggable);
                    ui.draggable.remove();
                }
            });
        }
    };



});