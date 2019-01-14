<?php
include '../functions/deviation.php';

//example 1
$options = [
    "FileToCheck" => json_decode(file_get_contents("generatedFiles/generatedInformation.json")),
    "KeyToSelect" => "Verrichting 1",
    "KeyToSearchFor" => "Operatieduur",
    "RemoveOutliers" => true,
    "SecondComparison" => true,
    "KeyToCompareMean" => "Geplande duur",
    "FirstCategoryMax" => 20,
    "MiddleCategoryMax" => 60,
    "FirstPercentageMeasure" => 20,
    "MiddlePercentageMeasure" => 12.5,
    "LastPercentageMeasure" => 10,
    "PositiveFeedback" => "Wel goed in te schatten",
    "NegativeFeedback" => "Niet goed in te schatten"

];
//example data

$deviation = new statisticFunctions\functions\Deviation($options);
$deviationResults = $deviation->getDeviatingStatistics();
?>


<?php foreach ($deviationResults as $caseTitle => $operation): ?>
<h1><?= $caseTitle ?></h1>
<?php if ($operation["amount"] >= 2): ?>
<p>Gemiddelde <?= $options["KeyToSearchFor"] ?> <?= $operation["mean"] ?></p>
<p>There were <?= $operation["amount"] ?> cases that included '<?= $caseTitle ?>' as <?= $options["KeyToSelect"] ?>.
    The data was divided by a standard deviation of ' ~ <?= intval($operation["standardDev"]) ?>' </p>
<p>This means in 68.26% of the cases, <?= $options["KeyToSearchFor"] ?> will be
    between <?= intval($operation["mean"] - $operation["standardDev"] * 1) ?>
    - <?= intval($operation["mean"] + $operation["standardDev"] * 1) ?> </p>
<p>This means in 95,44% of the cases, <?= $options["KeyToSearchFor"] ?> will be
    between <?= intval($operation["mean"] - $operation["standardDev"] * 2) ?>
    - <?= intval($operation["mean"] + $operation["standardDev"] * 2) ?> </p>
<p>This means in 99.72% of the cases, <?= $options["KeyToSearchFor"] ?> will be
    between <?= intval($operation["statisticMin"]) ?> - <?= intval($operation["statisticMax"]) ?></p>
<p>Advies: <?= $operation["advice"] ?></p>

<?php if ($operation["amount"] >= 30): ?>
<p>There were more than 30 values noted. This gives you the opportunity to see more detailed information on
    the following page: <a
            href="correlation.php?searchValue=<?= $caseTitle ?>&keyToSearchFor=<?= $options["KeyToSearchFor"] ?>&keyToSelect=<?= $options["KeyToSelect"] ?>">Correlations</a></p>
        <?php endif; ?>
        <br/>


    <?php else: ?>
        <p>There was only <?= $operation["amount"] ?> case that included '<?= $caseTitle ?>'
            as <?= $options["KeyToSelect"] ?>.
            This is to few cases to give feedback about '<?= $caseTitle ?>'</p>
        <br/>

    <?php endif; ?>

<?php endforeach; ?>

