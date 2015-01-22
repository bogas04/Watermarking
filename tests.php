<?php 
include 'data.php';
$records = getDataFrom(isset($_GET['type'])?$_GET['type']:'watermarked');
$partitions = hashAndWaterMark($records, false); 
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
      .table-container { width:1000px; min-height: 500px; }
      table { float : left; max-width: 250px; }
      h1 { text-align: center; }
      thead tr, tbody tr { display: inline-table; width: 100%; }
      tbody { display:block; max-height:400px; overflow:auto; }
      thead { background: lightgrey; display:block; }
    </style>
  </head>
  <body>
  <h1>Test on <?php echo $_GET['type']; ?> DB</h1>
  <strong> Secret Key </strong>: <?php echo $secret_key; ?> 
  <strong> Partition Count </strong>: <?php echo $partition_count; ?>
  <strong> Watermark </strong>: <?php echo $watermark; ?>
  <strong> Tuple Count </strong>: <?php echo count($records); ?>
  <strong> Hash Type </strong> : MD5 (stripped to length 7) <br>

  <?php
    # GENERATING WATERMARK TABLE
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
  ?>
  <p> Now we count the number of 1s and 0s obtained by checking the order of hashes in new database. This will help us extract the watermark </p>

  <!-- WATERMARK TABLE -->

  <table>
    <thead><tr><th>Watermark</th><th># of 0s</th><th># of 1s</th></tr></thead>
    <tbody>
      <?php
        $ex_wm = "";
        foreach($extracted_watermark as $index => $data) {
          $ex_wm .= $data[0] > $data[1] ? 0 : 1;
          echo "<tr><td>$index</td><td>{$data[0]}</td><td>{$data[1]}</td></tr>";
        }
      ?>
    </tbody>
  </table>

  <!-- PARTITION COUNT -->

  <table>
    <thead><tr><th>Partition #</th><th>Records</th><th>Increasing Count</th><th>Decreasing Count</th><th>Index</th></tr></thead>
    <tbody>
      <?php
        foreach($partitions as $index => $p) {
          echo "<tr><td>$index</td><td>". count($p['records']) ."</td><td> {$p['increasing_count']}</td><td> {$p['decreasing_count']}</td><td>{$p['watermarking_bit']}</td></tr>";
        }
      ?>
    </tbody>
  </table>

  <h3> Original Watermark : <?php echo $watermark; ?> </h3>
  <h3> Extracted  Watermark : <?php echo $ex_wm; ?> </h3>
  
 </body>
</html>
