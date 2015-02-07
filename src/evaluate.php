<?php

if ($argc != 2) {
  die("Usage: $argv[0] file\n");
}

$data = json_decode(file_get_contents($argv[1]), true);

$price_history = [];
$dividend = 0;
foreach ($data['prices'] as $date => $price) {
  if (isset($data['distributions'][$date])) {
    $dividend += intval($data['distributions'][$date]);
  }
  $month = substr($date, 0, 7);
  $price_history[$month][] = intval($price) + $dividend;
}

$file = tempnam('/tmp', 'fund');
define('BOOTSTRAP_PERIOD', intval(getenv('BOOTSTRAP_PERIOD')) ?: 12);

$months = array();
foreach ($price_history as $month => $prices) {
  $months[] = $month;
  $prices = [];
  for ($i = count($months) - 1 - BOOTSTRAP_PERIOD;
       $i < count($months) - 1; $i++) {
    if ($i < 0) break;
    foreach ($price_history[$months[$i]] as $price) {
      $prices[] = $price;
    }
  }
  if (count($prices) > 0) {
    $last_month = $months[count($months) - 2];
    $score = log($price_history[$month][count($price_history[$month]) - 1]) -
             log($price_history[$last_month]
                               [count($price_history[$last_month]) - 1]);
    file_put_contents($file, implode("\n", $prices));
    printf("%s\t%.3f\t", $month, $score);
    system("bin/bootstrap < {$file}");
  }
}
