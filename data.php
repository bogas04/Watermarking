<?php
  $secret_key = isset($_GET['secret_key'])?$_GET['secret_key']:"secrettexthere1234";
  $partition_count = isset($_GET['partition_count'])?$_GET['partition_count']:50;
  $watermark = isset($_GET['watermark'])?$_GET['watermark']:"101";

  function getDataFrom($filename) {
    $data_file = file($filename);
    $data_from_db = array();
    foreach($data_file as $key => $line) {
      $d = explode(" ", $line);
      if(count($d) == 2) {
        $d = array( "uid" => $key, "name" => $d[0], "age" => $d[1] );
      } else {
        $d = array( "uid" => $key, "name" => $d[0]. " " . $d[1], "age" => $d[2] );
      }
     $data_from_db[$key] = $d;
    }
    return $data_from_db;
  }

  function hashAndWaterMark($records) {
    global $secret_key;
    global $partition_count;
    global $watermark;

    $partitions = array();

    // find hashes, partition number and watermarking bit for all records
    
    foreach($records as $index => $record) {
      $hash = md5($secret_key . $record['uid'] . $secret_key);
      $partition_number = $hash % $partition_count;

      if(!$partitions[$partition_number]) {
        $partitions[$partition_number] = Array(
          'watermarking_bit' => md5($secret_key . $partition_number) % strlen($watermark),
          'records' => Array()
        );
      }
      $partitions[$partition_number]['records'][$hash] = $record['uid'];
    }  

    ksort($partitions);
    return $partitions;
  }

  
  $good_data = getDataFrom("data.txt");
  
  $bad_data = getDataFrom("baddata.txt");

  $records = $good_data;

  $partitions = hashAndWaterMark($good_data);
  
  $bad_partitions = hashAndWaterMark($bad_data);
?> 
