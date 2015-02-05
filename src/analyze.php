<?php

date_default_timezone_set('UTC');

$result = [];
for ($key = 2; $key <= 11; $key++) {
  $data = [];
  foreach (glob('result/*') as $file) {
    $id = str_replace(['result/', '.txt'], '', $file);
    foreach (explode("\n", trim(file_get_contents($file))) as $line) {
      $line = explode("\t", $line);
      if (trim($line[0]) == '') continue;
      $data[$line[0]][sprintf("%.4f%s", floatval($line[$key]) + 5, $id)] =
          $line;
    }
  }

  ksort($data);
  foreach ($data as $month => $report) {
    krsort($report);
    $report = array_values($report);
    $score = 0;
    $count = 0;
    for ($i = 0; $i < 10; $i++) {
      $score += $report[$i][1];
      $count++;
    }
    $result[$month][$key] = $score / $count;
  }
}

$total = [];
foreach ($result as $month => $report) {
  echo "$month";
  foreach ($report as $key => $score) {
    $total[$key] += $score;
    printf("\t%.4f", $score);
  }
  echo "\n";
}
echo "Total";
foreach ($total as $key => $score) {
  printf("\t%+d%%", (exp($score / count($result) * 12) - 1.0) * 100);
}
echo "\n";
