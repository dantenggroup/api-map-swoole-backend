<?php

namespace App\Repositories\Corn;

use App\Repositories\Corn\Language\BaseLanguage;
use Cron\CronExpression;
use Exception;

final class CronSchedule
{
// The actual minutes, hours, daysOfMonth, months, daysOfWeek and years selected by the provided cron specification.
    private $minutes = array();
    private $hours = array();
    private $daysOfMonth = array();
    private $months = array();
    private $daysOfWeek = array();
    private $years = array();
// The original cron specification in compiled form.
    private $cronMinutes = array();
    private $cronHours = array();
    private $cronDaysOfMonth = array();
    private $cronMonths = array();
    private $cronDaysOfWeek = array();
    private $cronYears = array();
// The language table
    private $lang = false;
    /**
     * Minimum and maximum years to cope with the Year 2038 problem in UNIX.
     * We run PHP which most likely runs on a UNIX environment so we
     * must assume vulnerability.
     */
    // Must match date range supported by date(). See also: http://en.wikipedia.org/wiki/Year_2038_problem
    protected $RANGE_YEARS_MIN = 1970;
    // Must match date range supported by date(). See also: http://en.wikipedia.org/wiki/Year_2038_problem
    protected $RANGE_YEARS_MAX = 2037;

    /**
     * Function:    __construct
     *
     * Description:    Performs only base initialization, including language initialization.
     *
     * Parameters:    $language            The language_code of the chosen language.
     * @param BaseLanguage $language
     */
    public function __construct(BaseLanguage $language)
    {
        $this->initLang($language);
    }
//
// Function:    fromCronString
//
// Description:    Creates a new Schedule object based on a Cron specification.
//
// Parameters:    $cronSpec            A string containing a cron specification.
//                $language            The language to use to create a natural language representation of the string
//
// Result:        A new Schedule object. An \Exception is thrown if the specification is invalid.
//
    /**
     * @param BaseLanguage $language
     * @param CronExpression $cronSpec
     * @return CronSchedule
     * @throws Exception
     */
    final public static function fromCronExpression(CronExpression $cronSpec, BaseLanguage $language)
    {
// Split input liberal. Single or multiple Spaces, Tabs and Newlines are all allowed as separators.
        if (count($elements = preg_split('/\s+/', $cronSpec->getExpression())) < 5) {
            throw new Exception('Invalid specification.');
        }
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Named ranges in cron entries
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $arrMonths = ['JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MAY' => 5, 'JUN' => 6,
            'JUL' => 7, 'AUG' => 8, 'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12
        ];
        $arrDaysOfWeek = array('MON' => 1, 'TUE' => 2, 'WED' => 3, 'THU' => 4, 'FRI' => 5, 'SAT' => 6, 'SUN' => 0);
// Translate the cron specification into arrays that hold specifications of the actual dates
        $newCron = new CronSchedule($language);
        $newCron->cronMinutes = $newCron->cronInterpret($elements[0], 0, 59, array(), 'minutes');
        $newCron->cronHours = $newCron->cronInterpret($elements[1], 0, 23, array(), 'hours');
        $newCron->cronDaysOfMonth = $newCron->cronInterpret($elements[2], 1, 31, array(), 'daysOfMonth');
        $newCron->cronMonths = $newCron->cronInterpret($elements[3], 1, 12, $arrMonths, 'months');
        $newCron->cronDaysOfWeek = $newCron->cronInterpret($elements[4], 0, 6, $arrDaysOfWeek, 'daysOfWeek');
        $newCron->minutes = $newCron->cronCreateItems($newCron->cronMinutes);
        $newCron->hours = $newCron->cronCreateItems($newCron->cronHours);
        $newCron->daysOfMonth = $newCron->cronCreateItems($newCron->cronDaysOfMonth);
        $newCron->months = $newCron->cronCreateItems($newCron->cronMonths);
        $newCron->daysOfWeek = $newCron->cronCreateItems($newCron->cronDaysOfWeek);
        if (isset($elements[5])) {
            $newCron->cronYears = $newCron->cronInterpret(
                $elements[5],
                $newCron->RANGE_YEARS_MIN,
                $newCron->RANGE_YEARS_MAX,
                [],
                'years'
            );
            $newCron->years = $newCron->cronCreateItems($newCron->cronYears);
        } else {
            $newCron->cronYears = $newCron->cronInterpret(
                '*',
                $newCron->RANGE_YEARS_MIN,
                $newCron->RANGE_YEARS_MAX,
                [],
                'years'
            );
            $newCron->years = $newCron->cronCreateItems($newCron->cronYears);
        }
        return $newCron;
    }

    /**
     * Function:    cronInterpret
     *
     * Description:    Interprets a single field from a cron specification.
     *  Throws an \Exception if the specification is in some way invalid.
     *
     * Parameters:    $specification        The actual text from the spefication, such as 12-38/3
     *                $rangeMin            The lowest value for specification.
     *                $rangeMax            The highest value for specification
     *                $namesItems            A key/value pair where value is a value
     *                                       between $rangeMin and $rangeMax and key is the name for that value.
     *                $errorName            The name of the category to use in case of an error.
     *
     * Result:        An array with entries, each of which is an array with the following fields:
     *                'number1'            The first number of the range or the number specified
     *                'number2'            The second number of the range if a range is specified
     *                'hasInterval'        true if a range is specified. false otherwise
     *                'interval'            The interval if a range is specified.
     */
    /**
     * @param $specification
     * @param $rangeMin
     * @param $rangeMax
     * @param $namedItems
     * @param $errorName
     * @return array
     * @throws Exception
     */
    final private function cronInterpret($specification, $rangeMin, $rangeMax, $namedItems, $errorName)
    {
        if ((!is_string($specification)) && (!(is_int($specification)))) {
            throw new Exception('Invalid specification.');
        }
// Multiple values, separated by comma
        $specs = array();
        $specs['rangeMin'] = $rangeMin;
        $specs['rangeMax'] = $rangeMax;
        $specs['elements'] = array();
        $arrSegments = explode(',', $specification);
        foreach ($arrSegments as $segment) {
            $hasRange = (($posRange = strpos($segment, '-')) !== false);
            $hasInterval = (($posIncrement = strpos($segment, '/')) !== false);
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Check: Increment without range is invalid
//if(!$hasRange && $hasInterval)              throw new \Exception("Invalid Range ($errorName).");
// Check: Increment must be final specification
            if ($hasRange && $hasInterval) {
                if ($posIncrement < $posRange) {
                    throw new \Exception("Invalid order ($errorName).");
                }
            }
// GetSegments
            $segmentNumber1 = $segment;
            $segmentNumber2 = '';
            $segmentIncrement = '';
            $intIncrement = 1;
            if ($hasInterval) {
                $segmentNumber1 = substr($segment, 0, $posIncrement);
                $segmentIncrement = substr($segment, $posIncrement + 1);
            }
            if ($hasRange) {
                $segmentNumber2 = substr($segmentNumber1, $posRange + 1);
                $segmentNumber1 = substr($segmentNumber1, 0, $posRange);
            }
// Get and validate first value in range
            if ($segmentNumber1 == '*') {
                $intNumber1 = $rangeMin;
                $intNumber2 = $rangeMax;
                $hasRange = true;
            } else {
                if (array_key_exists(strtoupper($segmentNumber1), $namedItems)) {
                    $segmentNumber1 = $namedItems[strtoupper($segmentNumber1)];
                }
                if (((string)($intNumber1 = (int)$segmentNumber1)) != $segmentNumber1) {
                    throw new \Exception("Invalid symbol ($errorName).");
                }
                if (($intNumber1 < $rangeMin) || ($intNumber1 > $rangeMax)) {
                    throw new \Exception("Out of bounds ($errorName).");
                }
// Get and validate second value in range
                if ($hasRange) {
                    if (array_key_exists(strtoupper($segmentNumber2), $namedItems)) {
                        $segmentNumber2 = $namedItems[strtoupper($segmentNumber2)];
                    }
                    if (((string)($intNumber2 = (int)$segmentNumber2)) != $segmentNumber2) {
                        throw new \Exception("Invalid symbol ($errorName).");
                    }
                    if (($intNumber2 < $rangeMin) || ($intNumber2 > $rangeMax)) {
                        throw new \Exception("Out of bounds ($errorName).");
                    }
                    if ($intNumber1 > $intNumber2) {
                        throw new \Exception("Invalid range ($errorName).");
                    }
                }
            }
// Get and validate increment
            if ($hasInterval) {
                if (($intIncrement = (int)$segmentIncrement) != $segmentIncrement) {
                    throw new \Exception("Invalid symbol ($errorName).");
                }
                if (($intIncrement < 1) || ($intIncrement > $rangeMax)) {
                    throw new \Exception("Out of bounds ($errorName).");
                }
            }
// Apply range and increment
            $elem = array();
            $elem['number1'] = $intNumber1;
            $elem['hasInterval'] = $hasRange;
            if ($hasRange) {
                $elem['number2'] = $intNumber2;
                $elem['interval'] = $intIncrement;
            }
            $specs['elements'][] = $elem;
        }
        return $specs;
    }
//
// Function:    cronCreateItems
//
// Description:    Uses the interpreted cron specification of a single item
// from a cron specification to create an array with keys that match the
//                selected items.
//
// Parameters:    $cronInterpreted    The interpreted specification
//
// Result:        An array where each key identifies a matching entry.
// E.g. the cron specification */10 for minutes will yield an array
//                [0] => 1
//                [10] => 1
//                [20] => 1
//                [30] => 1
//                [40] => 1
//                [50] => 1
//
    final private function cronCreateItems($cronInterpreted)
    {
        $items = array();
        foreach ($cronInterpreted['elements'] as $elem) {
            if (!$elem['hasInterval']) {
                $items[$elem['number1']] = true;
            } else {
                for ($number = $elem['number1']; $number <= $elem['number2']; $number += $elem['interval']) {
                    $items[$number] = true;
                }
            }
        }
        ksort($items);
        return $items;
    }
//
// Function:    dtFromParameters
//
// Description:    Transforms a flexible parameter passing of a datetime specification into an internally used array.
//
// Parameters:
//  $time  If a string interpreted as a datetime string in the YYYY-MM-DD HH:II format and other parameters ignored.
//  If an array $minute, $hour, $day, $month and $year are passed as keys 0-4 and other parameters ignored.
//  If a string, interpreted as unix time.
//  If omitted or specified false, defaults to the current time.
//
// Result: An array with indices 0-4 holding the actual interpreted values for $minute, $hour, $day, $month and $year.
//
    final private function dtFromParameters($time = false)
    {
        if ($time === false) {
            $arrTime = getDate();
            return array($arrTime['minutes'], $arrTime['hours'], $arrTime['mday'], $arrTime['mon'], $arrTime['year']);
        } elseif (is_array($time)) {
            return $time;
        } elseif (is_string($time)) {
            $arrTime = getDate(strtotime($time));
            return array($arrTime['minutes'], $arrTime['hours'], $arrTime['mday'], $arrTime['mon'], $arrTime['year']);
        } elseif (is_int($time)) {
            $arrTime = getDate($time);
            return array($arrTime['minutes'], $arrTime['hours'], $arrTime['mday'], $arrTime['mon'], $arrTime['year']);
        } else {
            $arrTime = getDate();
            return array($arrTime['minutes'], $arrTime['hours'], $arrTime['mday'], $arrTime['mon'], $arrTime['year']);
        }
    }

    final private function dtAsString($arrDt)
    {
        if ($arrDt === false) {
            return false;
        }
        return $arrDt[4] . '-' . (strlen($arrDt[3]) == 1 ? '0' : '') . $arrDt[3] .
            '-' . (strlen($arrDt[2]) == 1 ? '0' : '') . $arrDt[2] . ' ' . (strlen($arrDt[1]) == 1 ? '0' : '') .
            $arrDt[1] . ':' . (strlen($arrDt[0]) == 1 ? '0' : '') . $arrDt[0] . ':00';
    }
//
// Function:    match
//
// Description:    Returns true if the specified date and time corresponds to a scheduled point in time.
// false otherwise.
//
// Parameters:
//  $time  If a string interpreted as a datetime string in the YYYY-MM-DD HH:II format and other parameters ignored.
//  If an array $minute, $hour, $day, $month and $year are passed as keys 0-4 and other parameters ignored.
//  If a string, interpreted as unix time.
//  If omitted or specified false, defaults to the current time.
//
// Result:        true if the schedule matches the specified datetime. false otherwise.
//
    final public function match($time = false)
    {
// Convert parameters to array datetime
        $arrDT = $this->dtFromParameters($time);
// Verify match
// Years
        if (!array_key_exists($arrDT[4], $this->years)) {
            return false;
        }
// Day of week
        if (!array_key_exists(date(
            'w',
            strtotime($arrDT[4] . '-' . $arrDT[3] . '-' . $arrDT[2])
        ), $this->daysOfWeek)) {
            return false;
        }
// Month
        if (!array_key_exists($arrDT[3], $this->months)) {
            return false;
        }
// Day of month
        if (!array_key_exists($arrDT[2], $this->daysOfMonth)) {
            return false;
        }
// Hours
        if (!array_key_exists($arrDT[1], $this->hours)) {
            return false;
        }
// Minutes
        if (!array_key_exists($arrDT[0], $this->minutes)) {
            return false;
        }
        return true;
    }

    final public function next($time = false)
    {
// Convert parameters to array datetime
        $arrDT = $this->dtFromParameters($time);
        while (1) {
// Verify the current date is in range. If not, move into range and consider this the next position
            if (!array_key_exists($arrDT[4], $this->years)) {
                if (($arrDT[4] = $this->getEarliestItem($this->years, $arrDT[4], false)) === false) {
                    return false;
                }
                $arrDT[3] = $this->getEarliestItem($this->months);
                $arrDT[2] = $this->getEarliestItem($this->daysOfMonth);
                $arrDT[1] = $this->getEarliestItem($this->hours);
                $arrDT[0] = $this->getEarliestItem($this->minutes);
                break;
            } elseif (!array_key_exists($arrDT[3], $this->months)) {
                $arrDT[3] = $this->getEarliestItem($this->months, $arrDT[3]);
                $arrDT[2] = $this->getEarliestItem($this->daysOfMonth);
                $arrDT[1] = $this->getEarliestItem($this->hours);
                $arrDT[0] = $this->getEarliestItem($this->minutes);
                break;
            } elseif (!array_key_exists($arrDT[2], $this->daysOfMonth)) {
                $arrDT[2] = $this->getEarliestItem($this->daysOfMonth, $arrDT[2]);
                $arrDT[1] = $this->getEarliestItem($this->hours);
                $arrDT[0] = $this->getEarliestItem($this->minutes);
                break;
            } elseif (!array_key_exists($arrDT[1], $this->hours)) {
                $arrDT[1] = $this->getEarliestItem($this->hours, $arrDT[1]);
                $arrDT[0] = $this->getEarliestItem($this->minutes);
                break;
            } elseif (!array_key_exists($arrDT[1], $this->hours)) {
                $arrDT[0] = $this->getEarliestItem($this->minutes, $arrDT[0]);
                break;
            }
// Advance minute, hour, date, month and year while overflowing.
            $daysInThisMonth = date('t', strtotime($arrDT[4] . '-' . $arrDT[3]));
            if ($this->advanceItem($this->minutes, 0, 59, $arrDT[0])) {
                if ($this->advanceItem($this->hours, 0, 23, $arrDT[1])) {
                    if ($this->advanceItem($this->daysOfMonth, 0, $daysInThisMonth, $arrDT[2])) {
                        if ($this->advanceItem($this->months, 1, 12, $arrDT[3])) {
                            if ($this->advanceItem(
                                $this->years,
                                $this->RANGE_YEARS_MIN,
                                $this->RANGE_YEARS_MAX,
                                $arrDT[4]
                            )) {
                                return false;
                            }
                        }
                    }
                }
            }
            break;
        }
// If Datetime now points to a day that is schedule then return.
        $dayOfWeek = date('w', strtotime($this->dtAsString($arrDT)));
        if (array_key_exists($dayOfWeek, $this->daysOfWeek)) {
            return $arrDT;
        }
// Otherwise move to next scheduled date
        return $this->next($arrDT);
    }

    final public function nextAsString($time = false)
    {
        return $this->dtAsString($this->next($time));
    }

    final public function nextAsTime($time = false)
    {
        return strtotime($this->dtAsString($this->next($time)));
    }
//
// Function:    advanceItem
//
// Description:    Advances the current item to the next one (the next minute, the next hour, etc.).
//
// Parameters:    $arrItems            A reference to the collection in which to advance.
//                $rangeMin            The lowest possible value for $current.
//                $rangeMax            The highest possible value for $current
//                $current            The index that is being incremented.
//
// Result:        false if current did not overflow (reset back to the earliest possible value). true if it did.
//
    final private function advanceItem($arrItems, $rangeMin, $rangeMax, & $current)
    {
// Advance pointer
        $current++;
// If still before start, move to earliest
        if ($current < $rangeMin) {
            $current = $this->getEarliestItem($arrItems);
        }
// Parse items until found or overflow
        for (; $current <= $rangeMax; $current++) {
            if (array_key_exists($current, $arrItems)) {
                return false;
            }
        } // We did not overflow
// Or overflow
        $current = $this->getEarliestItem($arrItems);
        return true;
    }
//
// Function:    getEarliestItem
//
// Description:    Retrieves the earliest item in a collection, e.g. the earliest minute or the earliest month.
//
// Parameters:    $arrItems            A reference to the collection in which to search.
//                $afterItem            The highest index that is to be skipped.
//
    final private function getEarliestItem($arrItems, $afterItem = false, $allowOverflow = true)
    {
// If no filter is specified, return the earliest listed item.
        if ($afterItem === false) {
            reset($arrItems);
            return key($arrItems);
        }
// Or parse until we passed $afterItem
        foreach ($arrItems as $key => $value) {
            if ($key > $afterItem) {
                return $key;
            }
        }
// If still nothing found, we may have exhausted our options.
        if (!$allowOverflow) {
            return false;
        }
        reset($arrItems);
        return key($arrItems);
    }
//
// Function:    previous
//
// Description:    Acquires the first scheduled datetime before the provided one.
//
// Parameters:
//  $time If a string interpreted as a datetime string in the YYYY-MM-DD HH:II format and other parameters ignored.
//  If an array $minute, $hour, $day, $month and $year are passed as keys 0-4 and other parameters ignored.
//  If a string, interpreted as unix time.
//  If omitted or specified false, defaults to the current time.
//
// Result:        An array with the following keys:
//                0                    Previous scheduled minute
//                1                    Previous scheduled hour
//                2                    Previous scheduled date
//                3                    Previous scheduled month
//                4                    Previous scheduled year
//
    final public function previous($time = false)
    {
// Convert parameters to array datetime
        $arrDT = $this->dtFromParameters($time);
        while (1) {
// Verify the current date is in range. If not, move into range and consider this the previous position
            if (!array_key_exists($arrDT[4], $this->years)) {
                if (($arrDT[4] = $this->getLatestItem($this->years, $arrDT[4], false)) === false) {
                    return false;
                }
                $arrDT[3] = $this->getLatestItem($this->months);
                $arrDT[2] = $this->getLatestItem($this->daysOfMonth);
                $arrDT[1] = $this->getLatestItem($this->hours);
                $arrDT[0] = $this->getLatestItem($this->minutes);
                break;
            } elseif (!array_key_exists($arrDT[3], $this->months)) {
                $arrDT[3] = $this->getLatestItem($this->months, $arrDT[3]);
                $arrDT[2] = $this->getLatestItem($this->daysOfMonth);
                $arrDT[1] = $this->getLatestItem($this->hours);
                $arrDT[0] = $this->getLatestItem($this->minutes);
                break;
            } elseif (!array_key_exists($arrDT[2], $this->daysOfMonth)) {
                $arrDT[2] = $this->getLatestItem($this->daysOfMonth, $arrDT[2]);
                $arrDT[1] = $this->getLatestItem($this->hours);
                $arrDT[0] = $this->getLatestItem($this->minutes);
                break;
            } elseif (!array_key_exists($arrDT[1], $this->hours)) {
                $arrDT[1] = $this->getLatestItem($this->hours, $arrDT[1]);
                $arrDT[0] = $this->getLatestItem($this->minutes);
                break;
            } elseif (!array_key_exists($arrDT[1], $this->hours)) {
                $arrDT[0] = $this->getLatestItem($this->minutes, $arrDT[0]);
                break;
            }
// Recede minute, hour, date, month and year while overflowing.
            $daysInPreviousMonth = date('t', strtotime('-1 month', strtotime($arrDT[4] . '-' . $arrDT[3])));
            if ($this->recedeItem($this->minutes, 0, 59, $arrDT[0])) {
                if ($this->recedeItem($this->hours, 0, 23, $arrDT[1])) {
                    if ($this->recedeItem($this->daysOfMonth, 0, $daysInPreviousMonth, $arrDT[2])) {
                        if ($this->recedeItem($this->months, 1, 12, $arrDT[3])) {
                            if ($this->recedeItem(
                                $this->years,
                                $this->RANGE_YEARS_MIN,
                                $this->RANGE_YEARS_MAX,
                                $arrDT[4]
                            )) {
                                return false;
                            }
                        }
                    }
                }
            }
            break;
        }
// If Datetime now points to a day that is schedule then return.
        $dayOfWeek = date('w', strtotime($this->dtAsString($arrDT)));
        if (array_key_exists($dayOfWeek, $this->daysOfWeek)) {
            return $arrDT;
        }
// Otherwise move to next scheduled date
        return $this->previous($arrDT);
    }

    final public function previousAsString($time = false)
    {
        return $this->dtAsString($this->previous($time));
    }

    final public function previousAsTime($time = false)
    {
        return strtotime($this->dtAsString($this->previous($time)));
    }
//
// Function:    recedeItem
//
// Description:    Recedes the current item to the previous one (the previous minute, the previous hour, etc.).
//
// Parameters:    $arrItems            A reference to the collection in which to recede.
//                $rangeMin            The lowest possible value for $current.
//                $rangeMax            The highest possible value for $current
//                $current            The index that is being decremented.
//
// Result:        false if current did not overflow (reset back to the highest possible value). true if it did.
//
    final private function recedeItem($arrItems, $rangeMin, $rangeMax, & $current)
    {
// Recede pointer
        $current--;
// If still above highest, move to highest
        if ($current > $rangeMax) {
            $current = $this->getLatestItem($arrItems, $rangeMax + 1);
        }
// Parse items until found or overflow
        for (; $current >= $rangeMin; $current--) {
            if (array_key_exists($current, $arrItems)) {
                return false;
            }
        } // We did not overflow
// Or overflow
        $current = $this->getLatestItem($arrItems, $rangeMax + 1);
        return true;
    }
//
// Function:    getLatestItem
//
// Description:    Retrieves the latest item in a collection, e.g. the latest minute or the latest month.
//
// Parameters:    $arrItems            A reference to the collection in which to search.
//                $beforeItem            The lowest index that is to be skipped.
//
    final private function getLatestItem($arrItems, $beforeItem = false, $allowOverflow = true)
    {
// If no filter is specified, return the latestlisted item.
        if ($beforeItem === false) {
            end($arrItems);
            return key($arrItems);
        }
// Or parse until we passed $beforeItem
        end($arrItems);
        do {
            if (($key = key($arrItems)) < $beforeItem) {
                return $key;
            }
        } while (prev($arrItems));
// If still nothing found, we may have exhausted our options.
        if (!$allowOverflow) {
            return false;
        }
        end($arrItems);
        return key($arrItems);
    }
//
// Function:
//
// Description:
//
// Parameters:
//
// Result:
//
    final private function getClass($spec)
    {
        if (!$this->classIsSpecified($spec)) {
            return '0';
        }
        if ($this->classIsSingleFixed($spec)) {
            return '1';
        }
        return '2';
    }
//
// Function:
//
// Description:
//    Returns true if the Cron Specification is specified.
// false otherwise. This is true if the specification has more than one entry
//                or is anything than the entire approved range ("*").
//
// Parameters:
//
// Result:
//
    final private function classIsSpecified($spec)
    {
        if ($spec['elements'][0]['hasInterval'] == false) {
            return true;
        }
        if ($spec['elements'][0]['number1'] != $spec['rangeMin']) {
            return true;
        }
        if ($spec['elements'][0]['number2'] != $spec['rangeMax']) {
            return true;
        }
        if ($spec['elements'][0]['interval'] != 1) {
            return true;
        }
        return false;
    }
//
// Function:
//
// Description:    Returns true if the Cron Specification is specified
// as a single value. false otherwise. This is true only if there is only
//                one entry and the entry is only a single number (e.g. "10")
//
// Parameters:
//
// Result:
//
    final private function classIsSingleFixed($spec)
    {
        return (count($spec['elements']) == 1) && (!$spec['elements'][0]['hasInterval']);
    }

    final private function initLang(BaseLanguage $language)
    {
        $this->lang = $language->map();
    }

    final private function natLangPad2($number)
    {
        return (strlen($number) == 1 ? '0' : '') . $number;
    }

    private function natLangApply($id, $p1 = false, $p2 = false, $p3 = false, $p4 = false, $p5 = false, $p6 = false)
    {
        $txt = $this->lang[$id];
        if ($p1 !== false) {
            $txt = str_replace('@1', $p1, $txt);
        }
        if ($p2 !== false) {
            $txt = str_replace('@2', $p2, $txt);
        }
        if ($p3 !== false) {
            $txt = str_replace('@3', $p3, $txt);
        }
        if ($p4 !== false) {
            $txt = str_replace('@4', $p4, $txt);
        }
        if ($p5 !== false) {
            $txt = str_replace('@5', $p5, $txt);
        }
        if ($p6 !== false) {
            $txt = str_replace('@6', $p6, $txt);
        }
        return $txt;
    }
//
// Function:    natLangRange
//
// Description:    Converts a range into natural language
//
// Parameters:
//
// Result:
//
    final private function natLangRange($spec, $entryFunction, $p1 = false)
    {
        $arrIntervals = array();
        foreach ($spec['elements'] as $elem) {
            $arrIntervals[] = call_user_func($entryFunction, $elem, $p1);
        }
        $txt = "";
        for ($index = 0; $index < count($arrIntervals); $index++) {
            $txt .= ($index == 0 ? '' : ($index == (count($arrIntervals) - 1) ? ' ' .
                    $this->natLangApply('separator_and') . ' ' : ', ')) . $arrIntervals[$index];
        }
        return $txt;
    }
//
// Function:    natLangElementMinute
//
// Description:    Converts an entry from the minute specification to natural language.
//
    final private function natLangElementMinute($elem)
    {
        if (!$elem['hasInterval']) {
            if ($elem['number1'] == 0) {
                return $this->natLangApply('elemMin: at_the_hour');
            } else {
                return $this->natLangApply('elemMin: after_the_hour_every_X_minute' .
                    ($elem['number1'] == 1 ? '' : '_plural'), $elem['number1']);
            }
        }
        $txt = $this->natLangApply('elemMin: every_consecutive_minute' .
            ($elem['interval'] == 1 ? '' : '_plural'), $elem['interval']);
        if (($elem['number1'] != $this->cronMinutes['rangeMin']) ||
            ($elem['number2'] != $this->cronMinutes['rangeMax'])
        ) {
            $txt .= ' (' . $this->natLangApply('elemMin: between_X_and_Y', $this->natLangApply('ordinal: ' .
                    $elem['number1']), $this->natLangApply('ordinal: ' . $elem['number2'])) . ')';
        }
        return $txt;
    }
//
// Function:    natLangElementHour
//
// Description:    Converts an entry from the hour specification to natural language.
//
    final private function natLangElementHour($elem, $asBetween)
    {
        if (!$elem['hasInterval']) {
            if ($asBetween) {
                return $this->natLangApply(
                    'elemHour: between_X:00_and_Y:59',
                    $this->natLangPad2($elem['number1']),
                    $this->natLangPad2($elem['number1'])
                );
            } else {
                return $this->natLangApply('elemHour: past_X:00', $this->natLangPad2($elem['number1']));
            }
        }
        if ($asBetween) {
            $txt = $this->natLangApply('elemHour: in_the_60_minutes_past_' .
                ($elem['interval'] == 1 ? '' : '_plural'), $elem['interval']);
        } else {
            $txt = $this->natLangApply('elemHour: past_every_consecutive_' .
                ($elem['interval'] == 1 ? '' : '_plural'), $elem['interval']);
        }
        if (($elem['number1'] != $this->cronHours['rangeMin']) ||
            ($elem['number2'] != $this->cronHours['rangeMax'])
        ) {
            $txt .= ' (' .
                $this->natLangApply('elemHour: between_X:00_and_Y:59', $elem['number1'], $elem['number2']) . ')';
        }
        return $txt;
    }
//
// Function:    natLangElementDayOfMonth
//
// Description:    Converts an entry from the day of month specification to natural language.
//
    final private function natLangElementDayOfMonth($elem)
    {
        if (!$elem['hasInterval']) {
            return $this->natLangApply('elemDOM: the_X', $this->natLangApply('ordinal: ' . $elem['number1']));
        }
        $txt = $this->natLangApply('elemDOM: every_consecutive_day' .
            ($elem['interval'] == 1 ? '' : '_plural'), $elem['interval']);
        if (($elem['number1'] != $this->cronHours['rangeMin']) ||
            ($elem['number2'] != $this->cronHours['rangeMax'])
        ) {
            $txt .= ' (' . $this->natLangApply('elemDOM: between_the_Xth_and_Yth', $this->natLangApply('ordinal: ' .
                    $elem['number1']), $this->natLangApply('ordinal: ' . $elem['number2'])) . ')';
        }
        return $txt;
    }
//
// Function:    natLangElementDayOfMonth
//
// Description:    Converts an entry from the month specification to natural language.
//
    final private function natLangElementMonth($elem)
    {
        if (!$elem['hasInterval']) {
            return $this->natLangApply('elemMonth: every_X', $this->natLangApply('month: ' . $elem['number1']));
        }
        $txt = $this->natLangApply('elemMonth: every_consecutive_month' .
            ($elem['interval'] == 1 ? '' : '_plural'), $elem['interval']);
        if (($elem['number1'] != $this->cronMonths['rangeMin']) ||
            ($elem['number2'] != $this->cronMonths['rangeMax'])
        ) {
            $txt .= ' (' . $this->natLangApply('elemMonth: between_X_and_Y', $this->natLangApply('month: ' .
                    $elem['number1']), $this->natLangApply('month: ' . $elem['number2'])) . ')';
        }
        return $txt;
    }
//
// Function:    natLangElementYear
//
// Description:    Converts an entry from the year specification to natural language.
//
    final private function natLangElementYear($elem)
    {
        if (!$elem['hasInterval']) {
            return $elem['number1'];
        }
        $txt = $this->natLangApply('elemYear: every_consecutive_year' .
            ($elem['interval'] == 1 ? '' : '_plural'), $elem['interval']);
        if (($elem['number1'] != $this->cronMonths['rangeMin']) ||
            ($elem['number2'] != $this->cronMonths['rangeMax'])
        ) {
            $txt .= ' (' . $this->natLangApply('elemYear: from_X_through_Y', $elem['number1'], $elem['number2']) . ')';
        }
        return $txt;
    }
//
// Function:    asNaturalLanguage
//
// Description:    Returns the current cron specification in natural language.
//
// Parameters:    None
//
// Result:        A string containing a natural language text.
//
    final public function asNaturalLanguage()
    {
        $switchDaysOfWeekAreExcluding = true;
// Generate Time String
        $txtMinutes = array();
        $txtMinutes[0] = $this->natLangApply('elemMin: every_minute');
        $txtMinutes[1] = $this->natLangElementMinute($this->cronMinutes['elements'][0]);
        $txtMinutes[2] = $this->natLangRange($this->cronMinutes, array($this, 'natLangElementMinute'));
        $txtHours = array();
        $txtHours[0] = $this->natLangApply('elemHour: past_every_hour');
        $txtHours[1] = array();
        $txtHours[1]['between'] = $this->natLangRange($this->cronHours, array($this, 'natLangElementHour'), true);
        $txtHours[1]['past'] = $this->natLangRange($this->cronHours, array($this, 'natLangElementHour'), false);
        $txtHours[2] = array();
        $txtHours[2]['between'] = $this->natLangRange($this->cronHours, array($this, 'natLangElementHour'), true);
        $txtHours[2]['past'] = $this->natLangRange($this->cronHours, array($this, 'natLangElementHour'), false);
        $classMinutes = $this->getClass($this->cronMinutes);
        $classHours = $this->getClass($this->cronHours);
        switch ($classMinutes . $classHours) {
// Special case: Unspecified date + Unspecified month
//
// Rule: The language for unspecified fields is omitted if a more detailed field has already been explained.
//
// The minutes field always yields an explaination,
// at the very least in the form of 'every minute'. This rule states that if the
// hour is not specified, it can be omitted because 'every minute' is already sufficiently clear.
//
            case '00':
                $txtTime = $txtMinutes[0];
                break;
// Special case: Fixed minutes and fixed hours
//
// The default writing would be something like 'every 20 minutes past 04:00',
// but the more common phrasing would be: At 04:20.
//
// We will switch ForceDateExplaination on, so that even a non-specified date yields an explaination (e.g. 'every day')
//
            case '11':
                $txtTime = $this->natLangApply(
                    'elemMin: at_X:Y',
                    $this->natLangPad2($this->cronHours['elements'][0]['number1']),
                    $this->natLangPad2($this->cronMinutes['elements'][0]['number1'])
                );
                break;
// Special case: Between :00 and :59
//
// If hours are specified, but minutes are not,
// then the minutes string will yield something like 'every minute'. We must the
// differentiate the hour specification because the minutes specification does not relate to all minutes past the hour,
// but only to those minutes between :00 and :59
//
// We will switch ForceDateExplaination on, so that even a non-specified date yields an explaination (e.g. 'every day')
//
            case '01':
            case '02':
                $txtTime = $txtMinutes[$classMinutes] . ' ' . $txtHours[$classHours]['between'];
                break;
// Special case: Past the hour
//
// If minutes are specified and hours are specified,
// then the specification of minutes is always limited to a maximum of 60 minutes
// and always applies to the minutes 'past the hour'.
//
// We will switch ForceDateExplaination on, so that even a non-specified date yields an explaination (e.g. 'every day')
//
            case '12':
            case '22':
            case '21':
                $txtTime = $txtMinutes[$classMinutes] . ' ' . $txtHours[$classHours]['past'];
                break;
            default:
                $txtTime = $txtMinutes[$classMinutes] . ' ' . $txtHours[$classHours];
                break;
        }
// Generate Date String
        $txtDaysOfMonth = array();
        $txtDaysOfMonth[0] = '';
        $txtDaysOfMonth[1] = $this->natLangApply(
            'elemDOM: on_the_X',
            $this->natLangApply('ordinal: ' . $this->cronDaysOfMonth['elements'][0]['number1'])
        );
        $txtDaysOfMonth[2] = $this->natLangApply(
            'elemDOM: on_X',
            $this->natLangRange($this->cronDaysOfMonth, array($this, 'natLangElementDayOfMonth'))
        );
        $txtMonths = array();
        $txtMonths[0] = $this->natLangApply('elemMonth: of_every_month');
        $txtMonths[1] = $this->natLangApply(
            'elemMonth: during_every_X',
            $this->natLangApply('month: ' . $this->cronMonths['elements'][0]['number1'])
        );
        $txtMonths[2] = $this->natLangApply(
            'elemMonth: during_X',
            $this->natLangRange($this->cronMonths, array($this, 'natLangElementMonth'))
        );
        $classDaysOfMonth = $this->getClass($this->cronDaysOfMonth);
        $classMonths = $this->getClass($this->cronMonths);
        if ($classDaysOfMonth == '0') {
            $switchDaysOfWeekAreExcluding = false;
        }
        switch ($classDaysOfMonth . $classMonths) {
// Special case: Unspecified date + Unspecified month
//
// Rule: The language for unspecified fields is omitted if a more detailed field has already been explained.
//
// The time fields always yield an explaination,
// at the very least in the form of 'every minute'. This rule states that if the date
// is not specified, it can be omitted because 'every minute' is already sufficiently clear.
//
// There are some time specifications that do not contain an 'every' reference,
// but reference a specific time of day. In those cases
// the date explaination is enforced.
//
            case '00':
                $txtDate = '';
                break;
            default:
                $txtDate = ' ' . $txtDaysOfMonth[$classDaysOfMonth] . ' ' . $txtMonths[$classMonths];
                break;
        }
// Generate Year String
        if ($this->cronYears) {
            $txtYears = array();
            $txtYears[0] = '';
            $txtYears[1] = ' ' . $this->natLangApply('elemYear: in_X', $this->cronYears['elements'][0]['number1']);
            $txtYears[2] = ' ' .
                $this->natLangApply(
                    'elemYear: in_X',
                    $this->natLangRange($this->cronYears, array($this, 'natLangElementYear'))
                );
            $classYears = $this->getClass($this->cronYears);
            $txtYear = $txtYears[$classYears];
        }
// Generate DaysOfWeek String
        $collectDays = 0;
        foreach ($this->cronDaysOfWeek['elements'] as $elem) {
            if ($elem['hasInterval']) {
                for ($x = $elem['number1']; $x <= $elem['number2']; $x += $elem['interval']) {
                    $collectDays |= pow(2, $x);
                }
            } else {
                $collectDays |= pow(2, $elem['number1']);
            }
        }
        // * all days
        if ($collectDays == 127) {
            if (!$switchDaysOfWeekAreExcluding) {
                $txtDays = ' ' . $this->natLangApply('elemDOM: on_every_day');
            } else {
                $txtDays = '';
            }
        } else {
            $arrDays = array();
            for ($x = 0; $x <= 6; $x++) {
                if ($collectDays & pow(2, $x)) {
                    $arrDays[] = $x;
                }
            }
            $txtDays = '';
            for ($index = 0; $index < count($arrDays); $index++) {
                $txtDays .= ($index == 0 ? '' : ($index == (count($arrDays) - 1) ? ' ' .
                        $this->natLangApply($switchDaysOfWeekAreExcluding ? 'separator_or' : 'separator_and') .
                        ' ' : ', ')) . $this->natLangApply('day: ' . $arrDays[$index] . '_plural');
            }
            if ($switchDaysOfWeekAreExcluding) {
                $txtDays = ' ' . $this->natLangApply('elemDOW: but_only_on_X', $txtDays);
            } else {
                $txtDays = ' ' . $this->natLangApply('elemDOW: on_X', $txtDays);
            }
        }
        $txtResult = ucfirst($txtTime) . $txtDate . $txtDays;
        if (isset($txtYear)) {
            if ($switchDaysOfWeekAreExcluding) {
                $txtResult = ucfirst($txtTime) . $txtDate . $txtYear . $txtDays;
            } else {
                $txtResult = ucfirst($txtTime) . $txtDate . $txtDays . $txtYear;
            }
        }
        return $txtResult . '.';
    }
}
