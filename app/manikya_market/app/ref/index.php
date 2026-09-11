<?php
require_once '../../config/config.php';
requireAdmin();

$pageTitle = 'Company Profile';

$error = '';

$companyName = getSettingValue('company_name', (defined('SITE_NAME') ? (string)SITE_NAME : ''));
$companyAddress = getSettingValue('company_address', '');
$companyGstin = getSettingValue('company_gstin', (defined('INVOICE_COMPANY_GSTIN') ? (string)INVOICE_COMPANY_GSTIN : ''));
$companyPan = getSettingValue('company_pan', '');
$companyLogoPath = getSettingValue('company_logo_path', '');
$companyBankName = getSettingValue('company_bank_name', '');
$companyBankAccount = getSettingValue('company_bank_account', '');
$companyBankIfsc = getSettingValue('company_bank_ifsc', '');
$companyState = getSettingValue('company_state', '');
$companyPincode = getSettingValue('company_pincode', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyNameIn = trim((string)($_POST['company_name'] ?? ''));
    $companyAddressIn = trim((string)($_POST['company_address'] ?? ''));
    $companyGstinIn = strtoupper(trim((string)($_POST['company_gstin'] ?? '')));
    $companyPanIn = strtoupper(trim((string)($_POST['company_pan'] ?? '')));
    $companyBankNameIn = trim((string)($_POST['company_bank_name'] ?? ''));
    $companyBankAccountIn = trim((string)($_POST['company_bank_account'] ?? ''));
    $companyBankIfscIn = strtoupper(trim((string)($_POST['company_bank_ifsc'] ?? '')));
    $companyStateIn = trim((string)($_POST['company_state'] ?? ''));
    $companyPincodeIn = trim((string)($_POST['company_pincode'] ?? ''));

    if ($companyNameIn === '') {
        $error = 'Company Name is required.';
    } else {
        try {
            setSettingValue('company_name', $companyNameIn, 'company', 1);
            setSettingValue('company_address', $companyAddressIn, 'company', 1);
            setSettingValue('company_gstin', $companyGstinIn, 'company', 1);
            setSettingValue('company_pan', $companyPanIn, 'company', 1);
            setSettingValue('company_bank_name', $companyBankNameIn, 'company', 1);
            setSettingValue('company_bank_account', $companyBankAccountIn, 'company', 1);
            setSettingValue('company_bank_ifsc', $companyBankIfscIn, 'company', 1);
            setSettingValue('company_state', $companyStateIn, 'company', 1);
            setSettingValue('company_pincode', $companyPincodeIn, 'company', 1);

            if (isset($_FILES['company_logo']) && is_array($_FILES['company_logo']) && ($_FILES['company_logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if (($_FILES['company_logo']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Logo upload failed.');
                }

                $tmp = (string)($_FILES['company_logo']['tmp_name'] ?? '');
                $size = (int)($_FILES['company_logo']['size'] ?? 0);
                if ($tmp === '' || $size <= 0) {
                    throw new RuntimeException('Invalid logo upload.');
                }
                if ($size > 2 * 1024 * 1024) {
                    throw new RuntimeException('Logo must be <= 2MB.');
                }

                $imgInfo = @getimagesize($tmp);
                if ($imgInfo === false || empty($imgInfo['mime'])) {
                    throw new RuntimeException('Logo must be a valid image.');
                }

                $mime = (string)$imgInfo['mime'];
                $ext = '';
                if ($mime === 'image/png') $ext = 'png';
                elseif ($mime === 'image/jpeg') $ext = 'jpg';
                elseif ($mime === 'image/webp') $ext = 'webp';

                if ($ext === '') {
                    throw new RuntimeException('Logo must be PNG, JPG, or WEBP.');
                }

                $uploadDir = realpath(__DIR__ . '/../../assets');
                if ($uploadDir === false) {
                    throw new RuntimeException('Assets folder not found.');
                }
                $uploadDir = $uploadDir . DIRECTORY_SEPARATOR . 'uploads';
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                        throw new RuntimeException('Failed to create uploads folder.');
                    }
                }

                $filename = 'company_logo_' . date('Ymd_His') . '.' . $ext;
                $destFs = $uploadDir . DIRECTORY_SEPARATOR . $filename;

                if (!move_uploaded_file($tmp, $destFs)) {
                    throw new RuntimeException('Failed to save uploaded logo.');
                }

                $webPath = '/assets/uploads/' . $filename;
                setSettingValue('company_logo_path', $webPath, 'company', 1);
            }

            $_SESSION['success'] = 'Company profile updated.';
            header('Location: ' . BASE_URL . '/admin/settings/');
            exit();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }

    $companyName = $companyNameIn;
    $companyAddress = $companyAddressIn;
    $companyGstin = $companyGstinIn;
    $companyPan = $companyPanIn;
    $companyBankName = $companyBankNameIn;
    $companyBankAccount = $companyBankAccountIn;
    $companyBankIfsc = $companyBankIfscIn;
    $companyState = $companyStateIn;
    $companyPincode = $companyPincodeIn;
    $companyLogoPath = getSettingValue('company_logo_path', $companyLogoPath);
}

$logoUrl = '';
if ($companyLogoPath !== '') {
    $p = $companyLogoPath[0] === '/' ? $companyLogoPath : '/' . $companyLogoPath;
    $logoUrl = appUrl(BASE_URL . $p);
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="container-fluid pt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Company Profile</h1>
            <a class="btn btn-secondary" href="<?php echo BASE_URL; ?>/">Back</a>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars((string)$_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company Name</label>
                            <input class="form-control" name="company_name" value="<?php echo htmlspecialchars($companyName); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Logo</label>
                            <input class="form-control" type="file" name="company_logo" accept="image/png,image/jpeg,image/webp">
                            <?php if ($logoUrl !== ''): ?>
                                <div class="mt-2">
                                    <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo" style="max-height: 70px; max-width: 240px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">GSTIN</label>
                            <input class="form-control" name="company_gstin" value="<?php echo htmlspecialchars($companyGstin); ?>" placeholder="e.g. 29ABCDE1234F1Z5">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">PAN</label>
                            <input class="form-control" name="company_pan" value="<?php echo htmlspecialchars($companyPan); ?>" placeholder="e.g. ABCDE1234F">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Bank Name</label>
                            <input class="form-control" name="company_bank_name" value="<?php echo htmlspecialchars($companyBankName); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Current Account Number</label>
                            <input class="form-control" name="company_bank_account" value="<?php echo htmlspecialchars($companyBankAccount); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">IFSC Code</label>
                            <input class="form-control" name="company_bank_ifsc" value="<?php echo htmlspecialchars($companyBankIfsc); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Company Address</label>
                        <textarea class="form-control" name="company_address" rows="4" placeholder="Address line 1&#10;Address line 2"><?php echo htmlspecialchars($companyAddress); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">State</label>
                            <input class="form-control" name="company_state" value="<?php echo htmlspecialchars($companyState); ?>" placeholder="e.g. Karnataka">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pincode</label>
                            <input class="form-control" name="company_pincode" value="<?php echo htmlspecialchars($companyPincode); ?>" placeholder="e.g. 560001">
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit">Save</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>
