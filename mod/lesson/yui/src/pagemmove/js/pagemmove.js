/**
 * A tool for moving and creating lessons.
 *
 * @module moodle-mod_lesson-pagemove
 */

/**
 * A tool for moving and creating lessons.
 *
 * @class M.mod_lesson-pagemmove.PagemMove
 * @extends Base
 * @constructor
 */
function PagemMove() {
    PagemMove.superclass.constructor.apply(this, arguments);
}

var SELECTORS = {
    MAINCONTAINER: '.mod_lesson_main',
    LESSONPAGES: '.mod_lesson_pages',
    PAGEMODULES: '.mod_lesson_page_element'

    };

var AJAXBASE = M.cfg.wwwroot + '/mod/lesson/ajax.php';

Y.namespace('M.mod_lesson').PagemMove = Y.extend(PagemMove, Y.Base, {

    initializer: function() {
        var allLessonPages = Y.all(SELECTORS.PAGEMODULES);
        var lessonpagedata = Y.JSON.parse(this.get('lessondata'));
        // console.log(lessonpagedata);

        YUI().use('dd-delegate', 'dd-drop-plugin', function(Y) {

            var del = new Y.DD.Delegate( {
                container: SELECTORS.MAINCONTAINER,
                nodes: SELECTORS.PAGEMODULES

            });

            del.on('drag:end', function(e) {

                var currentnode = del.get('currentNode');
                console.log(currentnode.getXY());

                var ajaxurl = AJAXBASE,
                    config;

                config = {
                    method: 'post',
                    context: this,
                    sync: false,
                    data : {
                        'action' : 'saveposition',
                    },
                    on: {
                        success: function(tid, response) {
                            var jsondata, quickcomment;
                            try {
                                jsondata = Y.JSON.parse(response.responseText);
                                console.log(jsondata);
                                // if (jsondata.error) {
                                //     return new M.core.ajaxException(jsondata);
                                // } else {
                                //     console.log(jsondata);
                                // }
                            } catch (e) {
                                //return new M.core.exception(e);
                            }
                        },
                        failure: function(tid, response) {
                            //return M.core.exception(response.responseText);
                            alert('something went wrong');
                        }
                    }
                };

                YUI().use("io-base", function(Y) {
                    Y.io(ajaxurl, config);
                });


                // console.log('got here');

                // alert('got here');
                // Save position.
                // this._savedata();


                // Keep it in the main container.

                // Get x and y coordinates and update the database.


            });


        });

        allLessonPages.each(function (lessonPage) {
            // Click and drag of the lesson page.
            lessonPage.on('click', function () {
                console.log(lessonPage);
                // console.log(lessonPage.getXY());
                // lessonPage.setStyle('position', 'absolute');
                // lessonPage.setStyle('left', '25px');
                // lessonPage.setStyle('top', '55px');

            });
        });

        this._redraw();

    },

    _savedata: function() {
        // alert('saving data');
        console.log('saving data');
    },

    _redraw: function() {

        var lessonpagedata = Y.JSON.parse(this.get('lessondata'));
        // console.log(lessonpagedata);
        var lessonpagenodes = Y.all('.mod_lesson_page_element');
        // console.log(lessonpagenodes);
        // // Perhaps destroy the old one first.
        // lessonpagenodes.each(function(node) {
        //     console.log(node);
        //     node.destroy();
        // });

        // lessonpagedata.each(function(lessonnode) {
        //     console.log(lessonnode);
        // });

        // Draw a lesson page node.


        // console.log('something');
    }


}, {
    NAME: 'pagemMove',
    ATTRS: {
        /**
         * Data for the table.
         *
         * @attribute tabledata.
         * @type Array
         * @writeOnce
         */
        lessondata: {
            value: null
        }
    }
});


// Y.namespace('M.mod_lesson-pagemmove') = function() {};
Y.namespace('M.mod_lesson.PagemMove').init = function(config) {
    return new PagemMove(config);
};