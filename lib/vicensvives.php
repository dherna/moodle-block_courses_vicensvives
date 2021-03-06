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

/**
 * Authentication Plugin:
 *
 * Checks against an external database.
 *
 * @package    courses_vicensvives
 * @author     CV&A Consulting
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

class vicensvives_ws {

    const WS_URL = 'http://api.vicensvivesdigital.com/rest';

    static function configured() {
        global $CFG;

        return !empty($CFG->vicensvives_sharekey) and !empty($CFG->vicensvives_sharepass);
    }

    function book($bookid) {
        return $this->call('get', 'books/' . $bookid, array('lti_info' => "true"));
    }

    function books() {
        $books = $this->call('get', 'books', array('own' => "true")) ?: array();

        foreach ($books as $key => $book) {
            if (empty($book->idBook)) {
                unset($books[$key]);
            }
            $book->isbn = isset($book->isbn) ? $book->isbn : '';
        }

        return $books;
    }

    function levels($lang) {
        $levels = $this->call('get', 'levels', array('lang' => $lang)) ?: array();
        foreach ($levels as $key => $level) {
            if (empty($level->idLevel))  {
                unset($levels[$key]);
            }
        }
        return $levels;
    }

    function licenses($book=null, $activated=null) {
        $params = array();
        if ($book !== null) {
            $params['book'] = (int) $book;
        }
        if ($activated !== null) {
            $params['activated'] = (bool) $activated;
        }
        return $this->call('get', 'licenses', $params);
    }

    function subjects($lang) {
        $subjects = $this->call('get', 'subjects', array('lang' => $lang)) ?: array();
        foreach ($subjects as $key => $subject) {
            if (empty($subject->idSubject)) {
                unset($subjects[$key]);
            }
        }
        return $subjects;
    }

    function sendtoken($token) {
        global $CFG;
        $params = array(
            'url' => $CFG->wwwroot,
            'token' => $token,
            'identifier' => get_site_identifier(),
            'serviceEndpoint' =>  $CFG->wwwroot . '/webservice/rest/server.php',
        );
        return (bool) $this->call('post', 'schools/moodle', $params);
    }

    private function call($method, $path, $params=null) {
        global $CFG;

        if (empty($CFG->vicensvives_sharekey) or empty($CFG->vicensvives_sharepass)) {
            throw new vicensvives_ws_error('wsnotconfigured');
        }

        if (empty($CFG->vicensvives_accesstoken)) {
            $this->refresh_token();
        }

        list($status, $response) = $this->curl($method, $path, $params);

        if ($status == 401) {
            $this->refresh_token();
            list($status, $response) = $this->curl($method, $path, $params);
        }

        if ($status == 200) {
            return $response;
        }

        throw new vicensvives_ws_error('wsunknownerror');
    }

    private function curl($method, $path, $params=null, $auth=true) {
        global $CFG;

        $url = !empty($CFG->vicensvives_apiurl) ? $CFG->vicensvives_apiurl : self::WS_URL;
        $url .= '/' . $path;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');

        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($params) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
            }
        } else if ($method == 'put') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($params) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
            }
        } else if ($method == 'delete') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } else {
            if ($params) {
                $url .= '?' . http_build_query($params, '', '&');
            }
        }

        // Start the header.
        $header = array();
        if ($auth) {
            $header[] = 'Authorization: Bearer ' . $CFG->vicensvives_accesstoken;
        }

        // Manage data to send token.
        if($path == 'schools/moodle') {
            $header[] = 'Content-Type: application/json';
            $data_string = json_encode($params);
            $header[] = 'Content-Length: ' . strlen($data_string);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        }

        // Add header to curl.
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_URL, $url);
        $response = json_decode(curl_exec($ch));
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->log($method, $path, $params, $status, $response);

        return array($status, $response);
    }

    private function log($method, $path, $params, $status, $response) {
        global $PAGE, $SCRIPT;

        $data = array(
            'context' => $PAGE->context,
            'other' => array(
                'script' => $SCRIPT,
                'method' => $method,
                'path' => $path,
                'params' => $params,
                'status' => $status,
                'message' => !empty($response->msg) ? $response->msg : '',
            ),
        );

        $event = \block_courses_vicensvives\event\webservice_called::create($data);
        $event->trigger();
    }

    private function refresh_token() {
        global $CFG;

        $params = array(
            'client_id' => $CFG->vicensvives_sharekey,
            'client_secret' => $CFG->vicensvives_sharepass,
            'grant_type' => 'client_credentials',
        );

        list($status, $response) = $this->curl('post', 'oauth', $params, false);

        if ($status == 200 and !empty($response->access_token)) {
            set_config('vicensvives_accesstoken', $response->access_token);
        } else {
            unset_config('vicensvives_accesstoken');
            throw new vicensvives_ws_error('wsauthfailed');
        }
    }
}

class vicensvives_ws_error extends moodle_exception {
    function __construct($errorcode, $a=null, $debuginfo=null) {
        parent::__construct($errorcode, 'block_courses_vicensvives', '', $a, $debuginfo=null);
    }
}
