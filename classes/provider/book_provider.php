<?php

namespace local_lti\provider;
use \local_lti\imsglobal\lti\oauth;
use local_lti\provider\error;

class book_provider {

  private static function get_book_id() {
    global $DB;

    $id = book_provider::get_course_module_id();

    try {
      $cm = get_coursemodule_from_id('book', $id, 0, false, MUST_EXIST);
      $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
      $book = $DB->get_record('book', array('id'=>$cm->instance), '*', MUST_EXIST);
    } catch(\Exception $e) {
      error::render(get_string('error_book_id', 'local_lti'));
      return null;
    }


    return $book->id;
  }

  private static function get_course_module_id() {
    $request = oauth\request::from_request();
    $product_family_code = $request->get_parameter('tool_consumer_info_product_family_code');

    $id = null;

    if ($product_family_code === "moodle") {
      $id = $request->get_parameter('custom_id');
    } else if ($product_family_code === "canvas") {
      $id = required_param('id', PARAM_INT);
    }

    return $id;
  }

  public static function render() {
    global $PAGE;

    // Get the plugin renderer.
    $renderer = $PAGE->get_renderer('local_lti');

    // Render the book.
    if ($book_id = book_provider::get_book_id()) {
      $book = new \local_lti\output\book($book_id);
      echo $renderer->render($book);
      return true;
    }
    return false;
  }
}
