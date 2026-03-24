<?php
$ch = curl_init("https://ve.dolarapi.com/v1/historicos/dolares/oficial");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
curl_close($ch);
$data = json_decode($res, true);
echo "Total records: " . count($data) . "\n";
if(count($data)>0) {
    echo "First record: \n";
    print_r($data[0]);
    echo "Last record: \n";
    print_r($data[count($data)-1]);
}
?>
