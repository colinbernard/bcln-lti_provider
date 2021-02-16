<?php

namespace local_lti\external;

use external_api;
use local_lti\helper\consumer;
use local_lti\provider\error;

class errors_api extends external_api
{

    const PAGE_SIZE = 10;

    /*
    |--------------------------------------------------------------------------
    | Get errors
    |--------------------------------------------------------------------------
    |
    | Get all errors (by page)
    |
    */

    public static function get_errors_parameters()
    {
        return new \external_function_parameters([
            'consumer' => new \external_value(PARAM_INT, 'Filter by consumer ID'),
            'page'     => new \external_value(PARAM_INT, 'What page of results are we on (start at 0)'),
        ]);
    }

    public static function get_errors($consumer, $page)
    {
        global $DB;

        $params = self::validate_parameters(self::get_errors_parameters(), compact('consumer', 'page'));

        $conditions = [];
        if ( ! empty($params['consumer'])) {
            $conditions['consumer'] = $params['consumer'];
        }

        $errors = $DB->get_records(error::TABLE, $conditions, 'timecreated DESC', '*',
            $params['page'] * self::PAGE_SIZE,
            self::PAGE_SIZE);

        $page_count = $DB->count_records(error::TABLE, $conditions);

        foreach ($errors as $error) {
            $error->timecreated = userdate($error->timecreated, get_string('strftimedatetime'));
            $error->message     = error::ERROR_CODES[$error->code];

            // Get the consumer name attached to this error (rather than ID)
            if ($consumer = $DB->get_record(consumer::TABLE, ['id' => $error->consumer])) {
                $error->consumer = $consumer->name;
            }
        }

        return [
            'errors'     => $errors,
            'page_count' => $page_count,
        ];
    }

    public static function get_errors_returns()
    {
        return new \external_single_structure([
            'errors'     => new \external_multiple_structure(new \external_single_structure([
                'id'          => new \external_value(PARAM_INT),
                'consumer'    => new \external_value(PARAM_TEXT, 'Consumer name'),
                'code'        => new \external_value(PARAM_TEXT, 'Error code'),
                'message'     => new \external_value(PARAM_TEXT, 'Error message'),
                'timecreated' => new \external_value(PARAM_TEXT, 'Formatted error time'),
            ])),
            'page_count' => new \external_value(PARAM_INT),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Get consumer options
    |--------------------------------------------------------------------------
    |
    | Get consumer options for the consumers dropdown on the error tab
    |
    */

    public static function get_consumer_options_parameters()
    {
        return new \external_function_parameters([]);
    }

    public static function get_consumer_options()
    {
        global $DB;

        return $DB->get_records(consumer::TABLE, [], 'name ASC', 'id,name');
    }

    public static function get_consumer_options_returns()
    {
        return new \external_multiple_structure(new \external_single_structure([
            'id'   => new \external_value(PARAM_INT),
            'name' => new \external_value(PARAM_TEXT),
        ]));
    }

}
