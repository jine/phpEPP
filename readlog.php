<?php

$data = file_get_contents('log.bin');
$arr = explode(pack('L', 0xFEE1DEAD), $data);

foreach($arr as $xml) {
	if($xml != false) {
		echo gzuncompress($xml);
	}
}