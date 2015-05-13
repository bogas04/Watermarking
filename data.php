<?php
  $secret_key = isset($_GET['secret_key'])?$_GET['secret_key']:"secret";
  $partition_count = isset($_GET['partition_count'])?$_GET['partition_count']:50;
  $watermark = isset($_GET['watermark'])?$_GET['watermark']:"1011001";

  function getHash($a) {
    return hexdec(substr(md5($a), 0, 7));
  }

  function getDataFrom($table) {
    $time = microtime(true);
    
    $mysqli = new mysqli("127.0.0.1", "root", "", "watermarking");
  
    if($mysqli->connect_errno) {
      printf("Connect failed: %s\n", $mysqli->connect_error);
      exit();
    }
  
    $query = "SELECT * FROM $table";

    if ($result = $mysqli->query($query)) {
      $data = $result->fetch_all(MYSQLI_ASSOC);
      $mysqli->close();
      echo "<div class='timestamp'>getDataFrom Time : " . (microtime(true) - $time). " s </div>";
      return $data;
    }
  }

  function hashAndWaterMark($records, $embed = true) {
    $time = microtime(true);
    global $secret_key;
    global $partition_count;
    global $watermark;

    $partitions = array();

    // find hashes, partition number and watermarking bit for all records
    
    foreach($records as $index => $record) {
      $hash = getHash($secret_key . $record['uid'] . $secret_key);
      $partition_number = $hash % $partition_count;

      if(!$partitions[$partition_number]) {
        $partitions[$partition_number] = Array(
          'watermarking_bit' => getHash($secret_key . $partition_number) % strlen($watermark),
          'records' => Array()
        );
      }
      $partitions[$partition_number]['records'][$hash] = $record;
    }  

    ksort($partitions);

    if($embed) {
      foreach($partitions as $key => $value) { 
        if($watermark[$value['watermarking_bit']] == 1) {
          krsort($partitions[$key]['records'],SORT_NUMERIC);
        } else {
          ksort($partitions[$key]['records'],SORT_NUMERIC);
        }
      }
    }
    echo "<div class='timestamp'>hashAndWatermark Time : " . (microtime(true) - $time). " s </div>";
    return $partitions;
  }
  function render($record) {
    foreach($record as $r) {
      echo "<tr>";
      foreach(array_keys($r) as $key) {
        echo "<td>{$r[$key]}</td>";
      }
      echo "</tr>";
    }
  }
  function getFromPartitions($partition, $save = false, $tableName) {
    $time = microtime(true);
    $new_records = array();
    $mysqli = 0;
    
    if($save) {
      $mysqli = new mysqli("127.0.0.1", "root", "", "watermarking");
  
      if($mysqli->connect_errno) {
        printf("Connect failed: %s\n", $mysqli->connect_error);
        exit();
      }

      foreach($partition as $p) {
        foreach($p['records'] as $r) {
          $new_records[] = $r; 
          $query = "INSERT INTO $tableName (`uid`, `rec_no`, `fldnam`, `coll_date`, `fldnam_an`, `descript`) VALUES 
                    ('{$r['uid']}','{$r['rec_no']}','{$r['fldnam']}','{$r['coll_date']}','{$r['fldnam_an']}','".$mysqli->real_escape_string($r['descript'])."')";
          if(!$res = $mysqli->query($query)) {
            print_r($res);
            
            die($query. ' : '. $mysqli->error. " can't save");
          }
        } 
      }
    } else {
      foreach($partition as $p) {
        foreach($p['records'] as $r) {
          $new_records[] = $r; 
        } 
      }
    }
    echo "<div class='timestamp'>getFromPartitions Time : " . (microtime(true) - $time). " s </div>";
    return $new_records;  
  }

?> 
