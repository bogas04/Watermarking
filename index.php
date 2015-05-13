<?php 
include 'data.php';
$records = getDataFrom("original_addition_2");
$partitions = hashAndWaterMark($records); 
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Watermarking - Distortion Free</title>
    <style>
    table,td,th { 
      border: 1px solid black; 
      margin:10px; 
      padding:5px; 
      border-collapse:collapse 
    }
    .timestamp {
      text-align: center;
      clear: both;
      border: 1px dashed grey;
      margin: 0px;
      width: 98vw;
      background-color: gainsboro;
      color: black;
      padding: 5px;
    }
    .table-container {
      width:1000px;
      min-height: 500px;
    }
    table {
      float : left;
      max-width: 250px;
    }
    h1 {
      text-align: center;
    }
    thead tr, tbody tr {
      display: inline-table;
      width: 100%;
    }
    tbody {
      display:block;
      max-height:400px;
      overflow:auto;
    }
    thead {
      background: lightgrey;
      display:block;
    }
    </style>
  </head>
  <body>
  <h1>Implementation of Distortion Free Watermarking Algorithm</h1>
  <strong> Secret Key </strong>: <?php echo $secret_key; ?> 
  <strong> Partition Count </strong>: <?php echo $partition_count; ?>
  <strong> Watermark </strong>: <?php echo $watermark; ?>
  <strong> Tuple Count </strong>: <?php echo count($records); ?>
  <strong> Hash Type </strong> : MD5 (stripped to length 7) <br>

  <p>
    The hashed data's index tells the partition number, and each partition number has a watermarking bit index (0 to length of watermark - 1). Based bit at that index, the MD5 hashes of (secret_key . uid. secret_key) are sorted.
  Each hash value points to a uid of which it is formed from.
  </p> 
  <p> 
    <strong>NOTE</strong> : No data has been affected, this highlights the distortion free characteristic.
  </p> 

  <div class="table-container">

    <!-- ORIGINAL DATABASE -->

    <table>
      <thead>
        <tr><th>Original Database</th></tr>
      <tbody>
        <?php render($records); ?>
      </tbody>
    </table>


  <?php
    #GENERATING NEW DATA BASE
    $new_database = getFromPartitions($partitions,isset($_GET['to'])?true:false, $_GET['to']);
  ?>


  <!-- NEW DATABASE -->

    <table>
      <thead>
        <tr><th>New database</th></tr>
      <tbody>
        <?php render($new_database); ?>
      </tbody>
    </table>
  </div>  
    <?php
      # GENERATING HASH DATA FOR NEW DATABASE 

      $new_partitions = hashAndWaterMark($new_database);

      # GENERATING WATERMARK TABLE
      $time = microtime(true);
      $extracted_watermark = array();
      foreach($partitions as $index => $data) {
        $increasingCount = 0;
        $decreasingCount = 0;
        $keys = array_keys($data['records']);
        for($i = 1; $i < count($data['records']); $i++) {
           if($keys[$i] > $keys[$i - 1]) {
             $increasingCount++;
           } else {
             $decreasingCount++; 
           }
        }
        $partitions[$index]['increasing_count'] = $increasingCount;
        $partitions[$index]['decreasing_count'] = $decreasingCount;
        
        if(!$extracted_watermark[$data['watermarking_bit']]) {
          $extracted_watermark[$data['watermarking_bit']] = array("0" => 0, "1" => 0);
        }
        if($increasingCount > (count($data['records'])/2)) {
          $extracted_watermark[$data['watermarking_bit']]['0']++;
        } else {
          $extracted_watermark[$data['watermarking_bit']]['1']++;
        }
      }
      ksort($extracted_watermark);
      echo "<div class='timestamp'>Watermark Extraction Time : ".(microtime(true)-$time) ." s</div>"
   ?>
  <p> Now we count the number of 1s and 0s obtained by checking the order of hashes in new database. This will help us extract the watermark </p>

  <!-- WATERMARK TABLE -->

  <table>
    <thead><tr><th>Watermark</th><th># of 0s</th><th># of 1s</th></tr></thead>
    <tbody>
      <?php
        foreach($extracted_watermark as $index => $data) {
          echo "<tr><td>$index</td><td>{$data[0]}</td><td>{$data[1]}</td></tr>";
        }
      ?>
    </tbody>
  </table>

  <!-- PARTITION COUNT -->

  <table>
    <thead><tr><th>Partition #</th><th>Records</th></tr></thead>
    <tbody>
      <?php
        foreach($partitions as $index => $p) {
          echo "<tr><td>$index</td><td>". count($p['records']) ."</td></tr>";
        }
      ?>
    </tbody>
  </table>
  
 </body>
</html>
