<?php
$acc = 544;  // 220 en hex

// res = 66080 (decimal) / 10220 (hex) -> 220 XOR 10000

$enHex = dechex ($acc);
echo "enHex = ".$enHex."<br>";


//echo "Res en hex :". ('0x220' ^ '0x10000')."<br>";

echo "Res en dec :".(544 ^ 65536);

$control = $control ^ 65536;

//echo "userAccountControl after reconversion to decimal ".$userAccountControl;
?>