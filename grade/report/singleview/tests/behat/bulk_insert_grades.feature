@core @core_grades @gradereport_singleview
Feature: We can bulk insert grades for students in a course
  As a teacher
  In order to quickly grade items
  I can bulk insert values for all or empty grades.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email | idnumber | alternatename |
      | teacher1 | Teacher | 1 | teacher1@example.com | t1 | fred |
      | student1 | Student | 1 | student1@example.com | s1 | james |
      | student2 | Student | 2 | student1@example.com | s2 | holly |
      | student3 | Student | 3 | student1@example.com | s3 | anna |
      | student4 | Student | 4 | student1@example.com | s4 | zac |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
      | student4 | C1 | student |
    And the following "grade categories" exist:
      | fullname | course |
      | Sub category 1 | C1 |
    And the following "activities" exist:
      | activity | course | idnumber | name | intro |
      | assign | C1 | a1 | Test assignment one | Submit something!   |
      | assign | C1 | a2 | Test assignment two | Submit something!   |
      | assign | C1 | a3 | Test assignment three | Submit something! |
      | assign | C1 | a4 | Test assignment four | Submit nothing!    |
    And the following "activities" exist:
      | activity | course | idnumber | name | intro | gradecategory |
      | assign | C1 | a5 | Test assignment five | Submit nothing! | Sub category 1 |
      | assign | C1 | a6 | Test assignment six | Submit nothing!  | Sub category 1 |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Course 1"
    And I navigate to "Grades" node in "Course administration"
    And I navigate to "Gradebook setup" node in "Grade administration > Setup"
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name | grade item 1 |
    And I press "Save changes"
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name | calc item |
    And I press "Save changes"
    And I set "=[[gi1]]/2" calculation for grade item "calc item" with idnumbers:
      | grade item 1 | gi1 |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: I can bulk insert grades and check their override flags for grade view.
    Given I log in as "admin"
    And I navigate to "Single view" node in "Site administration > Grades > Report settings"
    And I set the following fields to these values:
      | Records per page | 2 |
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test assignment one"
    And I follow "View/grade all submissions"
    And I follow "Grade Student 1"
    And I set the following fields to these values:
      | Grade out of 100 | 50 |
    And I press "Save changes"
    And I press "Continue"
    And I follow "View gradebook"
    And I follow "Single view for Test assignment one"
    Then the field "Grade for james (Student) 1" matches value "50.00"
    And the field "Override for james (Student) 1" matches value "0"
    And I follow "Next"
    And I click on "Perform bulk insert" "checkbox"
    And I set the field "Insert value" to "1.0"
    And I press "Save"
    And I press "Continue"
    And the field "Grade for anna (Student) 3" matches value "1.00"
    And the field "Override for anna (Student) 3" matches value "1"
    And the field "Grade for zac (Student) 4" matches value "1.00"
    And the field "Override for zac (Student) 4" matches value "1"
    And I follow "Previous"
    And the field "Grade for james (Student) 1" matches value "50.00"
    And the field "Override for james (Student) 1" matches value "0"
    And the field "Grade for holly (Student) 2" matches value "1.00"
    And the field "Override for holly (Student) 2" matches value "1"
    And I click on "All grades" "option"
    And I click on "Perform bulk insert" "checkbox"
    And I set the field "Insert value" to "2.0"
    And I press "Save"
    And I press "Continue"
    And the field "Grade for james (Student) 1" matches value "2.00"
    And the field "Override for james (Student) 1" matches value "1"
    And the field "Grade for holly (Student) 2" matches value "2.00"
    And the field "Override for holly (Student) 2" matches value "1"
    And I follow "Next"
    And the field "Grade for anna (Student) 3" matches value "2.00"
    And the field "Override for anna (Student) 3" matches value "1"
    And the field "Grade for zac (Student) 4" matches value "2.00"
    And the field "Override for zac (Student) 4" matches value "1"

  @javascript
  Scenario: I can bulk insert grades and check their override flags for user view.
    Given I log in as "admin"
    And I navigate to "Single view" node in "Site administration > Grades > Report settings"
    And I set the following fields to these values:
      | Records per page | 6 |
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    # Insert a grade item that has a final grade different to the raw grade and no override.
    And I turn editing mode on
    And I add a "Database" to section "1" and I fill the form with:
      | Name              | Test database name |
      | Description       | Test               |
      | Aggregate type    | Average of ratings |
    And I add a "Text input" field to "Test database name" database and I fill the form with:
      | Field name | Test field name |
      | Field description | Test field description |
    And I follow "Templates"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I add an entry to "Test database name" database with:
      | Test field name | Student entry |
    And I press "Save and view"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test database name"
    And I follow "View single"
    And I set the field "rating" to "60"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the field "id_modgrade_point" to "80"
    And I press "Save and return to course"
    And I follow "Test assignment two"
    And I follow "View/grade all submissions"
    And I follow "Grade Student 1"
    And I set the following fields to these values:
      | Grade out of 100 | 50 |
    And I press "Save changes"
    And I press "Continue"
    And I follow "View gradebook"
    And I follow "Single view for Test assignment two"
    And I click on "Student 1" "option"
    Then the field "Grade for Test assignment two" matches value "50.00"
    And the field "Override for Test assignment two" matches value "0"
    And I follow "Next"
    And I click on "Perform bulk insert" "checkbox"
    And I click on "Empty grades" "option"
    And I set the field "Insert value" to "1.0"
    And I press "Save"
    And I press "Continue"
    And the field "Grade for Course total" matches value "104.00"
    And the field "Grade for Test assignment four" matches value "1.00"
    And the field "Override for Test assignment four" matches value "1"
    And the field "Grade for grade item 1" matches value "1.00"
    And the field "Grade for calc item" matches value "1.00"
    And the field "Override for calc item" matches value "1"
    And the field "Test database name" matches value "48.00"
    And the field "Override for Test database name" matches value "0"
    And I follow "Previous"
    And the field "Grade for Test assignment three" matches value "1.00"
    And the field "Override for Test assignment three" matches value "1"
    And the field "Grade for Test assignment two" matches value "50.00"
    And the field "Override for Test assignment two" matches value "0"
    And the field "Grade for Test assignment one" matches value "1.00"
    And the field "Override for Test assignment one" matches value "1"
    And the field "Grade for Test assignment five" matches value "1.00"
    And the field "Override for Test assignment five" matches value "1"
    And the field "Grade for Test assignment six" matches value "1.00"
    And the field "Override for Test assignment six" matches value "1"
    And the field "Grade for Category total" matches value "1.00"
    And the field "Override for Category total" matches value "1"
    And I follow "Next"
    And I click on "Perform bulk insert" "checkbox"
    And I click on "All grades" "option"
    And I set the field "Insert value" to "2.0"
    And I press "Save"
    And I press "Continue"
    And the field "Grade for Course total" matches value "16.00"
    And the field "Grade for Test assignment four" matches value "2.00"
    And the field "Override for Test assignment four" matches value "1"
    And the field "Grade for grade item 1" matches value "2.00"
    And the field "Grade for calc item" matches value "2.00"
    And the field "Override for calc item" matches value "1"
    And the field "Test database name" matches value "2.00"
    And the field "Override for Test database name" matches value "1"
    And I follow "Previous"
    And the field "Grade for Test assignment three" matches value "2.00"
    And the field "Override for Test assignment three" matches value "1"
    And the field "Grade for Test assignment two" matches value "2.00"
    And the field "Override for Test assignment two" matches value "1"
    And the field "Grade for Test assignment one" matches value "2.00"
    And the field "Override for Test assignment one" matches value "1"
    And the field "Grade for Test assignment five" matches value "2.00"
    And the field "Override for Test assignment five" matches value "1"
    And the field "Grade for Test assignment six" matches value "2.00"
    And the field "Override for Test assignment six" matches value "1"
    And the field "Grade for Category total" matches value "2.00"
    And the field "Override for Category total" matches value "1"