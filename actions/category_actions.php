<?php
/**
 * LAFORMATIK — Category Actions (Hardened)
 * Handles: add, edit, delete categories (POST).
 * All PDO operations wrapped in try-catch for robust error reporting.
 */
require_once __DIR__ . '/../db_connect.php';

$action  = $_POST['action'] ?? '';
$referer = $_POST['redirect_to'] ?? '../index.php';

// ─── GET: DELETE CATEGORY ───────────────────────────
if (isset($_GET['delete_cat'])) {
    $id = intval($_GET['delete_cat']);
    $referer = $_GET['redirect_to'] ?? '../index.php';

    if ($id <= 0) {
        header('Location: ' . $referer . '?error=' . urlencode('Invalid category.'));
        exit;
    }

    try {
        // Block if category has products
        $check = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE id_cat = ?");
        $check->execute([$id]);
        $count = $check->fetchColumn();

        if ($count > 0) {
            header('Location: ' . $referer . '?error=' . urlencode("Category is not empty!"));
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM categories WHERE id_cat = ?");
        $stmt->execute([$id]);

        header('Location: ' . $referer . '?msg=success');
        exit;

    } catch (PDOException $e) {
        $msg = 'Database error: ' . $e->getMessage();
        header('Location: ' . $referer . '?error=' . urlencode($msg));
        exit;
    }
}

switch ($action) {

    // ─── ADD CATEGORY ──────────────────────────────
    case 'add_category':
        $name  = trim($_POST['nom_cat'] ?? '');
        $emoji = trim($_POST['emoji'] ?? '📁');

        if ($name === '') {
            header('Location: ' . $referer . '?error=' . urlencode('Category name is required.'));
            exit;
        }

        try {
            // Check for duplicates (case-insensitive)
            $check = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE LOWER(nom_cat) = LOWER(?)");
            $check->execute([$name]);
            if ($check->fetchColumn() > 0) {
                header('Location: ' . $referer . '?error=' . urlencode('This category already exists.'));
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO categories (nom_cat, emoji) VALUES (?, ?)");
            $stmt->execute([$name, $emoji]);

            header('Location: ' . $referer . '?success=' . urlencode('Category "' . $name . '" added successfully.'));
            exit;

        } catch (PDOException $e) {
            $msg = 'Database error: ' . $e->getMessage();
            header('Location: ' . $referer . '?error=' . urlencode($msg));
            exit;
        }
        break;

    // ─── EDIT CATEGORY ─────────────────────────────
    case 'edit_category':
        $id    = intval($_POST['id_cat'] ?? 0);
        $name  = trim($_POST['nom_cat'] ?? '');
        $emoji = trim($_POST['emoji'] ?? '📁');

        if ($id <= 0 || $name === '') {
            header('Location: ' . $referer . '?error=' . urlencode('Invalid category data.'));
            exit;
        }

        try {
            // Duplicate check (excluding current)
            $check = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE LOWER(nom_cat) = LOWER(?) AND id_cat != ?");
            $check->execute([$name, $id]);
            if ($check->fetchColumn() > 0) {
                header('Location: ' . $referer . '?error=' . urlencode('Another category with this name already exists.'));
                exit;
            }

            $stmt = $pdo->prepare("UPDATE categories SET nom_cat = ?, emoji = ? WHERE id_cat = ?");
            $stmt->execute([$name, $emoji, $id]);

            header('Location: ' . $referer . '?success=' . urlencode('Category updated successfully.'));
            exit;

        } catch (PDOException $e) {
            $msg = 'Database error: ' . $e->getMessage();
            header('Location: ' . $referer . '?error=' . urlencode($msg));
            exit;
        }
        break;



    default:
        header('Location: ../index.php');
        exit;
}
