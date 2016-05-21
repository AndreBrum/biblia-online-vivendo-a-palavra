<?php

/*
Script Name: *Bovp Bible Insert Data Class
Script URI: http://www.vivendoapalavra.org/
Description: PHP class that insert the bible text.
Script Version: 0.1
Author: Andre Brum Sampaio
Author URI: http://www.web117.com.br
*/

class bovp_csv_import {

    #default params
    private $id = -1;
    private $table;
    private $group = 1; 
    public $delimiter = ';';
    public $field_enclosure = '|';
    public $file_name = '';
    private static $instance = NULL;

    #construct
    function bovp_csv_import(){}

    public static function getInstance() {      

            try {

                if (!self::$instance) {

                    self::$instance = new PDO("mysql" . ":host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
                    self::$instance-> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                       
                }
            }

            catch(PDOException $erro) {

                return $erro->getCode();       
            }

            return self::$instance;
    }

    #file name
    function setFileName( $file_name ) {

        if (isset( $file_name ) && !empty( $file_name ) ) { $this->file_name = $file_name; } else { $this->file_name = false; }

    }

    #line delimiter
    function setDelimiter( $delimiter ) {

        if ( isset( $delimiter ) && !empty( $delimiter ) ) { $this->delimiter = $delimiter; } 

    }

    #field delimiter
    function setFieldDelimiter( $field_enclosure ) {

        if ( isset( $field_enclosure ) && !empty( $field_enclosure ) ) { $this->field_enclosure = $field_enclosure; } 

    }  

    #set table to insert
    function setTable( $table ) {

        if ( isset( $table ) && !empty( $table ) ) { $this->table = $table; } 

    }  


    function insertFile() {

        $records = '';

        $tables = 'wp_bovp_en,wp_bovp_id_id,wp_bovp_it,wp_bovp_pt_BR,wp_bovp_es';

        if( !$this->table ){ return false; exit; }

        $bovp_query_drop = "DROP TABLE IF EXISTS ". $tables; 

        $bovp_drop_table = self::getInstance()->query( $bovp_query_drop );

        $bovp_query_table = "CREATE TABLE `". $this->table ."` (`id` int(11) NOT NULL AUTO_INCREMENT,`book` int(11) NOT NULL,`cp` int(11) NOT NULL, `vs` int(11) NOT NULL,`text` longtext NOT NULL, PRIMARY KEY (`id`))"; 
        
        $bovp_create_table = self::getInstance()->query($bovp_query_table);

        if( $this->file_name AND $bovp_create_table ) {

            // open file for ready only
            $text_bible = fopen( $this->file_name.'.csv', 'r' );

            if ( $text_bible ) {

                // read the header
                $header = fgetcsv( $text_bible, 0, $this->delimiter, $this->field_enclosure );

                array_pop( $header );

                $fields = "";

                foreach ( $header as $key => $value ) {

                    $fields .= "`" . $value . "`";

                    $keys = array_keys( $header );

                    if ( $key != end( $keys ) ) { $fields .= ",";}

                }


                // while not EOF
                while ( !feof( $text_bible ) ) { 

                    // read line
                    $cur_line = fgetcsv( $text_bible, 0, $this->delimiter, $this->field_enclosure );

                    if( $cur_line ) {

                        // indexed record
                        $field = array_combine( $header, $cur_line );

                        // Sql prepare
                        $records .=  '('. $field['book'] . ',' . $field['cp'] . ',' . $field['vs'] . ',"' . addslashes($field['text']) . '")';

                    }

                    if( ( ( $this->id+1 )/( 400*$this->group ) )==1 ) {

                        $insert = 'INSERT INTO `' . $this->table . '` ('. $fields .') ' . ' VALUES ' . $records.';';
                        $inserted = self::getInstance()->query( $insert );
                        $this->group++;
                        $records = "";

                                               
                    } else {

                        if( $cur_line ) { $records .=  ",";} else {

                        $insert = 'INSERT INTO `' . $this->table . '` ('. $fields .') ' . ' VALUES ' . substr($records,0,-1).';';
                        $inserted = self::getInstance()->query( $insert );


                        }

                    }

                    $this->id++;
                }

                fclose($text_bible);
            }




        } else { return false;}

        
        $query = self::getInstance()->prepare('SELECT COUNT( `book` ) as "TOTAL" FROM `'. $this->table .'`');
        
        $query->execute();

        $result = $query->fetch(PDO::FETCH_ASSOC);

        $count = $result['TOTAL'];

        if( (int)$count === (int)$this->id ) { 

            return true; 

        } else { 

            self::$instance->query( "DROP TABLE IF EXISTS `" . $this->table . "`" );
            return false; 

        }


    }


}

