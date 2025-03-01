<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Sonucu</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
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
        }
        .success-header {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .error-header {
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
<?php
error_reporting(E_ALL ^ E_NOTICE);

// Ödeme durumunu kontrol et
$isSuccess = ($_POST["Status"] == "Y" || $_POST["Status"] == "Success");
?>

<div class="container">
    <div class="card result-card">
        <!-- Başlık bölümü - başarılı/başarısız duruma göre değişir -->
        <div class="result-header <?php echo $isSuccess ? 'success-header' : 'error-header'; ?>">
            <div class="result-icon">
                <?php if($isSuccess): ?>
                    <i class="bi bi-check-circle-fill"></i>
                <?php else: ?>
                    <i class="bi bi-x-circle-fill"></i>
                <?php endif; ?>
            </div>
            <h3>
                <?php echo $isSuccess ? 'Ödeme İşlemi Başarılı' : 'Ödeme İşlemi Başarısız'; ?>
            </h3>
            <p>
                <?php echo $isSuccess ?
                    'İşleminiz başarıyla tamamlanmıştır.' :
                    'İşleminiz sırasında bir hata oluştu.'; ?>
            </p>
        </div>

        <!-- İşlem detayları -->
        <div class="result-body">
            <div class="detail-row">
                <div class="row">
                    <div class="col-5 detail-label">İşlem Durumu:</div>
                    <div class="col-7"><?php echo $isSuccess ? 'Başarılı' : 'Başarısız'; ?></div>
                </div>
            </div>

            <div class="detail-row">
                <div class="row">
                    <div class="col-5 detail-label">İşlem Tutarı:</div>
                    <div class="col-7"><?php echo $_POST["PurchAmount"] ? $_POST["PurchAmount"] : '-'; ?> TL</div>
                </div>
            </div>

            <?php if ($_POST["VerifyEnrollmentRequestId"]): ?>
                <div class="detail-row">
                    <div class="row">
                        <div class="col-5 detail-label">İşlem Numarası:</div>
                        <div class="col-7"><?php echo $_POST["VerifyEnrollmentRequestId"]; ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($_POST["Xid"]): ?>
                <div class="detail-row">
                    <div class="row">
                        <div class="col-5 detail-label">Referans No:</div>
                        <div class="col-7"><?php echo $_POST["Xid"]; ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Butonlar -->
            <div class="mt-4 d-flex justify-content-center">
                <?php if($isSuccess): ?>

                <?php else: ?>
                    <a href="javascript:void(0)" onclick="window.location.reload(true)" class="btn btn-danger me-2">
                        <i class="bi bi-arrow-repeat me-1"></i> Tekrar Dene
                    </a>
                    <a href="/" class="btn btn-secondary">
                        <i class="bi bi-house-fill me-1"></i> Ana Sayfaya Dön
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Teşekkür mesajı - sadece başarılı işlemlerde -->
    <?php if($isSuccess): ?>
        <div class="text-center mt-4">
            <h4>Bağışınız için teşekkür ederiz!</h4>
            <p class="text-muted">İyiliğiniz yerine ulaştırılacaktır.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<?php if($isSuccess): ?>
    <script>

        window.parent.postMessage({
            status: 'success',
            transaction_id: '<?php echo $_POST["VerifyEnrollmentRequestId"] ?? ""; ?>',
            message: 'Ödeme işlemi başarıyla tamamlandı'
        }, '*');
    </script>
<?php endif; ?>


<?php if($isSuccess): ?>
    <script>

        document.addEventListener('DOMContentLoaded', function() {

            localStorage.removeItem('shopping_cart');
            console.log('Sepet başarıyla temizlendi!');

        });
    </script>
<?php endif; ?>
</body>
</html>
<?php


if($isSuccess) {
    try {
        // Kaydedilecek verileri hazırla
        $paymentData = [
            'transaction_id' => $_POST["VerifyEnrollmentRequestId"] ?? "",
            'amount' => $_POST["PurchAmount"] ?? "",
            'status' => $isSuccess ? 'success' : 'failed',
            'reference_no' => $_POST["Xid"] ?? "",
            'payment_date' => date('Y-m-d H:i:s')
        ];

        // JSON dosyasının yolunu belirle
        $jsonFilePath = __DIR__ . '/storage/payments.json';

        // Varolan verileri oku
        $existingData = [];
        if (file_exists($jsonFilePath)) {
            $jsonContent = file_get_contents($jsonFilePath);
            $existingData = json_decode($jsonContent, true) ?: [];
        }

        // Yeni veriyi ekle
        $existingData[] = $paymentData;

        // Dosyaya yaz
        file_put_contents($jsonFilePath, json_encode($existingData, JSON_PRETTY_PRINT));

        error_log("Ödeme kaydı başarıyla JSON dosyasına eklendi.");
    } catch(Exception $e) {
        error_log("Dosya işlemi hatası: " . $e->getMessage());
    }
}
