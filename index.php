<?php
include_once("functions/correlation.php");
include_once("functions/Deviation.php");

$correlation = new Correlation(json_decode(file_get_contents("generatedFiles/generatedInformation.json")), str_replace('%plus%', '+', $_GET['operation']));

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
<h1><?= str_replace('%plus%', '+', $_GET["operation"]); ?></h1>
<?php $all = $correlation->calculateCorrelations();?>
<?php foreach($all as $result): ?>
    <?= $correlation->getCorrelationAdvise($result["coefficient"], $result["xTitle"], $result["yTitle"]); ?><br/><br/>
<?php endforeach; ?>

