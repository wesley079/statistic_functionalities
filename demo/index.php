<html>
<head>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css"/>
</head>
<body>
<style>
    .margin-top-30{
        margin-top: 30px;
    }
</style>
<div class="container">
    <h1 class="row">Statistic functionalities demo</h1>

    <div class="margin-top-30">
        <h2 class="row">1) You can upload files here</h2>
        <a href="../uploadJson.php"><button class="">JSON file</button></a>
        <a href="../uploadCSV.php"><button class="">CSV file</button></a>
    </div>

    <div class="margin-top-30">
        <h2 class="row">2) Is this a NewCompliance export?</h2><br/>
        <a href="NewComplianceOnly/newcomplianceDemo.php?option2=true"><button>Yes, calculate extra keys</button></a>
    </div>

    <div class="margin-top-30">
        <h2 class="row">3) You can view results here</h2>
        <span class="row">Be sure to upload a file first</span>
        <div class="row"><a href="deviation.php"><button>Results</button></a></div>
    </div>
</div>
</body>
</html>