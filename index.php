<?php
include_once("functions/deviation.php");

$deviation = new Deviation(json_decode(file_get_contents("generatedFiles/generatedInformation.json")), true);
$deviationResults = $deviation->getDeviatingStatistics();
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
    <?php foreach ($deviationResults["short"] as $operation => $time): ?>
        <tr class="<?php if ($time["amount"] < 2) {
            echo 'disabled';
        } elseif ($time["advice"] == "Goed inplanbaar") {
            echo 'good';
        } ?>">
            <td><?= $operation ?></td>
            <td><?= $time["amount"] ?></td>
            <td><?= $time["mean"] ?></td>
            <td><?= $time["meanPlanned"] ?></td>
            <td><?= intval($time["mean"] - $time["standardDev"]) ?>
                - <?= intval($time["mean"] + $time["standardDev"]) ?></td>
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
    <?php foreach ($deviationResults["medium"] as $operation => $time): ?>
        <tr class="<?php if ($time["amount"] < 2) {
            echo 'disabled';
        } elseif ($time["advice"] == "Goed inplanbaar") {
            echo 'good';
        } ?>">
            <td><?= $operation ?></td>
            <td><?= $time["amount"] ?></td>
            <td><?= $time["mean"] ?></td>
            <td><?= $time["meanPlanned"] ?></td>
            <td><?= intval($time["mean"] - $time["standardDev"]) ?>
                - <?= intval($time["mean"] + $time["standardDev"]) ?></td>
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
    <?php foreach ($deviationResults["long"] as $operation => $time): ?>
        <tr class="<?php if ($time["amount"] < 2) {
            echo 'disabled';
        } elseif ($time["advice"] == "Goed inplanbaar") {
            echo 'good';
        } ?>">
            <td><?= $operation ?></td>
            <td><?= $time["amount"] ?></td>
            <td><?= $time["mean"] ?></td>
            <td><?= $time["meanPlanned"] ?></td>
            <td><?= intval($time["mean"] - $time["standardDev"]) ?>
                - <?= intval($time["mean"] + $time["standardDev"]) ?></td>
            <td><?= $time["advice"] ?></td>
            <td class="scrollable"><?= implode("|", $time["real"]) ?></td>
            <td class="scrollable"><?= implode('|', $time["removed"]) ?></td>

        </tr>
    <?php endforeach; ?>
    </tbody>
</table>