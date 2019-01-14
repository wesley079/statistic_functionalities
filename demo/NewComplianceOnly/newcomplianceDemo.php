<?php
calculateDuration();
deleteMultipleOperations();

function calculateDuration(){
//new array
$newData = [];

//go through each operation
foreach (json_decode(file_get_contents("../../generatedFiles/generatedInformation.json")) as $case) {

/***
* Real time
***/
$startTime = new DateTime($case->Starttijd);
$endTime = new DateTime($case->Eindtijd);

//calculate difference
$diff = $endTime->diff($startTime);

//show difference in time (hours times 60 minutes)
$case->Operatieduur = ($diff->h * 60) + $diff->i;



/***
*scheduled time
***/
$plannedStartKey = "Gepland start";
$plannedEndKey = "Gepland eind";

$startTimePlanned = new DateTime($case->$plannedStartKey);
$endTimePlanned = new DateTime($case->$plannedEndKey);

//calculate difference
$diffPlanned = $endTimePlanned->diff($startTimePlanned);


$keyPlanned = "Geplande duur";
$case->$keyPlanned = ($diffPlanned->h * 60) + $diffPlanned->i;

//add to new array
array_push($newData, $case);
}

//update file with operation time added
file_put_contents("../../generatedFiles/generatedInformation.json", json_encode($newData));

}

function deleteMultipleOperations(){
    //new array
    $newData = [];

//go through each operation
    foreach (json_decode(file_get_contents("../generatedFiles/generatedInformation.json")) as $case) {

        $key = "Verrichting 2";
        if($case->$key == null) {

            //add to new array
            array_push($newData, $case);
        }
    }

//update file with operation time added
    file_put_contents("../generatedFiles/generatedInformation.json", json_encode($newData));

}

header('Location: '. '../');