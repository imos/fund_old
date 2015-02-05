<?php

date_default_timezone_set('UTC');

function FetchFundCsvSource($fund_id, $is_distribution) {
  $parameters = [
      'in_term_from_yyyy' => '2011',
      'in_term_from_mm' => '01',
      'in_term_from_dd' => '01',
      'in_term_to_yyyy' => date('Y'),
      'in_term_to_mm' => date('m'),
      'in_term_to_dd' => date('d')];
  $content = http_build_query($parameters, '', '&');
  $header = [
      'Content-Type: application/x-www-form-urlencoded',
      'Content-Length: ' . strlen($content)];
  return mb_convert_encoding(file_get_contents(
      $is_distribution
          ? ("https://site0.sbisec.co.jp/marble/fund/history/" .
             "distribution/distributionHistoryCsvAction.do?" .
             "fund_sec_code={$fund_id}")
          : ("https://site0.sbisec.co.jp/marble/fund/history/" .
             "standardprice/standardPriceHistoryCsvAction.do?" .
             "fund_sec_code={$fund_id}"),
      false,
      stream_context_create([
          'http' => [
              'method' => 'POST',
              'header' => implode("\r\n", $header),
              'content' => $content]])), 'UTF-8', 'Shift_JIS');
}

function FetchFundCsv($fund_id, $is_distribution) {
  $data = [];
  $csv = trim(str_replace(
      "\r", '', FetchFundCsvSource($fund_id, $is_distribution)));
  if ($csv == '') {
    die("Response is empty: $fund_id, $is_distribution\n");
  }
  if (strpos($csv, '<html') !== FALSE) {
    die("Failed to fetch ${fund_id}.\n");
  }
  foreach (explode("\n", $csv) as $line) {
    $data[] = str_getcsv($line);
  }
  return $data;
}

function ParseFundCsv($data) {
  if ($data[0][0] != '基準価額一覧' &&
      $data[0][0] != '分配金実績') {
    die("Invalid data: {$data[0][0]}\n");
  }
  $prices = [];
  for ($i = 8; $i < count($data); $i++) {
    $prices[str_replace('/', '-', $data[$i][0])] = $data[$i][1];
  }
  ksort($prices);
  return ['name' => $data[2][1],
          'modified' => time(),
          'modified_str' => gmdate('Y-m-d H:i:s'),
          'prices' => $prices];
}

function FetchFund($fund_id) {
  $path = "data/{$fund_id}.txt";
  if (file_exists($path)) {
    $info = json_decode(file_get_contents($path), true);
    // The data was recently updated.
    if (time() - 14 * 24 * 3600 < $info['modified']) {
      return;
    }
  }
  fwrite(STDERR, "Fetching $fund_id...\n");
  $data = ParseFundCsv(FetchFundCsv($fund_id, false));
  $distributions = ParseFundCsv(FetchFundCsv($fund_id, true));
  $data['distributions'] = $distributions['prices'];
  fwrite(STDERR, "Outputting $fund_id...\n");
  file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT |
                                              JSON_UNESCAPED_UNICODE));
}

function GetFundIds() {
  $html = file_get_contents(
      'https://site1.sbisec.co.jp/ETGate/WPLETmgR001Control?' .
      'getFlg=on&burl=search_fund&cat1=fund&cat2=lineup&' .
      'dir=edeliv&file=fund_edeliv_01.html');
  preg_match_all('%&fund_sec_code=1([\w\d]+)%', $html, $matches);
  return $matches[1];
}

function FetchFunds() {
  $fund_ids = GetFundIds();
  shuffle($fund_ids);
  foreach ($fund_ids as $fund_id) {
    FetchFund($fund_id);
  }
}

FetchFunds();
