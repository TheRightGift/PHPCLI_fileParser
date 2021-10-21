#!/usr/bin/php
<?php
    class Parser {
        function __construct( $argv ) {
            $this->argv = $argv;
            $this->requiredFields = [];
            $this->requiredFieldsPosition = [];
            $this->sourceFile = '';
            $this->destinationFile = '';
            $this->headerRow = [];
            $this->array = [];
            $this->controlArray = [];
        }
    
        function init(){
            $requiredFlagPosition = array_search('--required', $this->argv);

            if($requiredFlagPosition){
                foreach ($this->argv as $key => $value) {
                    if($key > $requiredFlagPosition){
                        array_push($this->requiredFields, $value);
                    }
                }
            } 
            

            $this->sourceFile = $this->argv[1];
            $this->destinationFile = $this->argv[2];

            $this->readSourceFile();
        }
        
        function readSourceFile() {
            $open = fopen($this->sourceFile, "r");
            if ($open !== FALSE) 
            {        
                echo 'Reading '.$this->sourceFile.'...'.PHP_EOL;

                // Set up columns for header row
                $headerHandler = $this->processHeader(fgetcsv($open, 10000, ","));
                if($headerHandler === 1){
                    $rowDataValidationState = 0;
                    // Get product rows
                    while (($data = fgetcsv($open, 1000, ",")) !== FALSE) 
                    {        
                        if(count($this->requiredFields) > 0){
                            if($this->validateRequiredFields($data) === 0){//failed validation
                                $rowDataValidationState = 1;
                                break;
                            } 
                        } 

                        $data[7] = 0;//initialize count column
                        $data[8] = str_replace(' ', '', implode(" ",$data));//initialize a control column
                        array_push($this->array, $data);                        
                    }

                    if($rowDataValidationState === 0){
                        $this->countProducts();
                    } else {
                        echo 'Parsing stopped. A required field is not provided.'.PHP_EOL;
                    }
                    
                }                
            
                fclose($open);
            } else {
                echo 'Error opening '.$this->sourceFile;
            }
        }

        function validateRequiredFields($data){
            $validationErr = 0;
            foreach ($this->requiredFieldsPosition as $pos) {
                if($data[$pos] === ''){
                    $validationErr = 1;
                    break;
                }
            }

            if($validationErr === 0){
                return 1;
            } else {
                return 0;
            }
        }

        function processHeader($header){
            $error = 0;
            if(count($this->requiredFields) > 0){
                foreach ($this->requiredFields as $key => $value) {
                    $searchForRequiredFields = array_search($value, $header);
    
                    if($searchForRequiredFields == false && $searchForRequiredFields !== 0){
                        $error = 1;
                        echo $value.' is not provided. It is a required field.'.PHP_EOL;
                        break;
                    } else {
                        array_push($this->requiredFieldsPosition, $searchForRequiredFields);
                    }
                }
            }
            
            if($error === 0){
                $this->headerRow = $header;
                $this->headerRow[7] = 'count';

                return 1;//successfully processed
            } else {
                return 0;// processed with error
            }
            
        }

        function countProducts() {
            echo 'Counting products...'.PHP_EOL;
            foreach ($this->array as $key => $row) {
                $controlArrayLen = count($this->controlArray);
                if($controlArrayLen < 1){
                    $row[7] += 1;
                    array_push($this->controlArray, $row);
                } else {
                    $found = 0;
                    foreach ($this->controlArray as $index => $controlArrayRow) {
                        if($row[8] == $controlArrayRow[8]){
                            $found = 1;
                            $this->controlArray[$index][7] += 1;
                            break;
                        }
                    }
        
                    if($found == 0){
                        $row[7] += 1;
                        array_push($this->controlArray, $row);
                    }            
                }
            }
            $this->writeDestinationFile();
        }

        function writeDestinationFile() {
            $done = $this->houseCleaning();
            if($done == 1){
                array_unshift($this->controlArray, $this->headerRow);
                $this->writeOperation();
            }
            
        }

        function houseCleaning(){
            echo 'Setting up write operations...'.PHP_EOL;
            foreach ($this->controlArray as $key => $controlArrayRow) {
                unset($this->controlArray[$key][count($controlArrayRow) - 1]);
            }

            return 1;
        }

        function writeOperation(){
            // Open a file in write mode ('w')
            $productCombinationCount = fopen($this->destinationFile, 'w');
            echo 'Writing to destination file.';
            
            // Loop through file pointer and a line
            foreach ($this->controlArray as $index => $fields) {
                echo '.';
                fputcsv($productCombinationCount, $fields);
            }
            
            fclose($productCombinationCount);
        }
    }
    
    $parseObj = new Parser($argv);
    $parseObj->init();
?>