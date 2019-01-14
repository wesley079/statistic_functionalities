#Statistic functionalities
<b>This repository is made for solutions involving software from NewCompliance B.V Group</b>
<br/><br/>
The functions included in this library will calculate deviation and correlation in from a JSON file.

The demo included will give you an example on how to make use of this library

## #1 Deviation
<b>Include the library:</b>

Use 'statisticFunctionalities\Deviation';

<b>Adjust the options as wanted</b>
```
//example for setting the options
$options = [
    "FileToCheck"               => json_decode(file_get_contents("../generatedFiles/generatedInformation.json")),
    "KeyToSelect"               => "Verrichting 1",
    "KeyToSearchFor"            => "Operatieduur",
    "RemoveOutliers"            => true,
    "SecondComparison"          => true,
    "SecondKeyToFindDeviation"  => "Geplande duur",
    "FirstCategoryMax"          => 20,
    "MiddleCategoryMax"         => 60,
    "FirstPercentageMeasure"    => 20,
    "MiddlePercentageMeasure"   => 12.5,
    "LastPercentageMeasure"     => 10,
    "PositiveFeedback"          => "Wel goed in te schatten",
    "NegativeFeedback"          => "Niet goed in te schatten"

];
```
<b>Initialize the deviation class</b>
```
$deviation = new statisticFunctions\Deviation($options);
```

<b>Get the results</b>
```
$deviationResults = $deviation->GetDeviationStatistics();
```
<h2>What does my result contain?</h2>

<p>The following keys will be available from all results in the returned array<br/><br/>
<b>amount</b> - the amount of measured cases<br/><br/>
<b>mean</b> - the mean of all meaasured numbers<br/><br/>
<b>standardDev</b> - standard deviation (1 time) <br/><br/>
<b>statisticMin</b> - mean - (standarddeviation * 3)<br/><br/>
<b>statisticMax</b> - mean + (standarddeviation * 3)<br/><br/>
<b>lowerThanComparison</b> -  Amount of cases where the number lower than the comparison number<br/><br/>
<b>higherThanComparison</b> - Amount of cases where the number was higher than the comparison number<br/><br/>
<b>Advice</b> - Advice as stated at the settings (positive or negative feedback). If (standarddeviation * 3) is smaller than (mean * percentage), it will be positive. The percentage will be taken from the percentageMeasure based on categories.
<p/>


<h3>Example on how to treat the results</h3>

```
foreach($deviationResults as $result){
    <p>
    There were <?= $operation["amount"] ?> cases that included '<?= $caseTitle ?>' as <?= $options["KeyToSelect"] ?>.
        
    The data was divided by a standard deviation of ' ~ <?= intval($operation["standardDev"]) ?>' 
    </p>
    
    <p>
    This means in 68.26% of the cases, <?= $options["KeyToSearchFor"] ?> will be
    between <?= intval($operation["mean"] - $operation["standardDev"] * 1) 
    ?>
}
``` 
## #2 Correlation
<p>Change options to your personal wishes and settings</p>

<b>Include the library:</b>

Use 'statisticFunctionalities\Correlation';

<b>Adjust the options as wanted</b>
```
//example for setting the options
$options = [
    "FileToCheck"           => json_decode(file_get_contents("../generatedFiles/generatedInformation.json")),
    "KeyToSearchFor"        => "Operatieduur",
    "KeyToSelect"           => "Verrichting 1",
    "ValueForKeyToSelect"   => "Verwijderen buisjes uit oren",
    "ExcludeKeywords"       => ["Patiëntnummer", "Casusnummer"]
];
```
<b>Initialize the deviation class</b>
```
$correlation = new statisticFunctions\Correlation($options);
```

<b>Get the results</b>

```
$results = $correlation->calculateCorrelations();
```

<h2>What does my result contain?</h2>
<b>xTitle</b> - Title of the x-axis that was being measured<br/><br/>
<b>yTitle</b> - Title of the y-axis that was being measured<br/><br/>
<b>coefficient</b> - the correlation coefficient between the values of xTitle and yTitle

<h3>These results can be used to get a advise if wanted<h3/>
```
<!--Example on how to treat the data-->
<?php foreach ($all as $result): ?>
    <?= $correlation->getCorrelationAdvise($result["coefficient"], $result["xTitle"], $result["yTitle"]); ?><br/><br/>
    //Result: The variable 'Operatieduur' looks like growing when 'Wond open (min)' is higher. The correlation is stated as moderate correlation: 0.51029910576627

<?php endforeach; ?>

//
```

