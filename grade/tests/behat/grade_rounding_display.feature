@core @core_grades
Feature: We can set the number of decimal points to round to, and have it round down.
  In order to change the grade decimal number
  As a teacher
  I need to add assessments to the gradebook.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email | idnumber | alternatename |
      | teacher1 | Teacher | 1 | teacher1@example.com | t1 | Terry         |
      | student1 | Student | 1 | student1@example.com | s1 | Sally         |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Course completion"
    And I set the following fields to these values:
      | id_criteria_grade | 1 |
      | Required course grade | 55 |
    And I press "Save changes"
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Submit your online text |
      | assignsubmission_onlinetext_enabled | 1 |
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test assignment name"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student1 submission |
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I navigate to "Grades" node in "Course administration"
    And I set the field "Grade report" to "Gradebook setup"
    And I set the following settings for grade item "Test assignment name":
      | Overall decimal points | 0 |
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name | calc item |
    And I press "Save changes"
    And I set "=[[gi1]]/2" calculation for grade item "calc item" with idnumbers:
      | Test assignment name | gi1 |
    And I set the following settings for grade item "calc item":
      | Maximum grade | 50 |
      | Overall decimal points | 0 |
    And I press "Save changes"
    And I follow "Course 1"
    And I follow "Test assignment name"
    And I follow "View/grade all submissions"
    And I click on "Grade Student 1" "link" in the "Student 1" "table_row"
    And I set the following fields to these values:
      | Grade out of 100 | 49.56 |
    And I press "Save changes"
    And I press "Continue"
    And I follow "Course 1"
    And I navigate to "Grades" node in "Course administration"

  @javascript
  Scenario: Students grades are rounded according to decimal setting in each grade book report.
    When I set the field "Grade report" to "Grader report"
    Then the following should exist in the "user-grades" table:
      | -1-                | -4-       | -5-       | -6-       |
      | Student 1          | 49        | 24        | 74.34     |
    And I set the field "Grade report" to "Grade history"
    And I press "Submit"
    Then the following should exist in the "gradereport_history" table:
      | First name/Surname | Grade item                    | Revised grade | Grader    |
      | Student 1          | Course total                  | 74.34         |           |
      | Student 1          | calc item                     | 24            |           |
      | Student 1          | Test assignment name          | 49            | Teacher 1 |
    And I select "User report" from the "Grade report" singleselect
    And I select "Student 1" from the "Select all or one user" singleselect
    And the following should exist in the "generaltable" table:
      | Grade item           | Grade | Percentage | Contribution to course total |
      | Test assignment name | 49    | 49 %       | 33 %                         |
      | calc item            | 24    | 49 %       | 16 %                         |
      | Course total         | 74.34 | 49.56 %    | -                            |
    And I log out
    And I log in as "admin"
    And I am on site homepage
    And I follow "Course 1"
    And I navigate to "Grades" node in "Course administration"
    And I select "Overview report" from the "Grade report" singleselect
    And I select "Student 1" from the "Select a user" singleselect
    And the following should exist in the "overview-grade" table:
      | Course name | Grade |
      | Course 1    | 74.34 |
    And I select "Single view" from the "Grade report" singleselect
    And I select "Student 1" from the "Select user..." singleselect
    And the field "Grade for Test assignment name" matches value "49"
    And the field "Grade for calc item" matches value "24"
    And the field "Grade for Course total" matches value "74.34"
    And I select "Test assignment name" from the "Select grade item..." singleselect
    And the field "Grade for Sally (Student) 1" matches value "49"
    And I select "calc item" from the "Select grade item..." singleselect
    And the field "Grade for Sally (Student) 1" matches value "24"
    And I select "Course total" from the "Select grade item..." singleselect
    And the field "Grade for Sally (Student) 1" matches value "74.34"

    @javascript
    Scenario: Overriding a grade will round the value as expected (down).
    When I set the field "Grade report" to "Grader report"
    And I press "Turn editing on"
    And I give the grade "51.78" to the user "Student 1" for the grade item "Test assignment name"
    And I press "Save changes"
    And I press "Turn editing off"
    Then the following should exist in the "user-grades" table:
      | -1-                | -4-       | -5-       | -6-       |
      | Student 1          | 51        | 25        | 77.67     |

    @javascript
    Scenario: Course completion with a course grade is shown as expected.
    When I set the field "Grade report" to "Grader report"
    And I press "Turn editing on"
    And I give the grade "36.5" to the user "Student 1" for the grade item "Test assignment name"
    And I press "Save changes"
    And I press "Turn editing off"
    And I select "Course grade settings" from the "Grade report" singleselect
    And I set the following fields to these values:
      | Overall decimal points | 0 |
    And I press "Save changes"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "Course completion status" block
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I follow "Student 1"
    Then the following should exist in the "criteriastatus" table:
      | -1-          | -3- | -4- | -5- |
      | Course grade | 55  | 54  | No  |
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I should see "54 (55 required)"
