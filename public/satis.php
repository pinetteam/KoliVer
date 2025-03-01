<?php
error_reporting(E_ALL ^ E_NOTICE);
$PostUrl      = 'https://onlineodemetest.vakifbank.com.tr:4443/VposService/v3/Vposreq.aspx'; 
$IsyeriNo     = $_POST["IsyeriNo"];
$TerminalNo   = $_POST["TerminalNo"];
$IsyeriSifre  = $_POST["IsyeriSifre"];
$KartNo       = $_POST["KartNo"];
$KartAy       = $_POST["KartAy"];
$KartYil      = $_POST["KartYil"];
$KartCvv      = $_POST["KartCvv"];
$Tutar        = $_POST["Tutar"];
$SiparID      = $_POST["SiparID"];
$IslemTipi    = $_POST["IslemTipi"];
$TutarKodu    = $_POST["TutarKodu"];
$ClientIp     = "212.2.199.55";

$PosXML = 'prmstr=<VposRequest><MerchantId>'.$IsyeriNo.'</MerchantId><Password>'.$IsyeriSifre.'</Password><TerminalNo>'.$TerminalNo.'</TerminalNo><TransactionType>'.$IslemTipi.'</TransactionType><TransactionId>'.$SiparID.'</TransactionId>';
$PosXML .= '<CurrencyAmount>'.$Tutar.'</CurrencyAmount><CurrencyCode>'.$TutarKodu.'</CurrencyCode><Pan>'.$KartNo.'</Pan><Expiry>'.$KartYil.$KartAy.'</Expiry>';
$PosXML .= '<Cvv>'.$KartCvv.'</Cvv><TransactionDeviceSource>0</TransactionDeviceSource><ClientIp>'.$ClientIp.'</ClientIp></VposRequest>';

echo '<h1>Vpos Request</h1>';
echo $PostUrl."<br>";
echo '<textarea rows="15" cols="60">'.$PosXML.'</textarea>';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $PostUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $PosXML);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 59);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

// cURL isteğini çalıştır ve sonucu al
$result = curl_exec($ch);

// Hata kontrolü
if ($result === false) {
    echo "cURL Hatası: " . curl_error($ch);
} else {
    echo '<h1>Vpos Response</h1>';
    echo '<textarea rows="15" cols="60">'.$result.'</textarea>';
}

curl_close($ch);
?>