#!/usr/bin/php
<?php
error_reporting(0);
require_once('DbParameters.php');
while (true) {
    try {
        if (isset($argv[1]) && isset($argv[2])) {
            $connection = new MongoDB\Driver\Manager("mongodb://{$argv[1]}:{$argv[2]}");
        } else {
            $connection = new MongoDB\Driver\Manager("mongodb://localhost:27017");
        }

        while (true) {
            echo "> ";
            $handle = fopen("php://stdin", "r");
            $string = strtolower(fgets($handle));

            if ($string == "\n") {
                continue;
            }
            $string = rtrim($string);
            $temp = explode(" ", $string);
            $query = [];
            foreach ($temp as $element) {
                if (isset($element) && $element != "") {
                    array_push($query, $element);
                }
            }

            $dbParams = new DbParameters();

            $filter = $dbParams->createFilter($query);
            $options = $dbParams->createOption($query);

            $query_mongo = new MongoDB\Driver\Query($filter, $options);
            if (isset($argv[3])) {
                $mongoResult = $connection->executeQuery("{$argv[3]}." . $dbParams->collectionName, $query_mongo);
            } else {
                $mongoResult = $connection->executeQuery("test." . $dbParams->collectionName, $query_mongo);
            }

            foreach ($mongoResult as $row) {
                //print_r($row);
                print json_encode($row, JSON_PRETTY_PRINT);
                print("\n");
            }
        }
    } catch (Exception $e) {
        print(">> Syntax error");
        echo "\n";
    }
}
?>