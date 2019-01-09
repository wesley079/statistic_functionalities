<?php

/***
 * Deviation Class
 *
 * This class turns raw JSON-exported data into arrays
 * The function getDeviatingStatistics will return one array filled with the keys "short, medium, long"
 * These times are adjustable programmatically by changing the public variables $shortOperationDuration & $mediumOperationDuration
 * Also these percentages are adjustable programmatically
 *
 * The private variables are only adjustable in this class and need changes in this class to make them work correctly
 *
 * @author              Wesley Kroon <Wesleyk079@gmail.com>
 */
class Deviation
{

    //adjustable private fields
    private $keyToSearchFor = "Verrichting 1";
    private $tooFewCasesFeedback = "Te weinig data";

    //adjustable public fields
    public $shortOperationDuration = 20;
    public $mediumOperationDuration = 60;
    public $shortOperationPercentage = 20;
    public $mediumOperationPercentage = 20;
    public $longOperationPercentage = 10;

    //fields
    public $rawJson = [];
    public $operationsWithTime = [];
    public $shortOperations = [];
    public $mediumOperations = [];
    public $longOperations = [];
    public $outliers = false;


    //functions

    /***
     * Gets triggered when a new Deviation class is made
     * @param $json - decoded object with of all operations
     * @param bool $outliers (optional) | whether to remove outliers from the data (standard false)
     */
    function Deviation($json, $outliers = false)
    {
        //fill class fields
        $this->rawJson = $json;
        $this->outliers = $outliers;
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
            $this->operationsWithTime = $this->removeOutliers($this->operationsWithTime);
        }

        //fill the short medium and long operation array based on surgery time
        $this->orderAllOperations();

        //sort the different surgery types based on their duration
        uasort($this->shortOperations, array('Deviation', 'orderByLength'));
        uasort($this->mediumOperations, array('Deviation', 'orderByLength'));
        uasort($this->longOperations, array('Deviation', 'orderByLength'));

        //fill the return array and return it
        $returnArray = [];

        $returnArray["short"] = $this->shortOperations;
        $returnArray["medium"] = $this->mediumOperations;
        $returnArray["long"] = $this->longOperations;

        return $returnArray;
    }

    /***
     * $this->operationsWithTime will be filled with all unique medical surgery options
     * For each medical surgery there will be 3 options: real time, planned time and the days these surgeries took place
     */
    private function getOperationTimes()
    {
        //Check for data
        foreach ($this->rawJson as $case) {

            //check if index already exists
            $key = $this->keyToSearchFor;
            $operation_key = $case->$key;

            if (!array_key_exists($operation_key, $this->operationsWithTime)) {
                //Key doesn't exist, make new key and add operation duration
                $this->operationsWithTime[$operation_key]["real"] = [];
                $this->operationsWithTime[$operation_key]["planned"] = [];
                $this->operationsWithTime[$operation_key]["days"] = [];
            }

            //add operation duration to key with the specific time
            array_push($this->operationsWithTime[$operation_key]["real"], $case->Operatieduur);

            $planned_key = "Geplande duur";
            array_push($this->operationsWithTime[$operation_key]["planned"], $case->$planned_key);

            array_push($this->operationsWithTime[$operation_key]["days"], $case->Starttijd);

            $this->operationsWithTime[$operation_key]["removed"] = [];

            //sort this operation's time
            sort($this->operationsWithTime[$operation_key]["real"]);
            sort($this->operationsWithTime[$operation_key]["planned"]);
        }
    }

    /***
     * This function will remove all outliers from the field $operationsWithTime
     * @param $operations
     * @return mixed
     */
    static function removeOutliers($operations)
    {
        foreach ($operations as $key => $operation) {

            //Determine whether a standard deviation is possible to calculate
            if (count($operation["real"]) > 1) {
                $deviation = Deviation::stats_standard_deviation($operation["real"]);
            } else {
                $deviation = "Te weinig data";
            }

            $mean = array_sum($operation["real"]) / count($operation["real"]);
            foreach ($operation["real"] as $singleKey => $singleNumber) {

                //remove outliers
                if ($singleNumber < $mean - (5 * $deviation) || $singleNumber > (5 * $deviation) + $mean) {
                    array_push($operations[$key]["removed"], $singleNumber);
                    unset($operations[$key]["real"][$singleKey]);

                }
            }
        }
        return $operations;
    }

    /***
     * Calculate all needed information
     * Add all operations to the three categories of surgery time
     */
    private function orderAllOperations()
    {
        foreach ($this->operationsWithTime as $operation => $time) {

            //skip empty operation titles
            if ($operation !== "") {

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
                    //short operations
                    case ($meanTime <= $this->shortOperationDuration):
                        //percentage that decides whether a operation in this array is too long
                        $percentage = ($this->shortOperationPercentage / 100);

                        //fill operation with standard info
                        $this->shortOperations[$operation] = $time;

                        //add all extra logic
                        $this->shortOperations[$operation] = $this->addOperationInformation($this->shortOperations[$operation], $amount, $meanTime, $stats_standard_dev, $percentage);
                        break;
                    //medium operation
                    case ($meanTime > $this->shortOperationDuration && $meanTime <= $this->mediumOperationDuration):
                        //percentage that decides whether a operation in this array is too long
                        $percentage = ($this->mediumOperationPercentage / 100);

                        //fill operation with standard info
                        $this->mediumOperations[$operation] = $time;

                        //add all extra logic
                        $this->mediumOperations[$operation] = $this->addOperationInformation($this->mediumOperations[$operation], $amount, $meanTime, $stats_standard_dev, $percentage);
                        break;
                    //long operation
                    case ($meanTime > $this->mediumOperationDuration):
                        //percentage that decides whether a operation in this array is too long
                        $percentage = ($this->longOperationPercentage / 100);

                        //fill operation with standard info
                        $this->longOperations[$operation] = $time;

                        //add all extra logic
                        $this->longOperations[$operation] = $this->addOperationInformation($this->longOperations[$operation], $amount, $meanTime, $stats_standard_dev, $percentage);
                        break;
                }
            }
        }
    }

    /***
     * Fill an operation with all needed values
     * Return the array filled with values
     *
     * @param $operations
     * @param $amount
     * @param $meanTime
     * @param $stats_standard_dev
     * @param $percentage
     * @return mixed
     */
    private function addOperationInformation($operations, $amount, $meanTime, $stats_standard_dev, $percentage)
    {
        $operation = $operations;

        $operation["amount"] = $amount;
        $operation["mean"] = $meanTime;
        $operation["standardDev"] = $stats_standard_dev;
        $operation["statistic_min"] = ($meanTime - 3 * $stats_standard_dev);
        $operation["statistic_max"] = ($meanTime + 3 * $stats_standard_dev);
        $operation["correct_plan"] = 0;
        $operation["wrong_plan"] = 0;
        $operation["meanPlanned"] = intval(array_sum($operation["planned"]) / count($operation["planned"]));

        //check correctly planned
        foreach ($operation["real"] as $time) {
            if ($time < $operation["planned"]) {
                $operation["correct_plan"]++;
            } else {
                $operation["wrong_plan"]++;
            }
        }

        //save advise for the ability to plan this operation
        $operation["advice"] = "Slecht inplanbaar";


        if ($stats_standard_dev < ($meanTime * $percentage)) {
            $operation["advice"] = "Goed inplanbaar";
        }

        if ($amount < 2) {
            $operation["advice"] = 'Te weinig data';
        }

        return $operation;
    }

    /***
     * Sort operation arrays custom
     * Order by duration of the operation
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
     *  This function calculates the functions of a sample
     *  NOTE: This function won't work on non-sample arrays
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

        return sqrt($squareTotal / ($n - 1));
    }
}
