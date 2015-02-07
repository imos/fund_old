<?php

$files = glob('data/*');
$tmpfile = tempnam('/tmp', 'fund');

$outputs = [];
foreach ($files as $file) {
  foreach ([24, 16, 8, 4, 2, 1] as $period) {
    foreach ([1, 5, 15, 40] as $leap) {
      if (!is_dir("result/$period/$leap")) {
        mkdir("result/$period/$leap", 0777, true);
      }
      $outputs[] = str_replace('data/', "result/$period/$leap/", $file);
    }
  }
}

shuffle($outputs);

file_put_contents(
    $tmpfile,
    "all: " . implode(' ', $outputs) . "\n\n" .
    "%:\n\techo \$* >&2\n\t+make \$*\n");

$parallel = intval(getenv('PARALLEL')) ?: 1;
exec("make -j $parallel -f $tmpfile");
