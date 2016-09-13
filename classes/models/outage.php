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
 * An Outage object with all information about one specific outage.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\models;

use auth_outage\outagelib;

class outage {
    /**
     * @var int Outage ID (auto generated by the DB).
     */
    public $id = null;

    /**
     * @var int Start Time timestamp.
     */
    public $starttime = null;

    /**
     * @var int Stop Time timestamp.
     */
    public $stoptime = null;

    /**
     * @var int Warning start timestamp.
     */
    public $warntime = null;

    /**
     * @var string Short description of the outage (no HTML).
     */
    public $title = null;

    /**
     * @var string Description of the outage (some HTML allowed).
     */
    public $description = null;

    /**
     * @var int Moodle User Id that created this outage.
     */
    public $createdby = null;

    /**
     * @var int Moodle User Id that last modified this outage.
     */
    public $modifiedby = null;

    /**
     * @var int Timestamp of when this outage was last modified.
     */
    public $lastmodified = null;

    /**
     * outage constructor.
     * @param object|array|null The data for the outage.
     */
    public function __construct($data = null) {
        if (is_null($data)) {
            return;
        }

        if (is_object($data) || is_array($data)) {
            outagelib::data2object($data, $this);

            // Adjust field types as needed.
            $fields = ['createdby', 'id', 'lastmodified', 'modifiedby', 'starttime', 'stoptime', 'warntime'];
            foreach ($fields as $f) {
                $this->$f = ($this->$f === null) ? null : (int)$this->$f;
            }

            return;
        }

        throw new \InvalidArgumentException('$data must be null (default), an array or an object.');
    }

    /**
     * Checks if the outage is active (in warning period or ongoing).
     * @param int|null $time Null to check if the outage is active now or another time to use as reference.
     * @return bool True if outage is ongoing or during the warning period.
     */
    public function is_active($time = null) {
        if ($time === null) {
            $time = time();
        }
        if (!is_int($time) || ($time <= 0)) {
            throw new \InvalidArgumentException('$time must be an positive int.');
        }
        if (is_null($this->warntime) || is_null($this->stoptime)) {
            return false;
        }

        return (($this->warntime <= $time) && ($time < $this->stoptime));
    }

    /**
     * Checks if the outage is happening.
     * @param int|null $time Null to check if the outage is happening now or another time to use as reference.
     * @return bool True if outage has started but not yet stopped. False otherwise including if in warning period.
     */
    public function is_ongoing($time = null) {
        if ($time === null) {
            $time = time();
        }
        if (!is_int($time) || ($time <= 0)) {
            throw new \InvalidArgumentException('$time must be an positive int.');
        }
        if (is_null($this->starttime) || is_null($this->stoptime)) {
            return false;
        }

        return (($this->starttime <= $time) && ($time < $this->stoptime));
    }

    /**
     * Get the title with properly replaced placeholders such as {{start}} and {{stop}}.
     * @return string Title.
     */
    public function get_title() {
        return $this->replace_placeholders($this->title);
    }

    /**
     * Get the description with properly replaced placeholders such as {{start}} and {{stop}}.
     * @return string Description.
     */
    public function get_description() {
        return $this->replace_placeholders($this->description);
    }

    /**
     * Returns the input string with all placeholders replaced.
     * @param $str string Input string.
     * @return string Output string.
     */
    private function replace_placeholders($str) {
        return str_replace(
            [
                '{{start}}',
                '{{stop}}',
                '{{duration}}',
            ],
            [
                userdate($this->starttime, get_string('datetimeformat', 'auth_outage')),
                userdate($this->stoptime, get_string('datetimeformat', 'auth_outage')),
                format_time($this->get_duration()),
            ],
            $str
        );
    }

    /**
     * Gets the duration of the outage (start to stop, warning not included).
     * @return int Duration in seconds.
     */
    public function get_duration() {
        return $this->stoptime - $this->starttime;
    }

    /**
     * Gets the warning duration from the outage (from warning time to start time).
     * @return int Warning duration in seconds.
     */
    public function get_warning_duration() {
        return $this->starttime - $this->warntime;
    }
}
