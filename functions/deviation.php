<?php

/***
 * Deviation Class
 *
 * This class turns raw JSON-exported data into arrays
 * The function getDeviatingStatistics will return one array filled with the keys "short, medium, long"
 * These times are adjustable programmatically by changing the public variables $shortDuration & $mediumDuration
 * Also these percentages are adjustable programmatically
 *
 * The private variables are only adjustable in this class and need changes in this class to make them work correctly
 *
 * @author              Wesley Kroon <Wesleyk079@gmail.com>
 */
class Deviation
{

    //adjustable private fields
    private $keyToSearchFor = "";
    private $tooFewCasesFeedback = "";
    private $firstKey = "";
    private $comparisonKey = "";

    //adjustable public fields
    public $shortDuration = 20;
    public $mediumDuration = 60;
    public $shortPercentage = 20;
    public $mediumPercentage = 20;
    public $longPercentage = 10;

    //fields
    public $rawJson = [];
    public $resultsWithTime = [];
    public $shortResults = [];
    public $mediumResults = [];
    public $longResults = [];
    public $outliers = false;
    public $secondComparison = false;
    public $negativeFeedback = "Niet goed";
    public $positiveFeedback = "Goed";
    //functions

    /***
     * Gets triggered when a new Deviation class is made
     * @param $json - decoded object with of all results
     * @param bool $outliers (optional) | whether to remove outliers from the data (standard false)
     * @param bool $secondComparison
     * @param $searchKey
     * @param $keyToFindDeviation
     * @param null $secondKeyToFindDeviation
     */
    function Deviation($json, $searchKey, $keyToFindDeviation, $outliers = false, $secondComparison = false, $secondKeyToFindDeviation = null)
    {
        //fill class fields
        $this->rawJson = $json;
        $this->outliers = $outliers;

        //second compare or not
        $this->secondComparison = $secondComparison;

        //keys to search for in this class
        $this->keyToSearchFor = $searchKey;
        $this->firstKey = $keyToFindDeviation;
        $this->comparisonKey = $secondKeyToFindDeviation;
    }


    /***
     * Returns the complete version of separated time categories
     * @return array
     */
    function getDeviatingStatistics()
    {
        //apply all functions needed to get the information to return
        $this->getOperationTimes();

        if ($this->outliers) {
            $this->resultsWithTime = $this->removeOutliers($this->resultsWithTime);
        }

        //fill the short medium and long result array based on surgery time
        $this->orderAllResults();

        //sort the different surgery types based on their duration
        uasort($this->shortResults, array('Deviation', 'orderByLength'));
        uasort($this->mediumResults, array('Deviation', 'orderByLength'));
        uasort($this->longResults, array('Deviation', 'orderByLength'));

        //fill the return array and return it
        $returnArray = [];

        $returnArray["short"] = $this->shortResults;
        $returnArray["medium"] = $this->mediumResults;
        $returnArray["long"] = $this->longResults;

        return $returnArray;
    }

    /***
     * $this->resultsWithTime will be filled with all unique medical surgery options
     * For each medical surgery there will be 3 options: realand comparison these surgeries took place
     */
    private function getOperationTimes()
    {
        //Check for data
        foreach ($this->rawJson as $case) {

            //check if index already exists
            $key = $this->keyToSearchFor;
            $result_key = $case->$key;

            if (!array_key_exists($result_key, $this->resultsWithTime)) {
                //Key doesn't exist, make new key and add result duration
                $this->resultsWithTime[$result_key]["real"] = [];
                $this->resultsWithTime[$result_key]["comparison"] = [];
            }

            //add result to key with the specified value
            $searchKey = $this->firstKey;
            array_push($this->resultsWithTime[$result_key]["real"], $case->$searchKey);

            //only if second comparison is asked
            if($this->secondComparison) {
                $comparison_key = $this->comparisonKey;
                array_push($this->resultsWithTime[$result_key]["comparison"], $case->$comparison_key);
            }


            $this->resultsWithTime[$result_key]["removed"] = [];

            //sort this result's time
            sort($this->resultsWithTime[$result_key]["real"]);
            sort($this->resultsWithTime[$result_key]["comparison"]);
        }
    }

    /***
     * This function will remove all outliers from the field $resultsWithTime
     * @param $results
     * @return mixed
     */
    static function removeOutliers($results)
    {
        foreach ($results as $key => $result) {

            //Determine whether a standard deviation is possible to calculate
            if (count($result["real"]) > 1) {
                $deviation = Deviation::stats_standard_deviation($result["real"]);
            } else {
                $deviation = "Te weinig data";
            }

            $mean = array_sum($result["real"]) / count($result["real"]);
            foreach ($result["real"] as $singleKey => $singleNumber) {

                //remove outliers
                if ($singleNumber < $mean - (5 * $deviation) || $singleNumber > (5 * $deviation) + $mean) {
                    array_push($results[$key]["removed"], $singleNumber);
                    unset($results[$key]["real"][$singleKey]);

                }
            }
        }
        return $results;
    }

    /***
     * Calculate all needed information
     * Add all results to the three categories of surgery time
     */
    private function orderAllResults()
    {
        foreach ($this->resultsWithTime as $result => $time) {

            //skip empty result titles
            if ($result !== "") {

                //basic needed statistics
                $amount = count($time["real"]);
                $meanTime = intval(array_sum($time["real"]) / $amount);

                //calculate the standard deviation if possible, if not give advice from $too_few_cases_feedback
                if (count($time["real"]) > 1) {
                    $stats_standard_dev = $this->stats_standard_deviation($time["real"]);
                } else {
                    $stats_standard_dev = $this->tooFewCasesFeedback;
                }

                //add to the right array based on mean surgery time
                switch (true) {
                    //short results
                    case ($meanTime <= $this->shortDuration):
                        //percentage that decides whether a result in this array is too long
                        $percentage = ($this->shortPercentage / 100);

                        //fill result with standard info
                        $this->shortResults[$result] = $time;

                        //add all extra logic
                        $this->shortResults[$result] = $this->addOperationInformation($this->shortResults[$result], $amount, $meanTime, $stats_standard_dev, $percentage);
                        break;
                    //medium result
                    case ($meanTime > $this->shortDuration && $meanTime <= $this->mediumDuration):
                        //percentage that decides whether a result in this array is too long
                        $percentage = ($this->mediumPercentage / 100);

                        //fill result with standard info
                        $this->mediumResults[$result] = $time;

                        //add all extra logic
                        $this->mediumResults[$result] = $this->addOperationInformation($this->mediumResults[$result], $amount, $meanTime, $stats_standard_dev, $percentage);
                        break;
                    //long result
                    case ($meanTime > $this->mediumDuration):
                        //percentage that decides whether a result in this array is too long
                        $percentage = ($this->longPercentage / 100);

                        //fill result with standard info
                        $this->longResults[$result] = $time;

                        //add all extra logic
                        $this->longResults[$result] = $this->addOperationInformation($this->longResults[$result], $amount, $meanTime, $stats_standard_dev, $percentage);
                        break;
                }
            }
        }
    }

    /***
     * Fill an result with all needed values
     * Return the array filled with values
     *
     * @param $results
     * @param $amount
     * @param $meanTime
     * @param $stats_standard_dev
     * @param $percentage
     * @return mixed
     */
    private function addOperationInformation($results, $amount, $meanTime, $stats_standard_dev, $percentage)
    {
        $result = $results;

        $result["amount"] = $amount;
        $result["mean"] = $meanTime;
        $result["standardDev"] = $stats_standard_dev;
        $result["statistic_min"] = ($meanTime - 3 * $stats_standard_dev);
        $result["statistic_max"] = ($meanTime + 3 * $stats_standard_dev);
        $result["correct_plan"] = 0;
        $result["wrong_plan"] = 0;

        if($this->secondComparison) {
            $result["meanComparison"] = intval(array_sum($result["comparison"]) / count($result["comparison"]));
        }

        //check correctly comparison
        foreach ($result["real"] as $time) {
            if ($time < $result["comparison"]) {
                $result["correct_plan"]++;
            } else {
                $result["wrong_plan"]++;
            }
        }

        //save advise for the ability to plan this result
        $result["advice"] = $this->negativeFeedback;


        if ($stats_standard_dev < ($meanTime * $percentage)) {
            $result["advice"] = $this->positiveFeedback;
        }

        if ($amount < 2) {
            $result["advice"] = 'Te weinig data';
        }

        return $result;
    }

    /***
     * Sort result arrays custom
     * Order by duration of the result
     * @param $a
     * @param $b
     * @return int
     */
    private static function orderByLength($a, $b)
    {
        if ($a["mean"] == $b["mean"]) {
            return 0;
        }
        return ($a["mean"] < $b["mean"]) ? -1 : 1;
    }


    /**
     *  This function calculates the functions of a population
     *  NOTE: This function won't work on sample arrays
     *
     * @param array $array
     * @return float|bool Standard functions for send array, or false if an error occurred
     */
    static function stats_standard_deviation(array $array)
    {
        //count amount of elements
        $n = count($array);

        //warn for too few found elements
        if ($n <= 1) {
            trigger_error("The array has too few element", E_USER_WARNING);
            return false;
        }

        //calculate mean and initialize the square total
        $mean = array_sum($array) / $n;
        $squareTotal = 0.0;

        //calculate (Xi - µ)²
        foreach ($array as $val) {
            $difference = ((double)$val) - $mean;
            $squareTotal += $difference * $difference;
        };

        return sqrt($squareTotal / $n);
    }
}
