<?php

/***
 * Correlation
 *
 * @author              Wesley Kroon <Wesleyk079@gmail.com>
 */
class Correlation
{

    //adjustable private fields
    private $keyToSearchFor = "Operatieduur";


    //adjustable public fields


    //fields
    public $filteredOperations = [];

    //functions

    /***
     * Gets triggered whem a Correlation class is made
     * Removes deviating cases
     * @param $json - decoded object with of all operations
     * @param $operationTitle
     */
    function Correlation($json, $operationTitle)
    {
        $selectedOperations = [];
        $allTimes = [];
        $meanTime = [];

        foreach ($json as $operation) {
            $key = "Verrichting 1";
            if ($operation->$key === $operationTitle) {
                array_push($selectedOperations, $operation);


                $timeKey = $this->keyToSearchFor;
                array_push($allTimes, $operation->$timeKey);
                array_push($meanTime, $operation->$timeKey);
            }
        }

        //calculate meanTime
        $meanTime = array_sum($meanTime) / count($meanTime);

        //calculate deviation
        $deviation = Deviation::stats_standard_deviation($allTimes);

        //remove outliers
        $this->filteredOperations = $this->removeOutliers($meanTime, $deviation, $selectedOperations);
    }

    public function calculateCorrelations()
    {

        $key_possibilities = $this->getAllPossibleKeys($this->filteredOperations);
        foreach ($key_possibilities as $possibleCorrelation) {
            $x = [];
            $y = [];
            foreach ($this->filteredOperations as $operation) {

                $key = $this->keyToSearchFor;

                //check if both values exist and are numeric
                if (is_numeric($operation->$key) && is_numeric($operation->$possibleCorrelation)) {
                    array_push($x, $operation->$key);
                    array_push($y, $operation->$possibleCorrelation);
                }
            }

            $rankedValueArray = $this->getRanks($this->keyToSearchFor, $possibleCorrelation, $x, $y);
            $return = $this->calculateDifference($rankedValueArray, $possibleCorrelation);
            $difference = $return["difference"];
            $count = $return["count"];

            //if difference was calculated, calculate the coefficient
            if ($difference !== false && $count >= 2) {
                echo $this->calculateCoefficient($difference, $count);
                echo $possibleCorrelation . ' heeft invloed op ' . $this->keyToSearchFor . '<br/>';
            }
        }
    }

    /**
     * @param $d2
     * @param $n
     * @return float|int
     */
    private function calculateCoefficient($d2, $n)
    {

        $above = 6 * $d2;
        $under = $n * (($n * $n) - 1);

        $coefficient = 1 - ($above / $under);
        return $coefficient;
    }

    /***
     * This function calculates the difference between rank x and rank y
     * The difference will be squared and summed up together
     * The total summed up value will be returned
     *
     * @param $array
     * @param $possibleCorrelationKey
     * @return array|bool
     */
    private function calculateDifference($array, $possibleCorrelationKey)
    {
        //make sure both array have the same
        if (!(count($array[$this->keyToSearchFor]) == count($array[$possibleCorrelationKey])) && count($array[$this->keyToSearchFor]) >= 30) {
            //warn for too few found elements in one of the two arrays
            trigger_error("The array has too few element", E_USER_WARNING);
            return false;
        }
        $totalDifference = 0;

        for ($i = 0; $i < count($array[$this->keyToSearchFor]); $i++) {

            //rank x - rank y
            $difference = $array[$this->keyToSearchFor][$i]["rank"] - $array[$possibleCorrelationKey][$i]["rank"];

            //square difference and add to total
            $totalDifference += ($difference * $difference);
        }

        return ["difference" => $totalDifference, "count" => count($array[$this->keyToSearchFor]), "x" => $array[$this->keyToSearchFor], "y" => $array[$possibleCorrelationKey]];
    }

    /***
     * Returns array of all numeric elements
     * With these keys correlating numbers can be searched for
     *
     * @param $operations
     * @return array
     */
    private function getAllPossibleKeys($operations)
    {
        $key_array = [];

        foreach ($operations[0] as $key => $operationKey) {
            if (is_numeric($operationKey) || is_float($operationKey)) {
                array_push($key_array, $key);
            }
        }

        return $key_array;
    }

    /***
     * Get the ranks of the values
     * Returns an array with both axes as title and ranks in the same order as sent to this function
     * @param $xTitle
     * @param $yTitle
     * @param $x
     * @param $y
     * @return array
     */
    private function getRanks($xTitle, $yTitle, $x, $y)
    {
        //calculate the rank for both values without changing order
        $x = $this->calculateRanks($x);
        $y = $this->calculateRanks($y);

        return [
            $xTitle => $x,
            $yTitle => $y
        ];
    }

    /***
     * In this function an array with the rank value will be returned
     * The order of the $unOrdered will not change
     * There will be returned an list of array with the following keys
     * - Value
     * - Rank
     * @param $unOrdered
     * @return mixed
     */
    private function calculateRanks($unOrdered)
    {

        $ordered = $unOrdered;
        sort($ordered);

        $countArray = [];

        //make a count array to make sure how many time a single value was found
        foreach ($ordered as $number) {
            if (!array_key_exists($number, $countArray)) {
                $countArray[$number]["count"] = 1;
            } else {
                $countArray[$number]["count"]++;
            }
        }

        //determine the rank for each value
        $currentRank = 0;
        foreach ($countArray as $key => $singleValue) {

            if ($singleValue["count"] > 1) {
                //multiple registered values were found earlier, calculate rank
                $totalRankWithCurrentValue = 0;
                for ($i = 1; $i <= $singleValue["count"]; $i++) {
                    $totalRankWithCurrentValue += ($currentRank + $i);
                }

                $meanTotalRank = $totalRankWithCurrentValue / $singleValue["count"];
                $countArray[$key]["rank"] = $meanTotalRank;
            } else {
                //only one registered value was found, rank = +1
                $countArray[$key]["rank"] = ($currentRank + 1);
            }

            //the next rank
            $currentRank += $singleValue["count"];
        }

        foreach ($unOrdered as $key => $number) {
            //create new array with the information
            $rankInformation = [];
            $rankInformation["value"] = $number;
            $rankInformation["rank"] = $countArray[$number]["rank"];

            //add rank and value both to a new key, but don't change the order
            $unOrdered[$key] = $rankInformation;
        }

        return $unOrdered;
    }


    /***
     * This function returns the operation array without the outliers
     * A case is removed when the value deviates 5 times the standard deviation
     *
     * @param $mean
     * @param $deviation
     * @param $operationArray
     * @return array
     */
    private function removeOutliers($mean, $deviation, $operationArray)
    {

        $operationWithoutOutlier = [];

        foreach ($operationArray as $operation) {
            $key = $this->keyToSearchFor;
            if ($operation->$key < $mean + ($deviation * 5) && $operation->$key > $mean - ($deviation * 5)) {
                array_push($operationWithoutOutlier, $operation);
            }
        }

        return $operationWithoutOutlier;
    }


}
