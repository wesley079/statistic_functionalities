<?php
//new array
$operationsWithTime = [];

//go through each operation
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


    //sort this operation's time
    sort($operationsWithTime[$operation_key]["real"]);
    sort($operationsWithTime[$operation_key]["planned"]);
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
</style>

<table>
    <thead>
    <tr>
        <th>Operatie</th>
        <th>Aantal gevonden</th>
        <th>Bijbehorende operatietijden</th>
        <th>Kortste tijd</th>
        <th>Langste tijd</th>
        <th>Gemiddelde tijd in min</th>
        <th>(gem) Geplande duur<br/>totaal</th>
        <th>Geschatte duur <br/>
            (statistisch)
        </th>
        <th>Goed inplanbaar?</th>
        <th>Dagen</th>
    </tr>
    </thead>
    <tbody>
    <?= count($operationsWithTime); ?>
    <?php foreach ($operationsWithTime as $operation => $time):
        $count = 0;
        $total = 0;
        $last = count($time["real"]) - 1;
        $average = intval(array_sum($time["real"]) / count($time["real"]));
        $adviseNumber = 0.15;
        $min_time = $average - $adviseNumber * $average;
        $max_time = $average + $adviseNumber * $average;
        $singleDeviation = intval(stats_standard_deviation($time["real"]));
        $tripleDeviation = intval(stats_standard_deviation($time["real"]) * 3);

        $plannedTime = intval(array_sum($time["planned"]) / count($time["planned"]));

        $statisticMinimum = $average - intval(stats_standard_deviation($time["real"]) * 3);
        $statisticMaximum = $average + intval(stats_standard_deviation($time["real"]) * 3);

        if($statisticMinimum <= 0){
            $statisticMinimum = 1;
        }

        $advise = "Slecht voorspelbaar";
        if ($tripleDeviation + $average >= $min_time && $tripleDeviation + $average <= $max_time) {
            $advise = "Wel voorspelbaar";
        }

        if (count($time["real"]) < 2) {
            $advise = "Te weinig data";
        }; ?>

        <tr class="<?php if ($advise == "Wel voorspelbaar") {
            echo 'good';
        } ?>">
            <td class="small"><?= $operation ?></td>
            <td><?= count($time["real"]); ?></td>
            <td class="scrollable"><?= implode('|', $time["real"]) ?></td>
            <td><?= $time["real"][0] ?> (min)</td>
            <td><?= $time["real"][$last] ?> (max)</td>
            <td><?= $average ?> (gemiddeld)</td>
            <td><?= $plannedTime ?> (planning)</td>
            <td><?= $statisticMinimum ?> - <?= $statisticMaximum ?> minuten</td>
            <td><?= $advise ?></td>
            <td><?= json_encode($time["days"]) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
