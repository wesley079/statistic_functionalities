<b>This repository is made for solutions involving software from NewCompliance B.V Group</b>
<br/><br/>
The functions included in this repository will only work by valid CSV exports from the analysis tool made by NewCompliance.


<b>How to use</b><br/>
Step 1. Export a .csv analysis involving several surgeries and 'geplande duur' (or do the alternative)* as key possibility<br/>
Step 2. Upload the .csv to uploadCSV.php (all privacy information will be randomized)<br/>
Step 3. Filter all single operations by running the filterAllSingleOperations.php<br/>
Step 4. Calculate the duration by running the calculateDuration.php script<br/>
Step 5. Adjust the public variables in functions/Deviation.php to your own wishes<br/>
Step 5. Open index.php to see the results

<i>*alternative. If 'geplande duur' is not available comment away code in calculateDuration.php (line 22 - 36)</i><br/>
