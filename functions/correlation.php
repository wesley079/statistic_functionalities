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

    //fields
    public $filteredOperations = [];

    //functions

    /***
     * Gets triggered when a Correlation class is made
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

    /***
     * This function will calculate the correlation coefficient using the following functions
     * - getAllPossibleKeys     / (get keys that have have numeric values
     * - getRanks               / (rank the values from lowest value to higher value)
     * - calculateDifference    / (calculates the d² to apply in Spearman's formula)
     * - calculateCoefficient   / Calculates the correlation coefficient for each key that has a numeric value
     * @return array
     */
    public function calculateCorrelations()
    {
        $allReturnArrays = [];
        $key_possibilities = $this->getAllPossibleKeys($this->filteredOperations);

        //calculate correlation coefficient for all values that are numeric
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

            //calculate values and save them in specified variables
            $rankedValueArray = $this->getRanks($this->keyToSearchFor, $possibleCorrelation, $x, $y);
            $return = $this->calculateDifference($rankedValueArray, $possibleCorrelation);
            $difference = $return["difference"];
            $count = $return["count"];

            //if difference was calculated, calculate the coefficient
            if ($difference !== false && $count >= 2) {
                $returnArray = [
                    "xTitle" => $this->keyToSearchFor,
                    "yTitle" => $possibleCorrelation,
                    "coefficient" => $this->calculateCoefficient($difference, $count)
                ];

                //save the found result in the object that gets returned to the user
                array_push($allReturnArrays, $returnArray);
            }
        }

        return $allReturnArrays;
    }

    /***
     * Get an advise based on the correlation coefficient
     * This function will return a 'DUTCH' language advise.
     * @param $correlationCoefficient
     * @param $xTitle
     * @param $yTitle
     * @return string
     */
    public function getCorrelationAdvise($correlationCoefficient, $xTitle, $yTitle)
    {
        //standard advise
        $advise = 'Not possible to give an advise based on this number';
        $positive = "Het gegeven '" . $xTitle . "' <b>lijkt langer te duren wanneer</b> '" . $yTitle . "' hoger is.";
        $negative = "Het gegeven '" . $xTitle . "' <b>lijkt langer te duren wanneer</b> '" . $yTitle . "' lager is.";
        $non = "Het gegeven '" . $xTitle . "' lijkt niet te correleren met '" . $yTitle . "'.";
        $correlationType = " Er is sprake van een ";

        switch ($correlationCoefficient) {
            case ($correlationCoefficient >= 0.9 && $correlationCoefficient <= 1):
                //very high positive correlation
                $advise = $positive . $correlationType . "zeer hoge correlatie: " . $correlationCoefficient;
                break;
            case ($correlationCoefficient <= -0.9 && $correlationCoefficient >= -1):
                //very high negative correlation
                $advise = $negative . $correlationType . "zeer hoge correlatie: " . $correlationCoefficient;
                break;
            case ($correlationCoefficient >= 0.7 && $correlationCoefficient < 0.9):
                //high positive correlation
                $advise = $positive . $correlationType . "hoge correlatie: " . $correlationCoefficient;
                break;
            case ($correlationCoefficient <= -0.7 && $correlationCoefficient > -0.9):
                //high negative correlation
                $advise = $negative . $correlationType . "hoge correlatie: " . $correlationCoefficient;
                break;
            case ($correlationCoefficient >= 0.5 && $correlationCoefficient < 0.7):
                //moderate positive correlation
                $advise = $positive . $correlationType . "gematige correlatie: : " . $correlationCoefficient;
                break;
            case ($correlationCoefficient <= -0.5 && $correlationCoefficient > -0.7):
                //moderate negative correlation
                $advise = $negative . $correlationType . "gematige correlatie: : " . $correlationCoefficient;
                break;
            case ($correlationCoefficient >= 0.3 && $correlationCoefficient < 0.5):
                //low positive correlation
                $advise = $positive . $correlationType . "enige correlatie : " . $correlationCoefficient;
                break;
            case ($correlationCoefficient <= -0.3 && $correlationCoefficient > -0.5):
                //low negative correlation
                $advise = $negative . $correlationType . "enige correlatie : " . $correlationCoefficient;
                break;
            case ($correlationCoefficient >= -0.3 && $correlationCoefficient < 0.3):
                //negligible correlation
                $advise = $non . $correlationType . " geen tot te verwaarloze correlatie " . $correlationCoefficient;
                break;
        }
        return $advise;
    }


    /**
     * Apply the Spearman's formula to the calculated d² and found n
     * NOTE: This is the formula based on a sample of data
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

        //remove all cases that deviate 5 times the standard deviation from the mean number
        foreach ($operationArray as $operation) {
            $key = $this->keyToSearchFor;
            if ($operation->$key < $mean + ($deviation * 5) && $operation->$key > $mean - ($deviation * 5)) {
                array_push($operationWithoutOutlier, $operation);
            }
        }

        return $operationWithoutOutlier;
    }


}
