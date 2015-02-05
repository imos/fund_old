<?php

$files = glob('data/*');
shuffle($files);

foreach ($files as $file) {
  $outputs[] = str_replace('data/', 'result/', $file);
}

exec("make -j 3 " . implode(' ', $outputs));
