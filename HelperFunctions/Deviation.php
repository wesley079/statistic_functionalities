<?php
class Deviation{
    private $json;
    private $combinedNumbers = [];
    private $numbers = [];
    private $caseArray = [];
    private $amountOfPropertyArray = [];
    private $uniquesByValues;
    private $average = 0;

    private $uniqueTitle = "";
    private $analyseProperty = "";
    private $currentDeviationMinimum = 0;
    private $deviationNumber = 0;
    private $deviationLimit = 0;

    /***
     * Function where this class gets filled with data about operations
     *
     * @param $receivedData
     * @param $uniqueTitle
     * @param $analyseProperty
     * @param $deviationLimit (optional) standard value = 1
     */
    function initialize($receivedData, $uniqueTitle, $analyseProperty, $deviationLimit = 1){
        $this->json = $receivedData;

        $this->uniqueTitle = $uniqueTitle;
        $this->analyseProperty = $analyseProperty;
        $this->deviationLimit = $deviationLimit;

        $this->orderByString($this->json, $uniqueTitle, $analyseProperty);
        $this->findDeviatingCases();
    }

    function commonCases(){
        $this->getAllValuesOrderedByAmount($this->analyseProperty);

        $commonArray = [];

        foreach($this->uniquesByValues as $key => $case){
            echo '<pre>';
            foreach($case as $uniqueValue){
                foreach($uniqueValue as $uniqueID){
                    if(!isset($commonArray[$uniqueID])){
                        $commonArray[$uniqueID] = 0;
                    }

                    $commonArray[$uniqueID] += 1;
                }
            }
        }


        return $commonArray;
    }

    /***
     * Returns the average of the analysed data
     * @return int
     */
    function getAverage(){
        return $this->average;
    }

    /***
     * Returns an array of cases that deviate from user's inputted amount of the standard deviation
     * @return array
     */
    function getDeviatingCases(){
        return $this->caseArray;
    }

    function supposedToBe($property, $value){
        $amount = 0;
        foreach($this->json as $operation){
            if($operation->$property == $value){
                $amount++;
            }
        }
        return ($amount / count($this->json)) * 100;
    }

    /***
     * Returns the total of cases analysed
     * @return int
     */
    function totalAmount(){
        return count($this->json);
    }

    /***
     *  In this function the deviating cases will be filtered
     *
     *  The amount of deviating cases is based on the standard deviation which can be changed by updating the
     *  deviation limit
     *
     * The arrays will be based on the type of analyses given
     */
    function findDeviatingCases(){
        //temporary variables
        $extremeNumbers = [];
        $lastChecked = 1;

        //calculate temporary variables
        $sortedData = $this->combinedNumbers;
        rsort($sortedData);
        $deviation = $this->stats_standard_deviation($this->numbers);
        $average = array_sum($this->numbers) / count($this->numbers);
        $property = $this->analyseProperty;

        foreach ($sortedData as $singleData){

            $difference = $singleData[$property] - $average;
            $percentualDeviation = ($difference / $deviation);

            //Check if the case deviates more than given deviationLimit time of the normal deviation
            if($percentualDeviation > $this->deviationLimit) {
                array_push($extremeNumbers, $singleData);
                $lastChecked = $singleData[$property];
            }
        }
        $this->currentDeviationMinimum = $lastChecked;
        $this->deviationNumber = $deviation;
        $this->average = $average;
        $this->caseArray = $extremeNumbers;
    }

    /***
     * This case calculates the amount of appearances of values
     */
    function getAllValuesOrderedByAmount(){
        if(empty($this->amountOfPropertyArray)) {
            $propertyArray = [];
            $operationsInValue = [];
            foreach ($this->caseArray as $propertyAdd) {

                foreach ($propertyAdd as $key => $singleProperty) {
                    //Check if a new array has to be added
                    if (!isset($propertyArray[$key])) {
                        $propertyArray[$key] = [];
                    }

                    //check if this value is already added by previous cases
                    if (!isset($propertyArray[$key][$singleProperty])) {
                        $propertyArray[$key][$singleProperty] = 0;
                    }

                    if(!isset($operationsInValue[$key][$singleProperty])){
                        $operationsInValue[$key][$singleProperty] = [];
                    }

                    array_push($operationsInValue[$key][$singleProperty], $propertyAdd[$this->uniqueTitle] );


                    //higher the amount of this value by 1
                    $propertyArray[$key][$singleProperty] += 1;
                }
            }

            $sortedPropertyArray = [];
            foreach($propertyArray as $key => $propertyList){
                arsort($propertyList);
                $sortedPropertyArray[$key] = $propertyList;
            }
            $this->uniquesByValues = $operationsInValue;
            $this->amountOfPropertyArray = $sortedPropertyArray;
            return $this->amountOfPropertyArray;
        }
        else{
            return $this->amountOfPropertyArray;
        }
    }

    /***
     * Returns an array with unique values involved by params
     */
    function getUniqueIDByPropertyValue($property, $value){
        return $this->uniquesByValues[$property][$value];
    }

    /***
     * Return the normal deviation
     * @return int
     */
    function getDeviationNumber(){
        return $this->deviationNumber;
    }

    /***
     * Returns amount of selected operation in this deviation
     * @return int
     */
    function amountOfDeviations(){
        return count($this->caseArray);
    }

    /***
     * Returns the lowest number from which cases are included
     * @return int
     */
    function lowestAmountDeviationNumber(){
        return $this->currentDeviationMinimum;
    }

    /***
     * Function where jsondata is orderd by a given string
     * Fill 'numbers' of given second string
     *
     * @param $receivedJson
     * @param $orderTitle
     * @param $dataTitle
     */
    function orderByString($receivedJson, $orderTitle, $dataTitle){

        foreach($receivedJson as $jsonObject){
            //add data with the unique id as key
            $this->numbers[$jsonObject->$orderTitle] = $jsonObject->$dataTitle;

            //Make sure the array with all the data has the most important data in it
            $this->combinedNumbers[$jsonObject->$orderTitle] = [$dataTitle => $jsonObject->$dataTitle, $orderTitle => $jsonObject->$orderTitle];

            //add the rest of the data to the object
            foreach($jsonObject as $key => $property){
                $this->combinedNumbers[$jsonObject->$orderTitle][$key] = $property;
            }

        }
    }

    /**
     * This user-land implementation follows the implementation quite strictly;
     * it does not attempt to improve the code or algorithm in any way. It will
     * raise a warning if you have fewer than 2 values in your array, just like
     * the extension does (although as an E_USER_WARNING, not E_WARNING).
     *
     * @param array $a
     * @param bool $sample [optional] Defaults to false
     * @return float|bool The standard deviation or false on error.
     */
    function stats_standard_deviation(array $a, $sample = false)
    {
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);
            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);
            return false;
        }
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double)$val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
            --$n;
        }
        return sqrt($carry / $n);
    }
}