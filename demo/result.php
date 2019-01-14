<?php
include_once("../functions/deviation.php");

//example 1
$options = [
    "FileToCheck" => json_decode(file_get_contents("../generatedFiles/generatedInformation.json")),
    "KeyToSelect" => "Verrichting 1",
    "KeyToSearchFor" => "Operatieduur",
    "RemoveOutliers" => true,
    "SecondComparison" => true,
    "SecondKeyToFindDeviation" => "Geplande duur",
    "FirstCategoryMax" => 20,
    "MiddleCategoryMax" => 80,
    "FirstPercentageMeasure" => 30,
    "MiddlePercentageMeasure" => 12.5,
    "LastPercentageMeasure" => 10

];
//example 2

$options = [
    "FileToCheck" => json_decode(file_get_contents("../generatedFiles/generatedInformation.json")),
    "KeyToSelect" => "MeasureName",
    "KeyToSearchFor" => "Value",
    "RemoveOutliers" => true,
    "SecondComparison" => false,
    "SecondKeyToFindDeviation" => null,
    "FirstCategoryMax" => 1,
    "MiddleCategoryMax" => 1000,
    "FirstPercentageMeasure" => 20,
    "MiddlePercentageMeasure" => 12.5,
    "LastPercentageMeasure" => 10

];


$deviation = new Deviation($options["FileToCheck"], $options["KeyToSelect"], $options["KeyToSearchFor"], $options["RemoveOutliers"], $options["SecondComparison"], $options["SecondKeyToFindDeviation"]);

//change duration
$deviation->shortDuration = $options["FirstCategoryMax"];
$deviation->mediumDuration = $options["MiddleCategoryMax"];

//change percentage
$deviation->shortPercentage = $options["FirstPercentageMeasure"];
$deviation->mediumPercentage = $options["MiddlePercentageMeasure"];
$deviation->longPercentage = $options["LastPercentageMeasure"];

//change feedback
$deviation->positiveFeedback = "Goed in te schatten";   //default value = "Goed"
$deviation->negativeFeedback = "Niet goed in te schatten"; //default value = "Niet goed"

$deviationResults = $deviation->getDeviatingStatistics();


$possibleResultKeys = ["short", "medium", "long"];

foreach($possibleResultKeys as $currentKey):
?>

<?php foreach ($deviationResults[$currentKey] as $caseTitle => $time): ?>
    <h1><?= $caseTitle ?></h1>
    <?php if ($time["amount"] >= 2): ?>
        <p>There were <?= $time["amount"] ?> cases that included '<?= $caseTitle ?>' as <?= $options["KeyToSelect"] ?>.
            The data was divided by a standard deviation of ' ~ <?= intval($time["standardDev"]) ?>' </p>
        <p>This means in 68.26% of the cases, <?= $options["KeyToSearchFor"] ?> will be
            between <?= intval($time["mean"] - $time["standardDev"] * 1) ?>
            - <?= intval($time["mean"] + $time["standardDev"] * 1) ?> </p>
        <p>This means in 95,44% of the cases, <?= $options["KeyToSearchFor"] ?> will be
            between <?= intval($time["mean"] - $time["standardDev"] * 2) ?>
            - <?= intval($time["mean"] + $time["standardDev"] * 2) ?> </p>
        <p>This means in 99.72% of the cases, <?= $options["KeyToSearchFor"] ?> will be
            between <?= intval($time["statistic_min"]) ?> - <?= intval($time["statistic_max"]) ?></p>
        <p>Advies: <?= $time["advice"] ?></p>

        <?php if ($time["amount"] >= 30): ?>
            <p>There were more than 30 values noted. This gives you the opportunity to see more detailed information on
                the following page: <a
                        href="correlation-demo.php?searchValue=<?= $caseTitle ?>&keyToSearchFor=<?= $options["KeyToSearchFor"] ?>&keyToSelect=<?= $options["KeyToSelect"] ?>">possible
                    correlations</a></p>
        <?php endif; ?>
        <br/>
        <?php ?>
    <?php else: ?>
        <p>There was only <?= $time["amount"] ?> case that included '<?= $caseTitle ?>'
            as <?= $options["KeyToSelect"] ?>.
            This is to few cases to give feedback about '<?= $caseTitle ?>'</p>
        <br/>

    <?php endif; ?>

<?php endforeach; ?>
<?php endforeach; ?>