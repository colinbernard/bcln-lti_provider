<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_lti\provider;

use local_lti\provider\request;

/**
 * LTI Resource
 *
 * Represents the LTI resource which was requested.
 *
 * @package    local_lti
 * @copyright  2019 Colin Bernard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class resource {

    /** @var int The type of the resource requested. */
    protected $type;

    /** @var int The ID of the tool consumer. This is NOT the consumer key. It references the local_lti_consumer ID field. */
    protected $consumer_id;

    /** @var object A reference to the request object. */
    public $request;

    /** @var int The ID of the resource content that is being requested.. */
    protected $content_id;

    public function __construct($type, $consumer_id, $request) {
        $this->type        = $type;
        $this->consumer_id = $consumer_id;
        $this->request     = $request;
        $this->content_id  = $this->get_content_id();
    }

    /**
     * Checks if a record exists in the resource linking database table for this resource.
     * If it does, update it. If it does not, create it.
     */
    protected function update_link() {

        // Check if record exists in local_lti_resource_link table.
        if ($this->is_linked()) {

            // Update access_count and last_access fields.
            global $DB;
            $record               = $this->get_record_from_database();
            $record->access_count += 1;
            $record->last_access  = date("Y-m-d H:i:s"); // Now.
            $DB->update_record('local_lti_resource_link', $record);
            $this->update_consumer();

        } else {

            // Create new record in local_lti_resource_link.
            $this->create_link();
        }
    }

    /**
     * Update the last_access consumer field.
     * Set it to 'now'.
     *
     * @throws \dml_exception
     */
    // TODO create consumer class and link to resource.
    private function update_consumer() {
        global $DB;

        $resource_record              = $this->get_record_from_database();
        $consumer_record              = $DB->get_record('local_lti_consumer', array('id' => $resource_record->consumer), 'id');
        $consumer_record->last_access = date("Y-m-d H:i:s"); // Now.
        $DB->update_record('local_lti_consumer', $consumer_record);
    }

    /**
     * Check if this resource already exists in the local_lti_resource_link table.
     *
     * @return boolean
     */
    private function is_linked() {
        $record = $this->get_record_from_database();
        if ($record) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return the database record for this resource.
     *
     * @return object The database record object.
     */
    private function get_record_from_database() {
        global $DB;

        // Search for the record using the record_link_id and consumer_id.
        $record = $DB->get_record('local_lti_resource_link',
            array(
                'content_id' => $this->content_id,
                'consumer'   => $this->consumer_id,
                'type'       => $this->type,
            ));

        // Return the record.
        return $record;
    }

    /*
     * Inserts this resource into local_lti_resource_link table.
     */
    public function create_link() {
        global $DB;

        $now = date("Y-m-d H:i:s");

        // Insert a new record.
        $record               = new \stdClass();
        $record->type         = $this->type;
        $record->consumer     = $this->consumer_id;
        $record->content_id   = $this->content_id;
        $record->access_count = 1;
        $record->created      = $now;
        $record->last_access  = $now;
        $DB->insert_record('local_lti_resource_link', $record);
    }

    /**
     * Get the book/page ID of this resource.
     */
    private function get_content_id() {

        // Check if the request custom ID parameter is set.
        if ( ! is_null($this->request->get_parameter('custom_id'))) {
            return $this->request->get_parameter('custom_id');

            // Check for an ID parameter appended to the launch URL.
            // Canvas LMS will work this way.
        } else if ($id = optional_param('id', false, PARAM_INT)) {

            // Set the request custom parameter. This is needed for Canvas to switch book pages.
            // The optional param above will not be set when navigating pages, so will rely on the stored request data.
            $this->request->set_parameter('custom_id', $id, false);

            // Return the ID.
            return $id;
        }

        // There is no ID set.
        return null;

    }

    /**
     * To be overridden by resource type classes.
     */
    abstract public function render();
}
