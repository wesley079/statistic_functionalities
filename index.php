<?php
include_once("functions/correlation.php");
include_once("functions/Deviation.php");

$correlation = new Correlation(json_decode(file_get_contents("generatedFiles/generatedInformation.json")), urldecode($_GET['operation']));

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
<h1><?= urldecode($_GET["operation"]); ?></h1>
<?php $all = $correlation->calculateCorrelations(); $dif = 0;?>
<table>
    <thead>
    <th>Duur</th>
    <th><?= $all["y-title"] ?></th>
    <th>Rank X</th>
    <th>Rank Y</th>
    <th>verschil</th>
    <th>verschil ²</th>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($all["x"]); $i++): ?>
        <tr>
            <td><?= $all["x"][$i]["value"] ?></td>
            <td><?= $all["y"][$i]["value"] ?></td>
            <td><?= $all["x"][$i]["rank"] ?></td>
            <td><?= $all["y"][$i]["rank"] ?></td>
            <td><?= $all["x"][$i]["rank"] - $all["y"][$i]["rank"] ?></td>
            <td><?= ($all["x"][$i]["rank"] - $all["y"][$i]["rank"]) * ($all["x"][$i]["rank"] - $all["y"][$i]["rank"]) ?></td>
        </tr>
    <?php $dif += ($all["x"][$i]["rank"] - $all["y"][$i]["rank"]) * ($all["x"][$i]["rank"] - $all["y"][$i]["rank"]);?>
    <?php endfor; ?>
    </tbody>
</table>


