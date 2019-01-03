<?php
include_once("personGenerator.php");

class AnonymousJson
{
    private $staffInformation = [
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
    private $anonymousStaff = [];
    private $anonymousTotal = [];
    private $json;

    /***
     * Input CSV file and get return an private JSON array
     * AnonymousJson constructor.
     * @param $csv
     */
    public function __construct($csv)
    {
        $this->json = $this->csvtojson($csv, ",");

        $this->RandomizeStaff();
        $this->RandomizePatient();

        $file_direction = 'generatedFiles/generatedInformation.json';

        //remove previous file
        unlink($file_direction);
        $file = fopen($file_direction, 'w') or die ('cannot open file' . $file_direction);

        //append to file
        fwrite($file, json_encode($this->anonymousTotal));
    }

    /***
     * Randomize staff in variable $json
     * Randomize case number
     */
    private function RandomizeStaff()
    {
        //randomize the staff private information
        foreach (json_decode($this->json) as $operation) {
            $operation->Casusnummer = mt_rand(0, 100000);

            //anonymize operating staff
            foreach ($this->staffInformation as $key) {
                if (isset($operation->$key) &&$operation->$key != "") {
                    $personGen = PersonRandomizer::getRandomPerson();
                    $operation->$key = $personGen["frontName"] . ' ' . $personGen["lastName"];
                }
            }
            array_push($this->anonymousStaff, $operation);
        }
    }

    /***
     * Randomize patient information
     */
    private function RandomizePatient()
    {
        foreach ($this->anonymousStaff as $operation) {
            $operation = (array)$operation;
            $randomPerson = PersonRandomizer::getRandomPerson();

            //anonymize patient
            $operation["Patiëntnaam"] = $randomPerson["frontName"] . ' ' . $randomPerson["lastName"];
            $operation["Patiëntnummer"] = mt_rand(0, 10000);
            $operation["Patiënt geslacht"] = $randomPerson["gender"];
            $operation["Patiënt geboortedatum"] = $randomPerson["dayOfBirth"];

            array_push($this->anonymousTotal, $operation);
        }
    }

    /***
     * Put CSV files into json
     * @param $file
     * @param $delimiter
     * @return false|string
     */
    private function csvtojson($file, $delimiter)
    {
        if (($handle = fopen($file, "r")) === false) {
            die("can't open the file.");
        }

        $csv_headers = fgetcsv($handle, 4000, $delimiter);
        $csv_json = array();

        while ($row = fgetcsv($handle, 4000, $delimiter)) {
            $csv_json[] = array_combine($csv_headers, $row);
        }

        fclose($handle);
        return json_encode($csv_json);
    }

}


