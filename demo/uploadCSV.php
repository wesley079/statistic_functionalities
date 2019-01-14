<?php
include_once('generators/generateInformation.php');

//generate new information
if(isset($_POST["submit"])) {
    if ( isset($_FILES["csv"])) {

        //if there was an error uploading the file
        if ($_FILES["csv"]["error"] > 0) {
            echo "Mistake. Return Code: " . $_FILES["csv"]["error"] . "<br />";

        }
        else {
            //Store file in directory "upload" with the name of "uploaded_file.txt"
            new AnonymousJson($_FILES["csv"]["tmp_name"]);
            unlink($_FILES["csv"]["tmp_name"]);

            echo "Upload succesfull";
        }
    } else {
        echo "No file selected";
    }
}
?>

    <!DOCTYPE html>
    <html>
    <body>
    <a href="index.php"><button>Back to demo startscreen</button></a><br/><br/>
    <h1>Upload CSV</h1>
    <p>For operation specific data the following data will be made anonymous</p>
    <ul>
        <li>Casusnummer</li>
        <li>Patiënt naam</li>
        <li>Patiënt geboortedatum</li>
        <li>Patiënt geslacht</li>
        <li>Patiënt nummer</li>
        <?php
        $array =  [
            "2e Anesth. Assist. 1",
            "2e Anesthesist 1",
            "2e Omloop 1",
            "2e operateur 1",
            "Aflos Anesthesie 1",
            "Aflos chirurgie 1",
            "Assistent 1",
            "Anesthesist 1",
            "Anesthesist 2",
            "Anesth. Assistent 1",
            "Anesth. Assistent 2",
            "Anesth. Assistent 3",
            "Assistent operateur 1",
            "Omloop 1",
            "Omloop 2",
            "Omloop 3",
            "Instrumenterend 1",
            "Instrumenterend 2",
            "Gast operateur 1",
            "Leerling Anesth. 1",
            "Leerling Chirurgie 1",
            "Oacv 1",
            "Assisterend 1",
            "OK-personeel 1",
            "Oogassistent 1",
            "Pacemakertechnicus 1",
            "Klin. Perfusionist 1",
            "Specialist 1",
            "Operateur 1",
            "Operateur 2",
            "Operateur 3",
            "Operateur 4",
            "Supervisor 1",
            "Waarnemend Operateur 1",
        ];
        foreach($array as $item){
            echo '<li>' . $item . '</li>';
        }
        ?>
    </ul>


    <form action="uploadCSV.php" method="post" enctype="multipart/form-data">
        Select your .csv file
        <input type="file" name="csv" id="csv" accept=".csv">
        <input type="submit" value="CSV uploaden" name="submit">
    </form>

    </body>
    </html>

<?php