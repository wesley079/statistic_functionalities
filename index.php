<?php
//new array
$operationsWithTime = [];

//Select the times of each operation
foreach (json_decode(file_get_contents("generatedFiles/generatedInformation.json")) as $case) {
    //new item
    $item = [];

    //check if index exists
    $key = "Verrichting 1";
    $operation_key = $case->$key;

    if (!array_key_exists($operation_key, $operationsWithTime)) {
        //Key doesn't exist, make new key and add operation duration
        $operationsWithTime[$operation_key]["real"] = [];
        $operationsWithTime[$operation_key]["planned"] = [];
        $operationsWithTime[$operation_key]["days"] = [];
    }

    //add operation duration to key with the specific time
    array_push($operationsWithTime[$operation_key]["real"], $case->Operatieduur);

    $planned_key = "Geplande duur";
    array_push($operationsWithTime[$operation_key]["planned"], $case->$planned_key);

    array_push($operationsWithTime[$operation_key]["days"], $case->Starttijd);

    $operationsWithTime[$operation_key]["removed"] = [];

    //sort this operation's time
    sort($operationsWithTime[$operation_key]["real"]);
    sort($operationsWithTime[$operation_key]["planned"]);
}

//Remove all outliers
foreach ($operationsWithTime as $key => $operation) {
    $temporary_list = [];
    if(count($operation["real"]) > 1) {
        $deviation = stats_standard_deviation($operation["real"]);
    }
    else{
        $deviation = "Te weinig data";
    }
    $average = array_sum($operation["real"]) / count($operation["real"]);
    foreach ($operation["real"] as $singleKey => $singleNumber) {
        //remove outliers


        if ($singleNumber < $average - (5 * $deviation) || $singleNumber > (5 * $deviation) + $average) {
            array_push($operationsWithTime[$key]["removed"], $singleNumber );
            unset($operationsWithTime[$key]["real"][$singleKey]);

        }
    }
}

//arrays to be filled
$shortOperations = [];
$mediumOperations = [];
$longOperations = [];

//Reorder all operations in short, medium and long time
foreach ($operationsWithTime as $operation => $time) {
    //remove empty operation titles
    if ($operation !== "") {
        $amount = count($time["real"]);
        $averageTime = intval(array_sum($time["real"]) / $amount);
        if(count($time["real"]) > 1) {
            $stats_standard_dev = stats_standard_deviation($time["real"]);
        }
        else{
            $stats_standard_dev = "Te weinig data";
        }
        switch (true) {
            case ($averageTime <= 20):
                //percentage that decides whether a operation in this array is too long
                $percentage = 0.2;
                $shortOperations[$operation] = $time;
                $shortOperations[$operation] = addOperationInformation($shortOperations[$operation], $amount, $averageTime, $stats_standard_dev, $percentage);
                break;
            case ($averageTime > 20 && $averageTime <= 60):
                //medium operation
                $percentage = 0.2;
                $mediumOperations[$operation] = $time;
                $mediumOperations[$operation] = addOperationInformation($mediumOperations[$operation], $amount, $averageTime, $stats_standard_dev, $percentage);
                break;
            case ($averageTime > 60):
                //long operation
                $percentage = 0.1;
                $longOperations[$operation] = $time;
                $longOperations[$operation] = addOperationInformation($longOperations[$operation], $amount, $averageTime, $stats_standard_dev, $percentage);
                break;
        }
    }
}




/**
 *  This function calculates the functions of a sample
 *  NOTE: This function won't work on non-sample arrays
 *
 * @param array $array
 * @return float|bool Standard functions for send array, or false if an error occurred
 */
function stats_standard_deviation(array $array)
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


/***
 * Fill an operation with all needed values
 * Return the array filled with values
 *
 * @param $operations
 * @param $amount
 * @param $averageTime
 * @param $stats_standard_dev
 * @param $percentage
 * @return mixed
 */
function addOperationInformation($operations, $amount, $averageTime, $stats_standard_dev, $percentage)
{
    $operation = $operations;

    $operation["amount"] = $amount;
    $operation["average"] = $averageTime;
    $operation["standardDev"] = $stats_standard_dev;
    $operation["statistic_min"] = ($averageTime - 3 * $stats_standard_dev);
    $operation["statistic_max"] = ($averageTime + 3 * $stats_standard_dev);
    $operation["correct_plan"] = 0;
    $operation["wrong_plan"] = 0;
    $operation["average_planned"] = intval(array_sum($operation["planned"]) / count($operation["planned"]));

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


    if ($stats_standard_dev < ($averageTime * $percentage)) {
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
function orderByLength($a, $b)
{
    if ($a["average"] == $b["average"]) {
        return 0;
    }
    return ($a["average"] < $b["average"]) ? -1 : 1;
}

uasort($shortOperations, "orderByLength");
uasort($mediumOperations, "orderByLength");
uasort($longOperations, "orderByLength");

?>
<style>
    .small {
        width: 240px;
    }

    table {
        text-align: center;
    }

    .good {
        background-color: yellow !important;
    }

    thead tr {
        color: white;
        background: darkslategrey !important;
    }

    tr:nth-child(odd) {
        background: #ccc;
    }

    .scrollable {
        max-width: 500px;
        overflow-x: scroll;
    }

    .disabled {
        opacity: 0.3;
    }
</style>
<h1>Korte operaties (< 20 min)</h1>
<h2>Als operaties van 20 minuten maximaal 4 minuten (20%) uitlopen krijgen deze een positief advies</h2>
<table>
    <thead>
    <tr>
        <th>Verrichting</th>
        <th>Aantal metingen</th>
        <th>Gemiddelde duur</th>
        <th>Gemiddeld gepland</th>
        <th>Verwachte tijdsduur</th>
        <th>Advies</th>
        <th>Gemeten data</th>
        <th>Verwijderde extremen</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($shortOperations as $operation => $time): ?>
        <tr class="<?php if ($time["amount"] < 2) {
            echo 'disabled';
        } elseif ($time["advice"] == "Goed inplanbaar") {
            echo 'good';
        } ?>">
            <td><?= $operation ?></td>
            <td><?= $time["amount"] ?></td>
            <td><?= $time["average"] ?></td>
            <td><?= $time["average_planned"] ?></td>
            <td><?= intval($time["average"] - $time["standardDev"]) ?>
                - <?= intval($time["average"] + $time["standardDev"]) ?></td>
            <td><?= $time["advice"] ?></td>
            <td class="scrollable"><?= implode("|", $time["real"]) ?></td>
            <td class="scrollable"><?= implode('|', $time["removed"]) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<h1>Gemiddelde operaties (20 - 60 min)</h1>
<h2>Operaties mogen 4 tot maximaal 12 minuten uitlopen (20%)</h2>
<table>
    <thead>
    <tr>
        <th>Verrichting</th>
        <th>Aantal metingen</th>
        <th>Gemiddelde duur</th>
        <th>Gemiddeld gepland</th>
        <th>Verwachte tijdsduur</th>
        <th>Advies</th>
        <th>Gemeten data</th>
        <th>Verwijderde extremen</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($mediumOperations as $operation => $time): ?>
        <tr class="<?php if ($time["amount"] < 2) {
            echo 'disabled';
        } elseif ($time["advice"] == "Goed inplanbaar") {
            echo 'good';
        } ?>">
            <td><?= $operation ?></td>
            <td><?= $time["amount"] ?></td>
            <td><?= $time["average"] ?></td>
            <td><?= $time["average_planned"] ?></td>
            <td><?= intval($time["average"] - $time["standardDev"]) ?>
                - <?= intval($time["average"] + $time["standardDev"]) ?></td>
            <td><?= $time["advice"] ?></td>
            <td class="scrollable"><?= implode("|", $time["real"]) ?></td>
            <td class="scrollable"><?= implode('|', $time["removed"]) ?></td>

        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h1>Lange operaties (>60 min)</h1>
<h2>Operaties mogen 6 minuten uitlopen (10%)</h2>
<table>
    <thead>
    <tr>
        <th>Verrichting</th>
        <th>Aantal metingen</th>
        <th>Gemiddelde duur</th>
        <th>Gemiddeld gepland</th>
        <th>Verwachte tijdsduur</th>
        <th>Advies</th>
        <th>Gemeten data</th>
        <th>Verwijderde extremen</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($longOperations as $operation => $time): ?>
        <tr class="<?php if ($time["amount"] < 2) {
            echo 'disabled';
        } elseif ($time["advice"] == "Goed inplanbaar") {
            echo 'good';
        } ?>">
            <td><?= $operation ?></td>
            <td><?= $time["amount"] ?></td>
            <td><?= $time["average"] ?></td>
            <td><?= $time["average_planned"] ?></td>
            <td><?= intval($time["average"] - $time["standardDev"]) ?>
                - <?= intval($time["average"] + $time["standardDev"]) ?></td>
            <td><?= $time["advice"] ?></td>
            <td class="scrollable"><?= implode("|", $time["real"]) ?></td>
            <td class="scrollable"><?= implode('|', $time["removed"]) ?></td>

        </tr>
    <?php endforeach; ?>
    </tbody>
</table>