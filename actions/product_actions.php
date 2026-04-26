<?php
/**
 * LAFORMATIK — Product Actions
 * Handles: add, edit, delete, sell (stock update) via POST.
 * Redirects back to products.php with status messages.
 */
require_once __DIR__ . '/../db_connect.php';

$action = $_POST['action'] ?? '';

// Helper: redirect back with message
function redirectBack(string $msgText, string $type = 'success', string $cat = '') {
    // Determine parameter: user asked to use ?msg=success for UI feedback, but we can also pass the actual text.
    // Let's pass ?msg=TEXT if success, or ?error=TEXT if error to keep compatibility.
    // If user prefers exactly ?msg=success, we can do ?msg=success&info=... but let's just use ?msg=URLENCODED_MESSAGE
    $param = ($type === 'error') ? 'error' : 'msg';
    $url   = '../products.php?' . $param . '=' . urlencode($msgText);
    if ($cat) $url .= '&cat=' . urlencode($cat);
    header("Location: $url");
    exit;
}

// ─── GET: DELETE PRODUCT ───────────────────────────
if (isset($_GET['delete_ref'])) {
    $ref = trim($_GET['delete_ref']);
    $redirectCat = $_GET['cat'] ?? '';

    if ($ref === '') {
        redirectBack('No product reference provided.', 'error', $redirectCat);
    }

    try {
        $check = $pdo->prepare("SELECT quantite, photo FROM produits WHERE ref_prod = ?");
        $check->execute([$ref]);
        $prod = $check->fetch();

        if (!$prod) {
            redirectBack('Product not found.', 'error', $redirectCat);
        }
        if (intval($prod['quantite']) > 0) {
            redirectBack('Cannot delete product with remaining stock.', 'error', $redirectCat);
        }

        // Remove photo file
        if ($prod['photo'] && file_exists(__DIR__ . '/../uploads/' . $prod['photo'])) {
            unlink(__DIR__ . '/../uploads/' . $prod['photo']);
        }

        $stmt = $pdo->prepare("DELETE FROM produits WHERE ref_prod = ?");
        $stmt->execute([$ref]);
        // requested exactly ?msg=success for completed action
        $url = '../products.php?msg=success';
        if ($redirectCat) $url .= '&cat=' . urlencode($redirectCat);
        header("Location: $url");
        exit;

    } catch (PDOException $e) {
        redirectBack('Database error: ' . $e->getMessage(), 'error', $redirectCat);
    }
}

// Determine the current category filter for redirect
$redirectCat = $_POST['current_cat'] ?? '';

switch ($action) {

    // ─── ADD PRODUCT ───────────────────────────────────
    case 'add':
        $ref   = trim($_POST['ref_prod'] ?? '');
        $desig = trim($_POST['designation'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $brand = trim($_POST['marque'] ?? '');
        $price = floatval($_POST['prix'] ?? 0);
        $qty   = intval($_POST['quantite'] ?? 0);
        $idCat = intval($_POST['id_cat'] ?? 0);

        // Validation
        $errors = [];
        if ($ref === '')   $errors[] = 'Reference is required.';
        if ($desig === '') $errors[] = 'Designation is required.';
        if ($price <= 0)   $errors[] = 'Price must be greater than 0.';
        if ($qty < 0)      $errors[] = 'Quantity cannot be negative.';
        if ($idCat <= 0)   $errors[] = 'Category is required.';

        if ($errors) {
            redirectBack(implode(' ', $errors), 'error', $redirectCat);
        }

        // Check duplicate reference
        $check = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE ref_prod = ?");
        $check->execute([$ref]);
        if ($check->fetchColumn() > 0) {
            redirectBack('This reference already exists.', 'error', $redirectCat);
        }

        // Handle image upload
        $photoName = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed)) {
                $photoName = uniqid('prod_') . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoName);
            }
        }

        $stmt = $pdo->prepare("INSERT INTO produits (ref_prod, designation, description, marque, prix, quantite, photo, id_cat) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$ref, $desig, $desc, $brand, $price, $qty, $photoName, $idCat]);
        redirectBack('Product added successfully.', 'success', $redirectCat);
        break;

    // ─── EDIT PRODUCT ──────────────────────────────────
    case 'edit':
        $ref   = trim($_POST['ref_prod'] ?? '');
        $desig = trim($_POST['designation'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $brand = trim($_POST['marque'] ?? '');
        $price = floatval($_POST['prix'] ?? 0);
        $qty   = intval($_POST['quantite'] ?? 0);
        $idCat = intval($_POST['id_cat'] ?? 0);

        $errors = [];
        if ($desig === '') $errors[] = 'Designation is required.';
        if ($price <= 0)   $errors[] = 'Price must be greater than 0.';
        if ($qty < 0)      $errors[] = 'Quantity cannot be negative.';
        if ($idCat <= 0)   $errors[] = 'Category is required.';

        if ($errors) {
            redirectBack(implode(' ', $errors), 'error', $redirectCat);
        }

        // Handle optional image upload
        $photoSql   = '';
        $photoParam = [];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed)) {
                $photoName = uniqid('prod_') . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoName);
                $photoSql   = ", photo = ?";
                $photoParam = [$photoName];
            }
        }

        $sql    = "UPDATE produits SET designation=?, description=?, marque=?, prix=?, quantite=?, id_cat=? {$photoSql} WHERE ref_prod=?";
        $params = array_merge([$desig, $desc, $brand, $price, $qty, $idCat], $photoParam, [$ref]);
        $stmt   = $pdo->prepare($sql);
        $stmt->execute($params);
        redirectBack('Product updated successfully.', 'success', $redirectCat);
        break;



    // ─── SELL (STOCK UPDATE) ───────────────────────────
    case 'sell':
        $ref = trim($_POST['ref_prod'] ?? '');
        $qty = intval($_POST['qty_sold'] ?? 0);

        if ($qty <= 0) {
            redirectBack('Sold quantity must be positive.', 'error', $redirectCat);
        }

        $check = $pdo->prepare("SELECT quantite FROM produits WHERE ref_prod = ?");
        $check->execute([$ref]);
        $prod = $check->fetch();

        if (!$prod) {
            redirectBack('Product not found.', 'error', $redirectCat);
        }
        if ($prod['quantite'] < $qty) {
            redirectBack('Insufficient stock. Available: ' . $prod['quantite'], 'error', $redirectCat);
        }

        $stmt = $pdo->prepare("UPDATE produits SET quantite = quantite - ? WHERE ref_prod = ?");
        $stmt->execute([$qty, $ref]);
        $newQty = $prod['quantite'] - $qty;
        redirectBack("Sale recorded. New stock: {$newQty}", 'success', $redirectCat);
        break;

    default:
        header('Location: ../products.php');
        exit;
}
