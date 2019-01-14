<?php

namespace statisticFunctions;

/***
 * Deviation Class
 *
 * This class turns raw JSON-exported data into arrays
 * The function getDeviatingStatistics will return one array filled with the keys "short, medium, long"
 * These times are adjustable programmatically by changing the public variables $firstCategory & $middleCategory
 * Also these percentages are adjustable programmatically
 *
 * The private variables are only adjustable in this class and need changes in this class to make them work correctly
 *
 * @author              Wesley Kroon <Wesleyk079@gmail.com>
 */
class Deviation
{

    //Setting fields
    private $keyToSearchFor = "";
    private $tooFewCasesFeedback = "";
    private $firstKey = "";
    private $comparisonKey = "";
    private $firstCategory = 20;
    private $middleCategory = 60;
    private $firstCatPercentage = 20;
    private $middleCatPercentage = 20;
    private $lastCatPercentage = 10;
    private $rawJson = [];
    private $resultsWithTime = [];
    private $results = [];
    private $outliers = false;
    private $secondComparison = false;
    private $negativeFeedback = "Niet goed";
    private $positiveFeedback = "Goed";

    //functions

    /***
     * Gets triggered when a new Deviation class is made
     * @param array $options
     */
    function __construct($options = [
        "FileToCheck" => null,
        "KeyToSelect" => "",
        "KeyToSearchFor" => "",
        "RemoveOutliers" => true,
        "SecondComparison" => false,
        "KeyToCompareMean" => "",
        "FirstCategoryMax" => 10,
        "MiddleCategoryMax" => 20,
        "FirstPercentageMeasure" => 20,
        "MiddlePercentageMeasure" => 12.5,
        "LastPercentageMeasure" => 10,
        "PositiveFeedback" => "Lower than the percentage you set",
        "NegativeFeedback" => "Higher than the the percentage you set"

    ])
    {
        //set variables to
        $this->rawJson = $options["FileToCheck"];
        $this->keyToSearchFor = $options["KeyToSelect"];
        $this->firstKey = $options["KeyToSearchFor"];
        $this->outliers = $options["RemoveOutliers"];
        $this->secondComparison = $options["SecondComparison"];
        $this->comparisonKey = $options["KeyToCompareMean"];
        $this->firstCategory = $options["FirstCategoryMax"];
        $this->middleCategory = $options["MiddleCategoryMax"];
        $this->firstCatPercentage = $options["FirstPercentageMeasure"];
        $this->middleCatPercentage = $options["MiddlePercentageMeasure"];
        $this->lastCatPercentage = $options["LastPercentageMeasure"];
        $this->positiveFeedback = $options["PositiveFeedback"];
        $this->negativeFeedback = $options["NegativeFeedback"];
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
        uasort($this->results, array('statisticFunctions\Deviation', 'orderByLength'));

        return $this->results;
    }

    /***
     * $this->resultsWithTime will be filled with all unique medical surgery options
     * For each medical surgery there will be 3 options: real and comparison these surgeries took place
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
            if ($this->secondComparison) {
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
        foreach ($this->resultsWithTime as $result => $operation) {

            //skip empty result titles
            if ($result !== "") {

                //basic needed statistics
                $amount = count($operation["real"]);
                $meanTime = intval(array_sum($operation["real"]) / $amount);

                //calculate the standard deviation if possible, if not give advice from $too_few_cases_feedback
                if (count($operation["real"]) > 1) {
                    $stats_standard_dev = $this->stats_standard_deviation($operation["real"]);
                } else {
                    $stats_standard_dev = $this->tooFewCasesFeedback;
                }

                $percentage = 0;
                $temporaryResult = null;
                if ($meanTime <= $this->firstCategory) {
                    //percentage that decides whether a result in this array is too long
                    $percentage = ($this->firstCatPercentage / 100);

                } elseif ($meanTime > $this->firstCategory && $meanTime <= $this->middleCategory) {
                    //percentage that decides whether a result in this array is too long
                    $percentage = ($this->middleCatPercentage / 100);

                } elseif ($meanTime > $this->middleCategory) {
                    //percentage that decides whether a result in this array is too long
                    $percentage = ($this->lastCatPercentage / 100);
                }

                $this->results[$result] = $this->addOperationInformation($operation, $amount, $meanTime, $stats_standard_dev, $percentage);

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
        $result["statisticMin"] = ($meanTime - 3 * $stats_standard_dev);
        $result["statisticMax"] = ($meanTime + 3 * $stats_standard_dev);
        $result["lowerThanComparison"] = 0;
        $result["higherThanComparison"] = 0;

        if ($this->secondComparison) {
            $result["meanComparison"] = intval(array_sum($result["comparison"]) / count($result["comparison"]));
        }

        //check correctly comparison
        foreach ($result["real"] as $time) {
            if ($time < $result["comparison"]) {
                $result["lowerThanComparison"]++;
            } else {
                $result["higherThanComparison"]++;
            }
        }

        //save advise for the ability to plan this result
        $result["advice"] = $this->negativeFeedback;


        if (($stats_standard_dev * 3) < ($meanTime * $percentage)) {
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
