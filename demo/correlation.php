<?php
include '../functions/correlation.php';
include '../functions/deviation.php';

//options to find the correlation
$options = [
    "FileToCheck" => json_decode(file_get_contents("generatedFiles/generatedInformation.json")),
    "KeyToSearchFor" => $_GET["keyToSearchFor"],
    "KeyToSelect" => $_GET["keyToSelect"],
    "ValueForKeyToSelect" => str_replace('%plus%', '+', $_GET['searchValue']),
    "ExcludeKeywords" => ["Patiëntnummer", "Casusnummer"]
];
$correlation = new statisticFunctions\functions\Correlation($options);

$all = $correlation->calculateCorrelations();
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

<h1><?= str_replace('%plus%', '+', $_GET["searchValue"]); ?></h1>

<!--Example on how to treat the data-->
<?php foreach ($all as $result): ?>
    <?= $correlation->getCorrelationAdvise($result["coefficient"], $result["xTitle"], $result["yTitle"]); ?><br/><br/>
<?php endforeach; ?>

