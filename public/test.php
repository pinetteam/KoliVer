<?php
error_reporting(E_ALL ^ E_NOTICE);

// Kart tipini belirleyen fonksiyon
function kartTipiBelirle($kartNumarasi) {
    // Visa kontrolleri
    if (preg_match('/^4/', $kartNumarasi)) {
        return 100; // Visa
    }

    // Mastercard kontrolleri
    if (preg_match('/^5[1-5]/', $kartNumarasi)) {
        return 200; // Mastercard
    }

    // Troy kartları
    if (preg_match('/^9/', $kartNumarasi)) {
        return 300; // Troy
    }

    // Bilinmeyen kart tipi
    return 0;
}

// MPI'dan dönen XML cevabını yorumlayıp gerekli alanları bir dizi içerisinde döndürür
function SonucuOku($result)
{
    $resultDocument = new DOMDocument();
    $resultDocument->loadXML($result);

    //Status Bilgisi okunuyor
    $statusNode = $resultDocument->getElementsByTagName("Status")->item(0);
    $status = "";
    if( $statusNode != null )
        $status = $statusNode->nodeValue;

    //PAReq Bilgisi okunuyor
    $PAReqNode = $resultDocument->getElementsByTagName("PaReq")->item(0);
    $PaReq = "";
    if( $PAReqNode != null )
        $PaReq = $PAReqNode->nodeValue;

    //ACSUrl Bilgisi okunuyor
    $ACSUrlNode = $resultDocument->getElementsByTagName("ACSUrl")->item(0);
    $ACSUrl = "";
    if( $ACSUrlNode != null )
        $ACSUrl = $ACSUrlNode->nodeValue;

    //Term Url Bilgisi okunuyor
    $TermUrlNode = $resultDocument->getElementsByTagName("TermUrl")->item(0);
    $TermUrl = "";
    if( $TermUrlNode != null )
        $TermUrl = $TermUrlNode->nodeValue;

    //MD Bilgisi okunuyor
    $MDNode = $resultDocument->getElementsByTagName("MD")->item(0);
    $MD = "";
    if( $MDNode != null )
        $MD = $MDNode->nodeValue;

    //MessageErrorCode Bilgisi okunuyor
    $messageErrorCodeNode = $resultDocument->getElementsByTagName("MessageErrorCode")->item(0);
    $messageErrorCode = "";
    if( $messageErrorCodeNode != null )
        $messageErrorCode = $messageErrorCodeNode->nodeValue;

    // Sonuç dizisi oluşturuluyor
    $result = array
    (
        "Status"=>$status,
        "PaReq"=>$PaReq,
        "ACSUrl"=>$ACSUrl,
        "TermUrl"=>$TermUrl,
        "MerchantData"=>$MD	,
        "MessageErrorCode"=>$messageErrorCode
    );
    return $result;
}

// GET parametrelerinden değerleri al
$kartNumarasi = $_GET['kart_no'] ?? '';
$tutar = $_GET['tutar'] ?? 1.00;
$sonKullanmaTarihi = $_GET['son_kullanma'] ?? '';
$cvv = $_GET['cvv'] ?? '';

// Kart tipini otomatik belirle
$kartTipi = kartTipiBelirle($kartNumarasi);

// MPI Servis URL'si
$mpiServiceUrl = "https://3dsecure.vakifbank.com.tr:4443/MPIAPI/MPI_Enrollment.aspx";

if($_POST["form"]=="send"){
    $krediKartiNumarasi = $_POST["pan"];
    $sonKullanmaTarihi = $_POST["ExpiryDate"];
    $kartTipi = $_POST["BrandName"];
    $tutar = $_POST["PurchaseAmount"];
    $paraKodu = $_POST["Currency"];
    $taksitSayisi = $_POST["InstallmentCount"];
    $islemNumarasi = $_POST["VerifyEnrollmentRequestId"];
    $uyeIsyeriNumarasi = $_POST["MerchantId"];
    $uyeIsYeriSifresi = $_POST["MerchantPassword"];
    $terminalNo = $_POST["TerminalNo"];

    $SuccessURL = $_POST["SuccessURL"];
    $FailureURL = $_POST["FailureURL"];
    $ekVeri = $_POST["SessionInfo"]; // Optional

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$mpiServiceUrl);
    curl_setopt($ch,CURLOPT_POST,TRUE);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch,CURLOPT_HTTPHEADER,array("Content-Type"=>"application/x-www-form-urlencoded"));
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "Pan=$krediKartiNumarasi&ExpiryDate=$sonKullanmaTarihi&PurchaseAmount=$tutar&Currency=$paraKodu&BrandName=$kartTipi&VerifyEnrollmentRequestId=$islemNumarasi&SessionInfo=$ekVeri&MerchantId=$uyeIsyeriNumarasi&MerchantPassword=$uyeIsYeriSifresi&TerminalNo=$terminalNo&SuccessUrl=$SuccessURL&FailureUrl=$FailureURL&InstallmentCount=$taksitSayisi");

    // İşlem isteği MPI'a gönderiliyor
    $resultXml = curl_exec($ch);
    curl_close($ch);

    // Sonuç XML'i yorumlanıp döndürülüyor
    $result = SonucuOku($resultXml);

    if($result["Status"]=="Y")
    {
        // Kart 3D-Secure Programına Dahil
       // echo $result["Status"];
        ?>
        <html>
        <head>
            <title>Get724 Mpi 3D-Secure İşlem Sayfası</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    text-align: center;
                    background-color: #f4f4f4;
                }
                .container {
                    max-width: 500px;
                    margin: 50px auto;
                    padding: 20px;
                    background-color: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .submit-btn {
                    background-color: #4CAF50;
                    color: white;
                    padding: 12px 20px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                }
            </style>
        </head>
        <body>
        <div class="container">
            <form name="downloadForm" action="<?php echo $result['ACSUrl']?>" method="POST">
                <h1>3-D Secure İşleminiz yapılıyor</h1>
                <h3>3D-Secure işleminizin doğrulama aşamasına geçebilmek için Devam butonuna basmanız gerekmektedir</h3>

                <input type="submit" class="submit-btn" value="Gönder">

                <input type="hidden" name="PaReq" value="<?php echo $result['PaReq']?>">
                <input type="hidden" name="TermUrl" value="<?php echo $result['TermUrl']?>">
                <input type="hidden" name="MD" value="<?php echo $result['MerchantData']?>">
            </form>
        </div>
        </body>
        </html>
        <?php
    } else {
        // 3D-Secure başarısız olduğunda gösterilecek hata sayfası
        ?>
        <!DOCTYPE html>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <title>Ödeme Başarısız</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
            <style>
                body {
                    background-color: #f8f9fa;
                    padding-top: 40px;
                }
                .result-card {
                    max-width: 500px;
                    margin: 0 auto;
                    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                    border-radius: 1rem;
                    overflow: hidden;
                }
                .result-header {
                    padding: 1.5rem;
                    text-align: center;
                    background-color: #f8d7da;
                    color: #842029;
                }
                .result-icon {
                    font-size: 3rem;
                    margin-bottom: 0.5rem;
                }
                .result-body {
                    padding: 1.5rem;
                }
                .detail-row {
                    padding: 0.5rem 0;
                    border-bottom: 1px solid #dee2e6;
                }
                .detail-row:last-child {
                    border-bottom: none;
                }
                .detail-label {
                    font-weight: 600;
                    color: #6c757d;
                }
            </style>
        </head>
        <body>
        <div class="container">
            <div class="card result-card">
                <!-- Başlık bölümü -->
                <div class="result-header">
                    <div class="result-icon">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <h3>Ödeme İşlemi Başarısız</h3>
                    <p>3D Secure doğrulama işlemi sırasında bir hata oluştu.</p>
                </div>

                <!-- İşlem detayları -->
                <div class="result-body">
                    <div class="detail-row">
                        <div class="row">
                            <div class="col-5 detail-label">İşlem Durumu:</div>
                            <div class="col-7">Başarısız</div>
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="row">
                            <div class="col-5 detail-label">Hata Kodu:</div>
                            <div class="col-7"><?php echo $result["MessageErrorCode"]; ?></div>
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="row">
                            <div class="col-5 detail-label">Durum:</div>
                            <div class="col-7"><?php echo $result["Status"]; ?></div>
                        </div>
                    </div>

                    <!-- Butonlar -->
                    <div class="mt-4 d-flex justify-content-center">
                        <a href="javascript:void(0)" onclick="window.history.back()" class="btn btn-danger me-2">
                            <i class="bi bi-arrow-repeat me-1"></i> Tekrar Dene
                        </a>

                    </div>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
    }
} else {
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <title>3D Secure Ödeme Formu</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

        <style>

            .form-container {
                background-color: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                width: 100%;
                max-width: 500px;
            }
            .input_yeni {
                width: 100%;
                padding: 10px;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .submit-btn {
                background-color: #4CAF50;
                color: white;
                padding: 12px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                width: 100%;
            }
        </style>
    </head>
    <body>
    <div class="form-container">
        <form action="" method="post">
            <input type="hidden" name="form" value="send">

            <!-- Sabit Gizli Alanlar -->
            <input type="hidden" name="MerchantId" value="000000037135639">
            <input type="hidden" name="MerchantPassword" value="s5RKz9c8">
            <input type="hidden" name="TerminalNo" value="V1752187">
            <input type="hidden" name="SuccessURL" value="https://koliver.ankara.bel.tr/sonuc.php">
            <input type="hidden" name="FailureURL" value="https://koliver.ankara.bel.tr/sonuc.php">

            <div>

                <input type="hidden" name="PurchaseAmount" class="input_yeni" value="<?php echo number_format($tutar, 2); ?>" readonly>
            </div>

            <div>
                <label>Kart Numarası</label>
                <input class="input_yeni" type="text" name="pan" value="<?php echo $kartNumarasi; ?>">
            </div>

            <div>
                <label>Son Kullanma Tarihi</label>

                <input class="input_yeni" type="text" name="ExpiryDate"  value="<?php echo $sonKullanmaTarihi; ?>" placeholder="AA/YY" maxlength="5">

            </div>

            <div>
                <label>CVV</label>
                <input class="input_yeni" type="text" name="KartCvv" placeholder="CVV Giriniz" required value="<?php echo $cvv; ?>">
            </div>

            <input type="hidden" name="BrandName" value="<?php echo $kartTipi; ?>">
            <input type="hidden" name="Currency" value="949">

            <div >

                <input  type="hidden" class="input_yeni" type="text" name="VerifyEnrollmentRequestId" value="SIP_ID<?php echo rand(10000000, 99999999); ?>">
            </div>

            <input type="hidden" name="SessionInfo" value="1">
            <input type="hidden" name="InstallmentCount" value="">

            <button type="submit" class="submit-btn">Ödemeyi Tamamla</button>
        </form>
    </div>
    </body>
    </html>
<?php } ?>


<script>
    // Form gönderilmeden önce son kullanma tarihini dönüştürme
    document.querySelector('form').addEventListener('submit', function(event) {
        // Son kullanma tarihi input değerini al
        const expiryDateInput = document.querySelector('input[name="ExpiryDate"]');
        const expiryValue = expiryDateInput.value;

        // Eğer girilen format AA/YY şeklindeyse
        if (expiryValue.includes('/')) {
            event.preventDefault(); // Form gönderimini geçici olarak durdur

            // AA/YY formatını parçala
            const parts = expiryValue.split('/');
            if (parts.length === 2) {
                const month = parts[0].trim();
                let year = parts[1].trim();

                // 2 haneli yıl ise başına 20 ekle (20 -> 2020)
                if (year.length === 2) {
                    year = '20' + year;
                }

                // YYAA formatında birleştir
                const formattedDate = year.slice(-2) + month.padStart(2, '0');

                // Input değerini güncelle
                expiryDateInput.value = formattedDate;

                // Formu gönder
                this.submit();
            }
        }


        const submitButton = document.querySelector('.submit-btn');
        submitButton.disabled = true;
        submitButton.innerHTML = 'Lütfen bekleyiniz... <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        // Bir alan oluşturup form içeriğinin üzerine mesaj göster
        const loadingOverlay = document.createElement('div');
        loadingOverlay.style.position = 'absolute';
        loadingOverlay.style.top = '0';
        loadingOverlay.style.left = '0';
        loadingOverlay.style.width = '100%';
        loadingOverlay.style.height = '100%';
        loadingOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0.8)';
        loadingOverlay.style.display = 'flex';
        loadingOverlay.style.justifyContent = 'center';
        loadingOverlay.style.alignItems = 'center';
        loadingOverlay.style.zIndex = '1000';
        loadingOverlay.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
            <h4 class="mt-3">Lütfen bekleyiniz</h4>
            <p>İşleminiz gerçekleştiriliyor...</p>
        </div>
    `;

        document.querySelector('.form-container').style.position = 'relative';
        document.querySelector('.form-container').appendChild(loadingOverlay);

    });

    // Input maskesi ekle (isteğe bağlı, daha iyi kullanıcı deneyimi için)
    document.querySelector('input[name="ExpiryDate"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');

        // AA/YY formatına dönüştür
        if (value.length > 0) {
            // İlk 2 karakter ay
            if (value.length <= 2) {
                e.target.value = value;
            }
            // İlk 2 karakter ay, sonraki karakterler yıl
            else {
                const month = value.substring(0, 2);
                const year = value.substring(2, 4);
                e.target.value = `${month}/${year}`;
            }
        }

        // Ay değerini 01-12 arasında sınırla
        let month = parseInt(value.substring(0, 2));
        if (month > 12) {
            e.target.value = '12' + e.target.value.substring(2);
        }
        if (month < 1 && value.length >= 2) {
            e.target.value = '01' + e.target.value.substring(2);
        }
    });


    // iframe src'yi toplam tutar ile ayarla

</script>
