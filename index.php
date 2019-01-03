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
    $deviation = stats_standard_deviation($operation["real"]);
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
        $stats_standard_dev = stats_standard_deviation($time["real"]);

        switch (true) {
            case ($averageTime <= 20):
                //percentage that decides whether a operation in this array is too long
                $percentage = 0.2;
                $shortOperations[$operation] = $time;
                $shortOperations[$operation] = addOperationInformation($shortOperations[$operation], $amount, $averageTime, $stats_standard_dev, $percentage);
                break;
            case ($averageTime > 20 && $averageTime <= 60):
                //medium operation
                $percentage = 0.18;
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

    if ($amount < 3) {
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
<h2>Als operaties van 20 minuten maximaal 4 minuten uitlopen krijgen deze een positief advies</h2>
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
        <tr class="<?php if ($time["amount"] < 3) {
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
<h2>Als operaties van 60 minuten maximaal 10,8 minuten uitlopen krijgen deze een positief advies</h2>
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
        <tr class="<?php if ($time["amount"] < 3) {
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
<h2>Als operaties van 120 minuten maximaal 12 minuten uitlopen krijgen deze een positief advies</h2>
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
        <tr class="<?php if ($time["amount"] < 3) {
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