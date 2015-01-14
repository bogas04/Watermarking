<?php include 'data.php'; ?>
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
      .table-container { width:1000px; height: 500px; }
      table { float : left; max-width: 250px; }
      h1 { text-align: center; }
      thead tr, tbody tr { display: inline-table; width: 100%; }
      tbody { display:block; max-height:400px; overflow:auto; }
      thead { background: lightgrey; display:block; }
    </style>
  </head>
  <body>
  <h1>Implementation of Distortion Free Watermarking Algorithm</h1>
  <strong> Secret Key </strong>: <?php echo $secret_key; ?> 
  <strong> Partition Count </strong>: <?php echo $partition_count; ?>
  <strong> Watermark </strong>: <?php echo $watermark; ?>
  <strong> Tuple Count </strong>: <?php echo count($records); ?>
  <strong> Hash Type </strong> : MD5 <br>

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
        <tr><th>id</th><th>name</th><th>age</th></tr></thead>
      <tbody>
        <?php
          foreach($records as $r) {
            echo "<tr><td>{$r['uid']}</td><td>{$r['name']}</td><td>{$r['age']}</td></tr>";
          }
        ?>
      </tbody>
    </table>


    <!-- HASHED DATA -->
    
    <table>
      <thead>
        <tr><th>Hashed data</th></tr>
      </thead>
      <tbody>
        <tr>
          <td>
          <?php
            foreach($partitions as $key => $value) { 
              if($watermark[$value['watermarking_bit']] == 1) {
                krsort($partitions[$key]['records'], SORT_STRING);
              } else {
                ksort($partitions[$key]['records'], SORT_STRING);
               }
            }
            echo "<pre>",print_r($partitions),"</pre>";
          ?>
          </td>
        </tr>
      </tbody>
    </table>

  </div>

  <?php
    #GENERATING NEW DATA BASE
    
    $data = array(0,1);
    $data[0] = array();
    $data[1] = array();
    foreach($records as $r) {
      $data[0][] = $r['uid'];
    }
    foreach($partitions as $value) {
      foreach($value['records'] as $uid) {
        $data[1][] = $uid;
      }
    }
    $new_database = array();
    foreach($data[1] as $index => $value) {
      $new_database[$index]['uid'] = $value;
      $new_database[$index]['name'] = $records[$value]['name'];
      $new_database[$index]['age'] = $records[$value]['age']; 
    }
  ?>


  <!-- NEW DATABASE -->
  <hr>

  <div class="table-container">
    <table>
      <thead>
        <tr><th>New database</th></tr>
        <tr><th>id</th><th>name</th><th>age</th></tr></thead>
      <tbody>
        <?php
          foreach($new_database as $r) {
            echo "<tr><td>{$r['uid']}</td><td>{$r['name']}</td><td>{$r['age']}</td></tr>";
          }
        ?>
      </tbody>
    </table>
    
    <?php
      # GENERATING HASH DATA FOR NEW DATABASE 

      $new_partitions = hashAndWaterMark($new_database);
    ?>


    <!-- NEW HASHED DATA -->
    
    <table>
      <thead>
        <tr><th>New Hashed data</th></tr>
      </thead>
      <tbody>
        <tr>
          <td>
          <?php echo "<pre>",print_r($new_partitions),"</pre>"; ?>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <hr>
  
  <?php

    # GENERATING WATERMARK TABLE


    foreach($new_partitions as $index => $data) {
      if(!$watermark_table[$data['watermarking_bit']]) {
        $watermark_table[$data['watermarking_bit']] = array( "0" => 0, "1" => 0 );
      }
      if(strcmp(array_keys($data['records'])[0],array_keys($data['records'])[1]) <= 0) {
        # increasing order
        $watermark_table[$data['watermarking_bit']][0]++;
      } else {
        # decreasing order
        $watermark_table[$data['watermarking_bit']][1]++;
      }
    }
    ksort($watermark_table);
  ?>
  <p> Now we count the number of 1s and 0s obtained by checking the order of hashes in new database. This will help us extract the watermark </p>

  <!-- WATERMARK TABLE -->

  <table>
    <thead><tr><th>Watermark</th><th># of 0s</th><th># of 1s</th></tr></thead>
    <tbody>
      <?php
        foreach($watermark_table as $index => $data) {
          echo "<tr><td>$index</td><td>{$data[0]}</td><td>{$data[1]}</td></tr>";
        }
      ?>
    </tbody>
  </table>
 </body>
</html>
