define(['jqueryui', 'jquery'], function(jqui, $) {

    var lessonobjects = null;
    var lessonobjectid = 9999;
    var lessonid = 0;
    var ajaxlocation = 'ajax.php';
    var lesson = null;
    var scale = 0.9;
    var newlesson = true;

    var Lesson = function(data) {
        /**
         * Create and add lesson pages to the lesson.
         *
         * @param {int} pageid Page ID for the lesson page.
         * @param {object} pagedata Data relating to the lesson page.
         */
        this.add_lessonpage = function(pageid, pagedata) {
            // Would like a better way to do this.
            switch(parseInt(pagedata.qtype)) {
                case 31:
                    this.pages[pageid] = new endofcluster_lessonPage(pagedata);
                    break;
                case 30:
                    this.pages[pageid] = new cluster_lessonPage(pagedata);
                    break;
                case 21:
                    this.pages[pageid] = new endofbranch_lessonPage(pagedata);
                    break;
                case 20:
                    this.pages[pageid] = new branchtable_lessonPage(pagedata);
                    break;
                case 10:
                    this.pages[pageid] = new essay_lessonPage(pagedata);
                    break;
                case 8:
                    this.pages[pageid] = new numerical_lessonPage(pagedata);
                    break;
                case 5:
                    this.pages[pageid] = new matching_lessonPage(pagedata);
                    break;
                case 3:
                    this.pages[pageid] = new multichoice_lessonPage(pagedata);
                    break;
                case 2:
                    this.pages[pageid] = new truefalse_lessonPage(pagedata);
                    break;
                case 1:
                    this.pages[pageid] = new shortanswer_lessonPage(pagedata);
                    break;
                default:
                    this.pages[pageid] = new lessonPage(pagedata);
                    break;
            }
        }

        this.id = lessonid;
        this.pages = {};
        for (lessonpage in data) {
            this.add_lessonpage(lessonpage, data[lessonpage]);
        }

    };

    var lessonPage = function(lessonobjectdata) {
        this.id = lessonobjectdata.id;
        this.qtype = parseInt(lessonobjectdata.qtype);
        this.lessonid = lessonobjectdata.lessonid;
        this.title = lessonobjectdata.title;
        this.contents = lessonobjectdata.contents;
        this.positionx = lessonobjectdata.positionx;
        this.positiony = lessonobjectdata.positiony;
        this.qtypestring = lessonobjectdata.qtypestr;
        this.nextpageid = lessonobjectdata.nextpageid;
        this.previouspageid = lessonobjectdata.prevpageid;
        this.location = lessonobjectdata.location;

        if (lessonobjectdata.hasOwnProperty("clusterid")) {
            this.clusterid = lessonobjectdata.clusterid;
        }
        if (lessonobjectdata.hasOwnProperty("subclusterid")) {
            this.subclusterid = lessonobjectdata.subclusterid;
        }

        this.jumps = {};
        var i = 0;
        var jumpname = "jumpto[" + i + "]";
        var answereditor = "answer_editor[" + i + "]";
        var responseeditor = "response_editor[" + i + "]";
        var lessonscore = "score[" + i + "]";

        while (lessonobjectdata.hasOwnProperty(jumpname)) {
            this.jumps[i] = {
                id: parseInt(lessonobjectdata[jumpname]),
                answer: lessonobjectdata[answereditor].text,
                response: lessonobjectdata[responseeditor].text,
                score: lessonobjectdata[lessonscore] // Might need grade here as well.
            }
            i += 1;
            jumpname = "jumpto[" + i + "]";
            answereditor = "answer_editor[" + i + "]";
            responseeditor = "response_editor[" + i + "]";
            lessonscore = "score[" + i + "]";
        }

        if (Object.keys(this.jumps).length === 0) {
            // Create default jumps.
            this.jumps[0] = {
                id: -1,
                answer: "Next page",
                response: "",
                score: 0
            }
            if (this.qtype < 11) {
                this.jumps[1] = {
                    id: 0,
                    answer: "This page",
                    response: "",
                    score: 0
                }
            }
        }

        if ("subclusterchildrenids" in lessonobjectdata) {
            this.childrenids = lessonobjectdata["subclusterchildrenids"];
        } else {
            this.childrenids = [];
        }

    };

    lessonPage.prototype = {
        in_cluster: function() {
            if (this.location === "cluster") {
                return true;
            }
            return false;
        },
        in_subcluster: function() {
            if (this.location === "subcluster") {
                return true;
            }
            return false;
        },
        update_jumps: function(jumpdata) {
            // Remove existing jumps.
            for (index in this.jumps) {
                delete this.jumps[index];
            }
            // Create new jumps from data.
            var i = 0;
            for (jumpid in jumpdata) {
                this.jumps[i] = {
                    id: parseInt(jumpdata[jumpid].jumpto),
                    answer: jumpdata[jumpid].answer,
                    response: jumpdata[jumpid].response,
                    score: jumpdata[jumpid].score,
                }
                i++;
            }
        },
        /**
         * This needs to be extended by the child classes.
         */
        get_default_edit_form: function() {
            var editform = '<div class="mod_lesson_page_editor">';
            editform += '<h3>Edit this ' + this.qtypestring + ' </h3>';
            editform += pageTitle(this.id, this.title);
            editform += pageContents(this.id, this.contents);
            return editform;
        },
        save_edit_form: function() {
            var jumps = {};
            var i = 1;
            var j = 0;
            // Iterate over lesson page answers.
            while ($('#mod_lesson_answer_' + i).length) {
                var jumpanswer = $('#mod_lesson_answer_' + i).val();
                var jumpto = $('#mod_lesson_jump_select_' + i).val();
                var response = '';
                var score = 0;
                if ($('#mod_lesson_response_' + i).length) {
                    response = $('#mod_lesson_response_' + i).val();
                    // console.log('responjse: ' + response);
                }
                if ($('#mod_lesson_score_' + i).length) {
                    score = $('#mod_lesson_score_' + i).val();
                }

                if (Object.keys(this.jumps).length <= j) {
                    // Need to add new jumps
                    this.jumps[j] = {
                        id: jumpto,
                        answer: jumpanswer,
                        response: response,
                        score: score
                    };
                } else {
                    // Update old jumps
                    this.jumps[j].id = jumpto;
                    this.jumps[j].answer = jumpanswer;
                    this.jumps[j].response = response;
                    this.jumps[j].score = score;
                }
                jumps[i] = {
                    answer: jumpanswer,
                    jumpto: jumpto,
                    response: response,
                    score: score
                };
                i++;
                j++;
            }

            var pagetitle = $('#mod_lesson_title_' + this.id).val();
            var pagecontent = $('#mod_lesson_contents_' + this.id).val();
            this.title = pagetitle;
            this.contents = pagecontent;

            var record = {
                page: {
                    id: this.id,
                    title: pagetitle,
                    contents: pagecontent
                },
                answer: {
                    lessonid: lesson.id,
                    pageid: this.id,
                    jumps: jumps

                }
            };

            var json = JSON.stringify(record);
            var pageid = this.id;

            // More ajax to save us all.
            $.ajax({
                    method: "POST",
                    url: ajaxlocation,
                    dataType: "json",
                    data: {
                        action: "updatelessonpage",
                        lessonid: lessonid,
                        jsondata: json
                    }
            }).done(function(newobject) {
                $('#mod_lesson_page_element_' + pageid + '_body').html(pagetitle);
                $('.mod_lesson_page_editor').remove();
                $('#mod_lesson_editor_addjump_btn').unbind('click');
                // Need to Refresh the jumps.
                drawalllines();
            });
        }
    };

    // Lesson page types
    // Cluster
    var cluster_lessonPage = function(lessonobjectdata) {
        lessonPage.call(this, lessonobjectdata);
        if ("clusterchildrenids" in lessonobjectdata) {
            this.childrenids = lessonobjectdata["clusterchildrenids"];
        } else {
            this.childrenids = [];
        }
    };

    cluster_lessonPage.prototype = {
        in_cluster: function() {
            return lessonPage.prototype.in_cluster.call(this);
        },
        in_subcluster: function() {
            return false;
        },
        update_jumps: function(jumpdata) {
            lessonPage.prototype.update_jumps.call(this, jumpdata);
        },
        get_edit_form: function(jumpoptions) {
            return branchtable_lessonPage.prototype.get_edit_form.call(this, jumpoptions);
        },
        save_edit_form: function() {
            lessonPage.prototype.save_edit_form.call(this);
        }
    }

    // End of Cluster
    var endofcluster_lessonPage = function(lessonobjectdata) {
        lessonPage.call(this, lessonobjectdata);
    };

    endofcluster_lessonPage.prototype = {
        in_cluster: function() {
            return lessonPage.prototype.in_cluster.call(this);
        },
        in_subcluster: function() {
            return false;
        },
        update_jumps: function(jumpdata) {
            lessonPage.prototype.update_jumps.call(this, jumpdata);
        }
    }

    // True / False
    var truefalse_lessonPage = function(lessonobjectdata) {
        lessonPage.call(this, lessonobjectdata);
    };

    truefalse_lessonPage.prototype = {
        in_cluster: function() {
            return lessonPage.prototype.in_cluster.call(this);
        },
        in_subcluster: function () {
            return lessonPage.prototype.in_subcluster.call(this);
        },
        update_jumps: function(jumpdata) {
            lessonPage.prototype.update_jumps.call(this, jumpdata);
        },
        get_edit_form: function(jumpoptions) {
            // return branchtable_lessonPage.prototype.get_edit_form.call(this, jumpoptions);
            var editform = lessonPage.prototype.get_default_edit_form.call(this);
            var i = 1;
            editform += '<div id="mod_lesson_editor_answers">';
            for (jumpid in this.jumps) {
                editform += '<div class="mod_lesson_editor_answer">';
                if (i == 1) {
                    editform += '<h4>Correct response</h4>';
                } else if (i == 2) {
                    editform += '<h4>Wrong response</h4>';
                } else {
                    editform += '<h4>Extra response that should be removed</h4>';
                }
                editform += '<div>Answer</div>';
                editform += '<div><input type="text" id="mod_lesson_answer_' + i + '" value="' + this.jumps[jumpid].answer + '"/></div>';
                editform += '<div>Response</div>';
                editform += '<div><textarea id="mod_lesson_response_' + i + '">' + this.jumps[jumpid].response + '</textarea></div>';
                editform += pageJump(this.jumps[jumpid].id, this.id, jumpoptions, i);
                editform += '<div>Score</div>';
                editform += '<div><input type="text" id="mod_lesson_score_' + i + '" value="' + this.jumps[jumpid].score + '"/></div>';
                editform += '</div>';
                i++;
            }
            editform += '</div>';
            editform += '<div><button type="button" id="mod_lesson_editor_save_btn">Save</button>';
            editform += '<button type="button" id="mod_lesson_editor_cancel_btn">Cancel</button>';
            editform += '</div></div>';
            return editform;
        },
        save_edit_form: function() {
            lessonPage.prototype.save_edit_form.call(this);
        }
    }

    // Numerical
    var numerical_lessonPage = function(lessonobjectdata) {
        lessonPage.call(this, lessonobjectdata);
    };

    numerical_lessonPage.prototype = {
        in_cluster: function() {
            return lessonPage.prototype.in_cluster.call(this);
        },
        in_subcluster: function () {
            return lessonPage.prototype.in_subcluster.call(this);
        },
        update_jumps: function(jumpdata) {
            lessonPage.prototype.update_jumps.call(this, jumpdata);
        },
        get_edit_form: function(jumpoptions) {
            // return branchtable_lessonPage.prototype.get_edit_form.call(this, jumpoptions);
            var editform = lessonPage.prototype.get_default_edit_form.call(this);
            var i = 1;
            editform += '<div id="mod_lesson_editor_answers">';
            for (jumpid in this.jumps) {
                editform += '<div class="mod_lesson_editor_answer">';
                editform += '<h4>Answer ' + i + '</h4>';
                editform += '<div>Answer</div>';
                editform += '<div><input type="text" id="mod_lesson_answer_' + i + '" value="' + this.jumps[jumpid].answer + '"/></div>';
                editform += '<div>Response</div>';
                editform += '<div><textarea id="mod_lesson_response_' + i + '">' + this.jumps[jumpid].response + '</textarea></div>';
                editform += pageJump(this.jumps[jumpid].id, this.id, jumpoptions, i);
                editform += '<div>Score</div>';
                editform += '<div><input type="text" id="mod_lesson_score_' + i + '" value="' + this.jumps[jumpid].score + '"/></div>';
                editform += '</div>';
                i++;
            }
            editform += '</div>';
            editform += '<div><button type="button" id="mod_lesson_editor_save_btn">Save</button>';
            editform += '<button type="button" id="mod_lesson_editor_cancel_btn">Cancel</button>';
            editform += '<button type="button" id="mod_lesson_editor_addjump_btn">Add another jump</button>';
            editform += '</div></div>';
            return editform;
        },
        save_edit_form: function() {
            lessonPage.prototype.save_edit_form.call(this);
        },
        add_additional_jump: function(event) {
            // console.log(event);
            var jumpoptions = event.data.jumpoptions;
            // Get the last jump count.
            var i = 1;
            while ($('#mod_lesson_answer_' + i).length) {
                i++;
            }
            // Check should be done for maximum number of jumps.
            var editform = '<div class="mod_lesson_editor_answer">';
            editform += '<h4>Answer ' + i + '</h4>';
            editform += '<div>Answer</div>';
            editform += '<div><input type="text" id="mod_lesson_answer_' + i + '" value=""/></div>';
            editform += '<div>Response</div>';
            editform += '<div><textarea id="mod_lesson_response_' + i + '"></textarea></div>';
            editform += pageJump(0, this.id, jumpoptions, i);
            editform += '<div>Score</div>';
            editform += '<div><input type="text" id="mod_lesson_score_' + i + '" value=""/></div>';
            editform += '</div>';
            $('#mod_lesson_editor_answers').append(editform);
            // return editform;
        },
    }

    // Short answer
    var shortanswer_lessonPage = function(lessonobjectdata) {
        lessonPage.call(this, lessonobjectdata);
    };

    shortanswer_lessonPage.prototype = {
        in_cluster: function() {
            return lessonPage.prototype.in_cluster.call(this);
        },
        in_subcluster: function () {
            return lessonPage.prototype.in_subcluster.call(this);
        },
        update_jumps: function(jumpdata) {
            lessonPage.prototype.update_jumps.call(this, jumpdata);
        },
        get_edit_form: function(jumpoptions) {
            return branchtable_lessonPage.prototype.get_edit_form.call(this, jumpoptions);
        },
        save_edit_form: function() {
            lessonPage.prototype.save_edit_form.call(this);
        }
    }

    // End of branch
    var endofbranch_lessonPage = function(lessonobjectdata) {
        lessonPage.call(this, lessonobjectdata);
    };

    endofbranch_lessonPage.prototype = {
        in_cluster: function() {
            return lessonPage.prototype.in_cluster.call(this);
        },
        in_subcluster: function() {
            return lessonPage.prototype.in_subcluster.call(this);
        },
        update_jumps: function(jumpdata) {
            lessonPage.prototype.update_jumps.call(this, jumpdata);
        }
    }

    // Content page
    var branchtable_lessonPage = function(lessonobjectdata) {
        lessonPage.call(this, lessonobjectdata);
    };

    branchtable_lessonPage.prototype = {
        in_cluster: function() {
            return lessonPage.prototype.in_cluster.call(this);
        },
        in_subcluster: function() {
            return lessonPage.prototype.in_subcluster.call(this);
        },
        update_jumps: function(jumpdata) {
            lessonPage.prototype.update_jumps.call(this, jumpdata);
        },
        get_edit_form: function(jumpoptions) {
            var editform = lessonPage.prototype.get_default_edit_form.call(this);
            var i = 1;
            editform += '<div id="mod_lesson_editor_answers">';
            for (jumpid in this.jumps) {
                editform += '<div class="mod_lesson_editor_answer">';
                editform += '<h4>Content ' + i + '</h4>';
                editform += '<div>Jump name</div>';
                editform += '<div><input type="text" id="mod_lesson_answer_' + i + '" value="' + this.jumps[jumpid].answer + '"/></div>';
                editform += pageJump(this.jumps[jumpid].id, this.id, jumpoptions, i);
                editform += '</div>';
                i++;
            }
            editform += '</div>';
            editform += '<div><button type="button" id="mod_lesson_editor_save_btn">Save</button>';
            editform += '<button type="button" id="mod_lesson_editor_cancel_btn">Cancel</button>';
            editform += '<button type="button" id="mod_lesson_editor_addjump_btn">Add another jump</button>';
            editform += '</div></div>';
            return editform;
        },
        add_additional_jump: function(event) {
            // console.log(event);
            var jumpoptions = event.data.jumpoptions;
            // Get the last jump count.
            var i = 1;
            while ($('#mod_lesson_answer_' + i).length) {
                i++;
            }
            // Check should be done for maximum number of jumps.
            var editform = '<div class="mod_lesson_editor_answer">';
            editform += '<h4>Content ' + i + '</h4>';
            editform += '<div>Jump name</div>';
            editform += '<div><input type="text" id="mod_lesson_answer_' + i + '" value=""/></div>';
            editform += pageJump(0, this.id, jumpoptions, i);
            editform += '</div>';
            $('#mod_lesson_editor_answers').append(editform);
            // return editform;
        },
        save_edit_form: function() {
            lessonPage.prototype.save_edit_form.call(this);
        }
    }

    // Essay page
    var essay_lessonPage = function(lessonobjectdata) {
        lessonPage.call(this, lessonobjectdata);
    };

    essay_lessonPage.prototype = {
        in_cluster: function() {
            return lessonPage.prototype.in_cluster.call(this);
        },
        in_subcluster: function() {
            return lessonPage.prototype.in_subcluster.call(this);
        },
        update_jumps: function(jumpdata) {
            lessonPage.prototype.update_jumps.call(this, jumpdata);
        },
        get_edit_form: function(jumpoptions) {
            return branchtable_lessonPage.prototype.get_edit_form.call(this, jumpoptions);
        },
        save_edit_form: function() {
            lessonPage.prototype.save_edit_form.call(this);
        }
    }

    // Matching page
    var matching_lessonPage = function(lessonobjectdata) {
        lessonPage.call(this, lessonobjectdata);
    };

    matching_lessonPage.prototype = {
        in_cluster: function() {
            return lessonPage.prototype.in_cluster.call(this);
        },
        in_subcluster: function () {
            return lessonPage.prototype.in_subcluster.call(this);
        },
        update_jumps: function(jumpdata) {
            lessonPage.prototype.update_jumps.call(this, jumpdata);
        },
        get_edit_form: function(jumpoptions) {
            return branchtable_lessonPage.prototype.get_edit_form.call(this, jumpoptions);
        },
        save_edit_form: function() {
            lessonPage.prototype.save_edit_form.call(this);
        }
    }

    // Multichoice page
    var multichoice_lessonPage = function(lessonobjectdata) {
        lessonPage.call(this, lessonobjectdata);
    };

    multichoice_lessonPage.prototype = {
        in_cluster: function() {
            return lessonPage.prototype.in_cluster.call(this);
        },
        in_subcluster: function () {
            return lessonPage.prototype.in_subcluster.call(this);
        },
        update_jumps: function(jumpdata) {
            lessonPage.prototype.update_jumps.call(this, jumpdata);
        },
        get_edit_form: function(jumpoptions) {
            return numerical_lessonPage.prototype.get_edit_form.call(this, jumpoptions);
        },
        save_edit_form: function() {
            lessonPage.prototype.save_edit_form.call(this);
        },
        add_additional_jump: function(jumpoptions) {
            numerical_lessonPage.prototype.add_additional_jump.call(this, jumpoptions);
        }
    }

    // End Lesson page types

    // Lesson page edit elements

    var pageTitle = function(pageid, title) {
        var html;
        html = '<div>Page Title</div>';
        html += '<div><input type="input" class="mod_lesson_title" id="mod_lesson_title_' + pageid + '" value="' + title + '" /></div>';
        return html;
    };

    var pageContents = function(pageid, contents) {
        var html;
        html = '<div>Page Content</div>';
        html += '<div><textarea id="mod_lesson_contents_' + pageid + '">' + contents + '</textarea></div>';
        return html;
    };

    var pageJump = function(jumpid, pageid, jumpoptions, number) {
        var html;
        html = '<div>Jump</div>';
        html += '<select id="mod_lesson_jump_select_' + number + '">';
        // $.each(jumpoptions ,function(index, jumpoption) {
        for (index in jumpoptions) {
            if (jumpid == index) {
                html += '<option value="' + index + '" selected>' + jumpoptions[index] + '</option>';
            } else {
                html += '<option value="' + index + '">' + jumpoptions[index] + '</option>';
            }
        }
        html += '</select>';
        return html;
    }
    // End of Lesson page edit elements



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

        // console.log($('.mod_lesson_pages').scrollTop());
        var scrolltopoffset = $('.mod_lesson_pages').scrollTop();
        var scrollleftoffset = $('.mod_lesson_pages').scrollLeft();
        // console.log($('.mod_lesson_pages').scrollLeft());

        var fromoffset = $('#mod_lesson_page_element_' + pagefrom).position();
        // console.log(fromoffset);
        var tooffset = $('#mod_lesson_page_element_' + pageto).position();
        fromoffset.top = fromoffset.top + (scrolltopoffset * 1) + 15;
        tooffset.top = tooffset.top + (scrolltopoffset * 1) + 15;

        fromoffset.left = fromoffset.left + (scrollleftoffset * 1);
        tooffset.left = tooffset.left + (scrollleftoffset * 1);


        var fromx = fromoffset.left + $('#mod_lesson_page_element_' + pagefrom).width();
        var fromy = fromoffset.top + $('#mod_lesson_page_element_' + pagefrom).height();
        // fromy = fromy + scrollleftoffset + 15;

        var length = Math.sqrt(((tooffset.left - fromx) * (tooffset.left - fromx)) +
                ((tooffset.top - fromy) * (tooffset.top - fromy)));
        var angle = Math.atan2((fromy - tooffset.top), (fromx - tooffset.left)) * (180 / Math.PI);
        var cx = ((fromx + tooffset.left) / 2) - (length / 2);
        var cy = ((fromy + tooffset.top) / 2) - 1;
        var htmlline = "<div class='lessonline' style='left:" + cx + "px; top:" + cy + "px; width:" + length + "px;";
        htmlline += " -moz-transform:rotate(" + angle + "deg); -webkit-transform:rotate(" + angle + "deg);";
        htmlline += " -o-transform:rotate(" + angle + "deg); -ms-transform:rotate(" + angle + "deg);";
        htmlline += " transform:rotate(" + angle + "deg);' />";
        $('.mod_lesson_pages').append(htmlline);

        // try to add a circle in the middle of the line.
        // Top position is good.
        var circletop = cy - 15;
        // Pretty god damn close.
        var circleleft = ((fromx + tooffset.left) / 2) - 15;
        var circlediv = "<div class='mod_lesson_jump_circle' id='mod_lesson_jump_circle_" + pagefrom + "'";
        circlediv += " style='left:" + circleleft + "px; top:" + circletop + "px;' data-toggle='tooltip' title='" + lesson.pages[pagefrom].jumps[0].answer + "'></div>";
        $('.mod_lesson_pages').append(circlediv);

    };

    var drawalllines = function() {
        $('.lessonline').remove();
        $('.mod_lesson_jump_circle').remove();
        for (lpid in lesson.pages) {
            var currentobject = lesson.pages[lpid];
            for (jumpid in currentobject.jumps) {
                if (currentobject.jumps[jumpid].id == -1) {
                    nextpageid = currentobject.nextpageid;
                } else {
                    nextpageid = currentobject.jumps[jumpid].id;
                }

                if (currentobject.qtype == 31) {
                    drawline(currentobject.clusterid, nextpageid);
                } else if (nextpageid === "-9") {
                    drawline(currentobject.id, currentobject.id + '1');
                } else if (!currentobject.in_cluster() && !currentobject.in_subcluster()) {
                    drawline(currentobject.id, nextpageid);
                }
            }
        }
    };


    var attachElement = function(event, ui) {
        // var visible = $(this).is(':visible');
        // if (visible) {
        //     console.log('visible');
        // } else {
        //     console.log('can not see this');
        // }
        var elementid = ui.helper.attr('id');
        console.log(elementid);
        var pagesections = elementid.split('_');
        var pageid = pagesections[4];
        var ischild = $(this).find(ui.helper).length;
        if (ischild) {
            return;
        }

        // Need to get the cluster id as well.
        var parentpageid = $(this).attr('id');
        pagesections = parentpageid.split('_');
        var parentid = pagesections[4];

        lesson.pages[pageid].location = 'cluster';

        var extrasauce = null;

        // Checks for content page (difficulty starts here)
        if (lesson.pages[pageid].qtype == 20) {
            lesson.pages[pageid].location = 'subcluster';

            // Check first to see if there is already an end of branch record before creating another one.
            // Create the end of branch page.
            record = {
                qtype: "21",
                lessonid: lesson.id,
                title: "Default title",
                contents: "",
                positionx: 0,
                positiony: 0,
                prevpageid: pageid,
                nextpageid: 0
            };

            // ajax call
            var endofbranch = $.Deferred();


            var promise = $.ajax({
                method: "POST",
                url: ajaxlocation,
                dataType: "json",
                data: {
                    action: "createcontent",
                    lessonid: lessonid,
                    lessondata: record
                }
            });

            promise.done(function(lessonpage) {
                endofbranch.resolve(lessonpage);
            });

            promise.fail(function(e) {
                console.log(e);
            });
            // return endofbranch.promise();
            // console.log(endofbranch.promise());
            extrasauce = endofbranch.promise();
            // return;

            // return a promise
        }

        // Find location in cluster for item.
        // See if there are any childelements.
        var afterid = null;
        if (lesson.pages[parentid].childrenids.length > 0) {
            afterid = lesson.pages[parentid].childrenids[lesson.pages[parentid].childrenids.length -1];
        } else {
            afterid = lesson.pages[parentid].id;
        }

        // Put a when in here.
        $.when(pageid, extrasauce).done(function(var1, var2) {
            // console.log(var1);
            // console.log(var2);

            var pageids = [var1];
            if (var2 !== null) {

                // Create whole object for internal use.
                lesson.add_lessonpage(var2.id, var2);
                lesson.pages[var2.prevpageid].nextpageid = var2.id;
                lesson.pages[var2.id].qtypestr = "End of branch";
                lesson.pages[var2.id].location = "subcluster";
                lesson.pages[var2.id].subclusterid = var1;
                lesson.pages[var2.id].nextpageid = parentid;

                pageids.push(var2.id);
            }
            // console.log(pageids);

            for (index in pageids) {
                // Check that this item isn't already in the array.
                if ($.inArray(pageids[index], lesson.pages[parentid].childrenids) == -1) {
                    lesson.pages[parentid].childrenids.push(pageids[index]);
                }

                var previousid = lesson.pages[pageids[index]].previouspageid;
                var nextid = lesson.pages[pageids[index]].nextpageid;
                if (previousid !== "0") {
                    lesson.pages[previousid].nextpageid = nextid;
                }
                if (nextid !== "0") {
                    lesson.pages[nextid].previouspageid = previousid;
                }
            }

            movepageids = pageids.join();

            lessondata = {
                pageid: movepageids,
                after: afterid
            }

            // Try some ajax here.
            $.ajax({
                method: "POST",
                url: ajaxlocation,
                dataType: "json",
                data: {
                    action: "movepage",
                    lessonid: lesson.id,
                    lessondata: lessondata
                }
            })
                .done(function(newobject) {
                    formatClusters();
                    formatSubClusters();

                })
        });
        // console.log(lesson);
        ui.helper.detach();
        $(this).append(ui.helper);
        
    };

    var detachElement = function(event, ui) {
        var ischild = $(this).find(ui.helper).length;
        // var lastoffset = ui.helper.offset();
        if (ischild) {
            ui.helper.detach();
            $(".mod_lesson_pages").append(ui.helper);
            // ui.helper.offset({left: event.pageX, top: event.pageY});
            formatClusters();
        }
    };

    var formatClusters = function() {
        for (var lessonpageid in lesson.pages) {
            var currentobject = lesson.pages[lessonpageid];
            if (currentobject.qtype === 30) {
                // We have a cluster. Now count how many children it has.
                var childcount = currentobject.childrenids.length;
                
                var childwidth = 270;
                var childheight = 100;
                // // var clusterwidth = $("#mod_lesson_page_element_" + lessonpageid).width();
                var clusterheight = $("#mod_lesson_page_element_" + lessonpageid).height();
                var newwidth = 0;
                var newheight = 0;
                
                // if  (childcount > 3) {
                //     newwidth = childwidth * 3;
                //     additionalheight = (Math.ceil(childcount / 3) * childheight) + 10;
                //     newheight = 100 + additionalheight;
                // } else {
                    // var newchildcount = childcount;
                    // if (childcount == 0) {
                    //     newchildcount = 1;
                    // }
                    newwidth = childwidth;
                    if (clusterheight == 200) {
                        // Height does not need to be adjusted.
                        newheight = clusterheight;
                    } else {
                        newheight = clusterheight + (childheight * 1);
                    }
                // }
                

                // // Adjust the cluster width.
                $("#mod_lesson_page_element_" + lessonpageid).width(newwidth);
                // // Adjust the cluster height.
                $("#mod_lesson_page_element_" + lessonpageid).height(newheight);
                // // Position children in the cluster.
                if (childcount) {
                    var originalx = $("#mod_lesson_page_element_" + lessonpageid).offset().left + 10;
                    var startx = originalx;
                    var starty = $("#mod_lesson_page_element_" + lessonpageid).offset().top + 80;
                    for (var key in currentobject.childrenids) {
                        // $("#mod_lesson_page_element_" + currentobject.childrenids[key]).remove();
                        if ((key % 3) === 0 && key !== "0") {
                            starty = starty + 110;
                            $("#mod_lesson_page_element_" + currentobject.childrenids[key]).offset({
                                top: starty,
                                left: originalx
                            });
                            startx = originalx + $("#mod_lesson_page_element_" + currentobject.childrenids[key]).width() + 30;
                        } else {
                            $("#mod_lesson_page_element_" + currentobject.childrenids[key]).offset({top: starty, left: startx});
                            startx = startx + $("#mod_lesson_page_element_" + currentobject.childrenids[key]).width() + 30;
                        }
                        $("#mod_lesson_page_element_" + currentobject.childrenids[key]).css('display', 'none');
                    }
                    // $("#mod_lesson_page_element_" + lessonpageid).append('<div id="child_count_' + lessonpageid + '><p>Contains ' + childcount + ' page/s.</p></div>');
                } else {
                    // $("#mod_lesson_page_element_" + lessonpageid).append('<div id="child_count_' + lessonpageid + '>Empty</div>');
                }
                $("#mod_lesson_page_element_" + lessonpageid).droppable({
                    drop: attachElement,
                    out: detachElement
                });
            }
        }
    };

    var formatSubClusters = function() {
        for (var lessonpageid in lesson.pages) {
            var currentpage = lesson.pages[lessonpageid];
            if (currentpage.qtype === 20 && currentpage.in_subcluster()) {
                // We have a subcluster. Now count how many children it has.
                var childcount = currentpage.childrenids.length;
                if (childcount === 0) {
                    childcount = 1;
                }
                var childwidth = 250;
                var childheight = 100;
                // var clusterwidth = $("#mod_lesson_page_element_" + lessonpageid).width();
                var subclusterheight = $("#mod_lesson_page_element_" + lessonpageid).height();
                var newwidth = 0;
                var newheight = 0;
                
                if  (childcount > 3) {
                    newwidth = childwidth * 3;
                    newheight = 100 + (Math.ceil(childcount / 3) * childheight) + 10;
                } else {
                    newwidth = childwidth * childcount;
                    newheight = 100 + (childheight * 1);
                }

                // Adjust the subcluster width.
                $("#mod_lesson_page_element_" + lessonpageid).width(newwidth);
                // Adjust the subcluster height.
                $("#mod_lesson_page_element_" + lessonpageid).height(newheight);
                // Position children in the subcluster.
                var originalx = $("#mod_lesson_page_element_" + lessonpageid).offset().left + 10;
                var startx = originalx;
                var starty = $("#mod_lesson_page_element_" + lessonpageid).offset().top + 80;
                for (var key in currentpage.childrenids) {
                    
                    if ((key % 3) === 0 && key !== "0") {
                        starty = starty + 110;
                        $("#mod_lesson_page_element_" + currentpage.childrenids[key]).offset({
                            top: starty,
                            left: originalx
                        });
                        startx = originalx + $("#mod_lesson_page_element_" + currentpage.childrenids[key]).width() + 30;
                    } else {
                        $("#mod_lesson_page_element_" + currentpage.childrenids[key]).offset({top: starty, left: startx});
                        startx = startx + $("#mod_lesson_page_element_" + currentpage.childrenids[key]).width() + 30;
                    }

                }
                // Change the title of the sub cluster from "Content" to "Sub cluster".
                var subclusterheader = "Sub cluster";
                subclusterheader += '<img src="../../theme/image.php?theme=clean&component=core&image=t%2Fedit" class="mod_lesson_page_object_menu"></div></header>';
                $("#mod_lesson_page_element_" + lessonpageid + "_header").html(subclusterheader);
                $("#mod_lesson_page_element_" + lessonpageid).droppable({
                    drop: attachElement,
                    out: detachElement
                });
            }
        }
    };

    var replacecontent = function(event, ui) {
        pagetype = ui.helper.text();
        var htmlelement = "<div class='mod_lesson_menu_item'>" + pagetype + "</div>";
        $('.mod_lesson_menu').append(htmlelement);
        $(".mod_lesson_menu_item").draggable({
             stop: createLessonObject
        });
    };

    var createLessonObject = function(event, ui) {
        var pagetype = ui.helper.text();
        var qtype,
            content,
            title,
            location;
        switch(pagetype) {
            case "Content":
                qtype = "20";
                content = "";
                title =  "Default title";
                location = "normal";
                break;
            case "True False":
                qtype = "2";
                content = "";
                title =  "Default title";
                location = "normal";
                break;
            case "Numerical":
                qtype = "8";
                content = "";
                title =  "Default title";
                location = "normal";
                break;
            case "Multiple Choice":
                qtype = "3";
                content = "";
                title =  "Default title";
                location = "normal";
                break;
            case "Cluster":
                qtype = "30";
                content = "Cluster";
                title =  "Cluster";
                location = "cluster";
                break;
            default:
                qtype = "0";
                content = "";
                title =  "Default title";
                location = "normal";
                break;
        }
        
        if (!ui.helper.hasClass('mod_lesson_page_element')) {
            var lastoffset = ui.helper.offset();
            ui.helper.addClass('mod_lesson_page_element');
            ui.helper.removeClass('mod_lesson_menu_item');

            var defaultdata = {
                title: title,
                contents: content,
                qtype: qtype,
                lessonid: lesson.id,
                location: location,
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
                    lessonid: lesson.id,
                    lessondata: defaultdata
                }
            })
                .done(function(newobject) {
                    if (newobject.qtype !== "31") {
                        ui.helper.attr('id', 'mod_lesson_page_element_' + newobject.id);
                        var htmlelement = '<header id="mod_lesson_page_element_' + newobject.id + '_header">' + pagetype;
                        htmlelement += '<img src="../../theme/image.php?theme=clean&component=core&image=t%2Fedit" class="mod_lesson_page_object_menu"></div></header>';
                        htmlelement += '<div class="mod_lesson_page_element_body" id="mod_lesson_page_element_' + newobject.id + '_body">' + newobject.title + '</div>';
                        $("#mod_lesson_page_element_" + newobject.id).html(htmlelement);
                    }


                    lesson.add_lessonpage(newobject.id, newobject);
                    lesson.pages[newobject.prevpageid].nextpageid = newobject.id;
                    // This is really bad, need to figure out another way to do this.
                    if (newobject.qtype === "30") {
                        // Add the end of cluster object.
                        var endofclusterid = (parseInt(newobject.id)) + 1;
                        var endofclusterdata = {
                            clusterid: newobject.id,
                            contents: "End of cluster",
                            id: endofclusterid,
                            lessonid: lesson.id,
                            location: "normal",
                            nextpageid: "0", 
                            positionx: "0",
                            positiony: "0",
                            qtype: 31,
                            qtypestring: "End of cluster",
                            title: "End of cluster"
                        }
                        lesson.add_lessonpage(endofclusterid, endofclusterdata);
                        lesson.pages[newobject.id].nextpageid = endofclusterid;
                    }

                    if (newobject.qtype !== "31") {
                        ui.helper.detach();
                        $('.mod_lesson_pages').append(ui.helper);
                        $("#mod_lesson_page_element_" + newobject.id).offset(lastoffset);
                        if (pagetype === "Cluster") {
                            formatClusters();
                        }
                        resetListeners();
                        drawalllines();
                    }
                })

                .fail(function(e) {
                    console.log(e);
                })

        }
        drawalllines();
        resetListeners();

    };

    /**
     * May need to be expanded to return the full record for the lesson page.
     */
    var getJumpOptions = function(pageid) {
        var jumpoptions = $.Deferred();

        var promise = $.ajax({
            method: "POST",
            url: ajaxlocation,
            dataType: "json",
            data: {
                action: "getjumpoptions",
                lessonid: lessonid,
                pageid: pageid
            }
        });

        promise.done(function(options) {
            jumpoptions.resolve(options);
        });

        promise.fail(function(e) {
            console.log(e);
        });
        return jumpoptions.promise();
    };

    var openEditor = function(event) {
        event.preventDefault();
        event.stopPropagation();
        var elementid = $(this).parents('.mod_lesson_page_element').attr('id');
        var pageids = elementid.split('_');
        var pageid = pageids[4];
        var jumpselectoptions = '';

        closeObjectMenus();

        $.when(getJumpOptions(pageid)).done(function(joptions){
            // Create a page for editing the content.
            var pageeditor = lesson.pages[pageid].get_edit_form(joptions);
            $('.mod_lesson_pages').append(pageeditor);
            $('#mod_lesson_editor_addjump_btn').click({jumpoptions: joptions}, lesson.pages[pageid].add_additional_jump);
            $('#mod_lesson_editor_save_btn').click(function() {
                lesson.pages[pageid].save_edit_form();
            });
            $('#mod_lesson_editor_cancel_btn').click(function() {
                $('.mod_lesson_page_editor').remove();
            });
        });
    };

    var openObjectMenu = function(event) {

        if (!$(this).parent().children('.mod_lesson_page_object_menu_thing').length) {
            var menu = '<div class="mod_lesson_page_object_menu_thing">';
            menu += '<ul>';
            menu += '<li><a href="#" class="mod_lesson_page_edit">Edit</a></li>';
            menu += '<li><a href="#" class="mod_lesson_page_link">Link</a></li>';
            menu += '<li><a href="#" class="mod_lesson_page_delete">Delete</a></li>';
            menu += '</ul>';
            menu += '</div>';
            $(this).parent().append(menu);
            $('.mod_lesson_page_delete').on({
                click: deleteLessonPageObject
            });
            $('.mod_lesson_page_edit').on({
                click: openEditor
            });
            $('.mod_lesson_page_link').on({
                click: linkLessonPage
            });
        } else {
            $(this).parent().find('.mod_lesson_page_object_menu_thing').remove();
        }
    }

    var closeObjectMenus = function() {
        $('.mod_lesson_main').find('.mod_lesson_page_object_menu_thing').remove();
    };

    var deleteLessonPageObject = function(event) {
        event.preventDefault();
        var elementid = $(this).parents('.mod_lesson_page_element').attr('id');
        var pageids = elementid.split('_');
        var pageid = pageids[4];
        var pagedata = {};
        if (lesson.pages[pageid].qtype == 30) {
            // Find the matching end of cluster.
            for (index in lesson.pages) {
                if (parseInt(lesson.pages[index].clusterid) == pageid) {
                    pagedata.endofclusterid = lesson.pages[index].id;
                }
            }
        }
        if (lesson.pages[pageid].qtype == 20) {
            // Find the matching end of subcluster if it exists.
            for (index in lesson.pages) {
                if (parseInt(lesson.pages[index].subclusterid) == pageid) {
                    pagedata.endofclusterid = lesson.pages[index].id;
                }
            }
        }

        $.ajax({
            method: "POST",
            url: ajaxlocation,
            dataType: "json",
            data: {
                action: "deletelessonpage",
                lessonid: lessonid,
                pageid: pageid,
                lessondata: pagedata
            }
        })
            .done(function() {
                // if (lesson.pages[pageid].qtype == 30) {
                if (pagedata.endofclusterid.length) {
                    delete lesson.pages[pagedata.endofclusterid];
                }
                delete lesson.pages[pageid];

            })

            .fail(function(e) {
                console.log(e);
            });
        $(this).parents('.mod_lesson_page_element').remove();
        closeObjectMenus();
        drawalllines();
    };

    var linkLessonPage = function(event) {
        event.preventDefault();
        event.stopPropagation();
        // Should use parents instead of a lot of parent.
        var elementid = $(this).parent().parent().parent().parent().attr('id');
        var pageids = elementid.split('_');
        var pageid = pageids[4];
        $('.mod_lesson_page_element').click({pageid: pageid}, actuallyLinkLessonPages);
        $('.mod_lesson_page_element').hover(hoverin, hoverout);
    };

    var hoverin = function() {
        $(this).css('background-color', '#b8b8b8');
    };

    var hoverout = function() {
        $(this).css('background-color', 'white');
    };

    var actuallyLinkLessonPages = function(event) {
        var objectid = this.id;
        var jumpids = objectid.split('_');
        var jumpid = jumpids[4];
        var pageid = event.data.pageid;
        $('.mod_lesson_page_element').unbind('click');
        $(this).css('background-color', 'white');
        $('.mod_lesson_page_element').off("mouseenter mouseleave");

        var lessondata = {
            pageid: pageid,
            jumpid: jumpid
        };

        // Go go ajax link and stuff.
        $.ajax({
            method: "POST",
            url: ajaxlocation,
            dataType: "json",
            data: {
                action: "linklessonpages",
                lessonid: lessonid,
                lessondata: lessondata
            }
        })
            .done(function(response) {

                lesson.pages[pageid].update_jumps(response);
                drawalllines();
            })

            .fail(function(e) {
                console.log(e);
            });
        closeObjectMenus();

    };

    var editTitle = function(event) {
        var classdetail = event.currentTarget.id;
        var pageids = classdetail.split('_');
        var pageid = pageids[4];
        var innertext = event.currentTarget.innerText;
        var inputid = 'mod_lesson_inline_edit_' + pageid;
        event.currentTarget.innerHTML = '<input type="text" id="' + inputid + '" value="' + innertext + '"/>';
        $('#' + inputid).keydown(function(e) {
            if (e.which == 13) {
                alert('save this title');
            } 
            if (e.which == 27) {
                event.currentTarget.innerHTML = innertext;
            }
        });
    };

    var expandCluster = function() {
        // Always have to fetch that page ID.
        var lessonpageobject = $(this).parents('.mod_lesson_page_element');
        var elementid = lessonpageobject.attr('id');
        var pageid = elementid.split('_')[4];
        // $('#child_count_' + pageid).css('display', 'none');
        for (index in lesson.pages[pageid].childrenids) {
            $("#mod_lesson_page_element_" + lesson.pages[pageid].childrenids[index]).css('display', 'inline');
        }
        // console.log(lessonpageobject);
        // lessonpageobject.css({width: "300px", height: "300px"});
        lessonpageobject.animate({
            width: "700px",
            height: "500px",
            overflow: "scroll"
        }, 500, function() {
            lessonpageobject.css('z-index', '2');
            drawalllines();
        });
        

    };

    var contractCluster = function() {
        // Always have to fetch that page ID.
        var lessonpageobject = $(this).parents('.mod_lesson_page_element');
        var elementid = lessonpageobject.attr('id');
        var pageid = elementid.split('_')[4];
        // $('#child_count_' + pageid).css('display', 'inline');
        for (index in lesson.pages[pageid].childrenids) {
            $("#mod_lesson_page_element_" + lesson.pages[pageid].childrenids[index]).css('display', 'none');
        }
        // console.log(lessonpageobject);
        // lessonpageobject.css({width: "300px", height: "300px"});
        lessonpageobject.animate({
            width: "300px",
            height: "175px",
            overflow: "scroll"
        }, 500, function() {
            lessonpageobject.css('z-index', '0');
            drawalllines();
        });
        

    };

    var resetListeners = function() {

        $(".mod_lesson_page_element").draggable({
            drag: drawalllines,
            stop: saveLocation
        });

        // Remove handler so that we don't double up with other elements.
        $(".mod_lesson_page_element").unbind('dblclick');
        $(".mod_lesson_page_object_menu").unbind('click');

        // $(".mod_lesson_page_element").on({
        //     dblclick: openEditor
        // });

        $(".mod_lesson_page_object_expand").on({
            click: expandCluster
        });

        $(".mod_lesson_page_object_contract").on({
            click: contractCluster
        });

        $(".mod_lesson_page_object_menu").on({
            click: openObjectMenu
        });             

        $(".mod_lesson_menu_item").draggable({
            stop: createLessonObject
        });

        $("#mod_lesson_reset_button").click(function() {
            console.log('yeah');
            newlesson = true;
            setLessonPages();
        });

        // $('.mod_lesson_pages').scroll(function() {
        //     drawalllines();
        // });

        $('.mod_lesson_page_element_body').on({
            dblclick: editTitle
        });

    };

    var saveLocation = function(event, ui) {
        var lastposition = ui.helper.position();
        var elementid = this.id;
        var pageids = elementid.split('_');
        var pageid = pageids[4];
        var scrolltopoffset = $('.mod_lesson_pages').scrollTop();
        var scrollleftoffset = $('.mod_lesson_pages').scrollLeft();
        var lessonobjectdata = {
            pageid: pageid,
            positionx: Math.round(lastposition.left + (scrollleftoffset * 1)),
            positiony: Math.round(lastposition.top + (scrolltopoffset * 1))
        };

        if (lessonobjectdata.positionx < 0) {
            lessonobjectdata.positionx = 0;
            var lessonelement = $("#mod_lesson_page_element_" + pageid);
            var parentelement = lessonelement.parent();
            // Don't use this.
            lessonelement.position({
                my: "left top",
                at: "left+" + lessonobjectdata.positionx + " top+" + lessonobjectdata.positiony,
                of: parentelement
            });
        }
        if (lessonobjectdata.positiony < 40) {
            lessonobjectdata.positiony = 40;
            var lessonelement = $("#mod_lesson_page_element_" + pageid);
            var parentelement = lessonelement.parent();
            // Don't use this.
            lessonelement.position({
                my: "left top",
                at: "left+" + lessonobjectdata.positionx + " top+" + lessonobjectdata.positiony,
                of: parentelement
            });
        }

        var data = {
            action: "saveposition",
            lessonid: lessonid,
            lessondata: lessonobjectdata
        };

        actuallySaveLocation(data);

    };

    var actuallySaveLocation = function(locationdata) {
        // Try some ajax here.
        $.ajax({
            method: "POST",
            url: ajaxlocation,
            dataType: "json",
            data: locationdata
        })
            .done(function() {
                // Not doing anything at the moment.
            });
    }

    var setLessonPages = function() {
        // var parentelement = $(".mod_lesson_pages");
        var currentcount = 1;
        var currentx = 0;
        var currenty = 40;
        var hascluster = false;

        for (elementid in lessonobjects) {
            if (newlesson) {
                // console.log('yeah!');
                if (lessonobjects[elementid].qtype !== "31" && lessonobjects[elementid].qtype !== "21") {
                    var lessonelement = $("#mod_lesson_page_element_" + elementid);
                    lessonelement.css({position: "absolute", top: currenty, left: currentx});

                    var data = {
                        action: "saveposition",
                        lessonid: lessonid,
                        lessondata: {
                            pageid: elementid,
                            positionx: currentx,
                            positiony: currenty
                        }
                    };

                    actuallySaveLocation(data);

                    currentcount ++;
                    if (currentcount != 4) {
                        currentx += 275;
                    } else {
                        currentx = 0;
                        currenty += 210;
                        currentcount = 0;
                    }
                }
            } else {
                // End of cluster elements have been removed from this form.
                if (lessonobjects[elementid].qtype !== "31" && lessonobjects[elementid].qtype !== "21") {
                    var newx = parseInt(lessonobjects[elementid].x);
                    var newy = parseInt(lessonobjects[elementid].y);
                    var lessonelement = $("#mod_lesson_page_element_" + elementid);
                    // var parentelement = lessonelement.parent();
                    // console.log(lessonobjects[elementid]);
                    // console.log(lessonelement.offsetTop);

                    if (newx < 0) {
                        newx = 0;
                    }
                    if (newy < 0) {
                        newy = 0;
                    }
                    newx = newx + 'px';
                    newy = newy + 'px';
                    // newy = (newy - 50) + 'px';
                    lessonelement.css({position: "absolute", top: newy, left: newx});
                }
            }
        }
        if (newlesson) {
            formatClusters();
            formatSubClusters();
            drawalllines();
            newlesson = false
        }
    };

    var setLessonData = function(lessonid, pageid) {
        var tmepthing = $.Deferred();

        var lessondata = {
            pageid: pageid
        };

        var promise = $.ajax({
            method: "POST",
            url: ajaxlocation,
            dataType: "json",
            data: {
                action: "getlessondata",
                lessonid: lessonid,
                lessondata: lessondata
            }
        });

        promise.done(function(lessonpages) {
            tmepthing.resolve(lessonpages);
        });

        promise.fail(function(e) {
            console.log(e);
        });
        return tmepthing.promise();
    };

    var checkPagePositions = function() {
        for (index in lesson.pages) {
            if (lesson.pages[index].positionx > 0 || lesson.pages[index].positiony > 0) {
                newlesson = false;
                return;
            }
        }
    };

    return {

        init: function(llessonid, pageid) {
            lessonid = llessonid;
            $.when(setLessonData(llessonid, pageid)).done(function(data) {
                lessonobjects = data;
                console.log(lessonobjects);

                lesson = new Lesson(lessonobjects);
                console.log(lesson);

                // Add end of lesson objects.
                // addEOL();
                checkPagePositions();
                if (newlesson) {
                    // console.log('lesson is new');
                } else {
                    // console.log('lesson is old');
                }
                setLessonPages();
                // Format clusters.
                formatClusters();
                formatSubClusters();

                // Position all elements.

                // Draw lines between all of the objects.
                drawalllines();

                // addMenu();
                resetListeners();
                // setLessonPages();

                $(".mod_lesson_menu").droppable({
                    out: replacecontent,
                    drop: function(event, ui) {
                        ui.draggable.remove();
                    }
                });

                // $(".mod_lesson_pages").bind('mousewheel', function(event) {
                //     event.preventDefault();
                //     event.stopPropagation();
                //     if (event.originalEvent.wheelDelta >= 0) {
                //         scale = scale - 0.1;
                //     } else {
                //         scale = scale + 0.1;
                //     }
                //     // var things = $(".mod_lesson_pages").css({"-webkit-transform": "scale(" + scale + ")"});
                //     var things = $(".mod_lesson_pages").css({"zoom": scale, "-moz-transform": "scale(" + scale + ")"});
                //     // var things = $(".mod_lesson_pages").find("*").css({"zoom": scale, "-moz-transform": "scale(" + scale + ")"});
                //     drawalllines();
                // });

            });
        }
    };
});