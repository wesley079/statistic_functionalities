<?php
//new array
$newData = [];

//go through each operation
foreach (json_decode(file_get_contents("generatedFiles/generatedInformation.json")) as $case) {

    $key = "Verrichting 2";
    if($case->$key == null) {

        //add to new array
        array_push($newData, $case);
    }
}

//update file with operation time added
file_put_contents("generatedFiles/generatedInformation.json", json_encode($newData));
