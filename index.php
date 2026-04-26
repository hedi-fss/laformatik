<?php
/**
 * LAFORMATIK — Main Dashboard & Layout
 * Dynamic sidebar with emoji icons from DB, category edit/delete, dashboard analytics.
 */
require_once __DIR__ . '/db_connect.php';

// ─── Fetch categories with product counts + emoji ───
$catStmt = $pdo->query("
    SELECT c.id_cat, c.nom_cat, c.emoji, COUNT(p.ref_prod) AS nb_products
    FROM categories c
    LEFT JOIN produits p ON c.id_cat = p.id_cat
    GROUP BY c.id_cat
    ORDER BY c.nom_cat ASC
");
$categories = $catStmt->fetchAll();
$totalProducts = array_sum(array_column($categories, 'nb_products'));

// ─── Dashboard analytics ───
$dashValue  = $pdo->query("SELECT COALESCE(SUM(prix * quantite), 0) AS val FROM produits")->fetch();
$dashTotal  = $pdo->query("SELECT COUNT(*) AS total FROM produits")->fetch();
$dashCats   = $pdo->query("SELECT COUNT(*) AS total FROM categories")->fetch();
$dashLow    = $pdo->query("SELECT COUNT(*) AS total FROM produits WHERE quantite > 0 AND quantite < 5")->fetch();
$dashOut    = $pdo->query("SELECT COUNT(*) AS total FROM produits WHERE quantite = 0")->fetch();
$dashTopCat = $pdo->query("
    SELECT c.nom_cat, SUM(p.quantite) AS total_qty
    FROM categories c JOIN produits p ON c.id_cat = p.id_cat
    GROUP BY c.id_cat ORDER BY total_qty DESC LIMIT 1
")->fetch();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="LAFORMATIK — IT Hardware Store Stock Management System">
    <title>LAFORMATIK | IT Stock Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <!-- ══════════ SIDEBAR ══════════ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="logo-link">
                <img src="Images/logo.png" alt="LAFORMATIK" class="logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="logo-fallback" style="display:none;">
                    <span class="logo-text">LAFORMATIK</span>
                </div>
            </a>
            <p class="logo-sub">IT Stock Management</p>
        </div>

        <!-- Theme Toggle -->
        <div class="theme-toggle-wrap">
            <button class="theme-toggle" id="themeToggle" title="Toggle Dark/Light Mode">
                <span class="tgl-icon" id="themeIcon">🌙</span>
                <span class="tgl-label" id="themeLabel">Dark Mode</span>
                <span class="tgl-track"><span class="tgl-thumb"></span></span>
            </button>
        </div>

        <!-- Category Navigation -->
        <nav class="sidebar-nav">
            <h3 class="nav-heading">Categories</h3>
            <ul class="cat-list" id="catList">
                <li>
                    <a href="products.php" class="cat-item active-cat">
                        <span class="cat-ico">📦</span>
                        <span class="cat-name">All Products</span>
                        <span class="cat-count"><?= $totalProducts ?></span>
                    </a>
                </li>
                <?php foreach ($categories as $cat): ?>
                <li class="cat-li">
                    <a href="products.php?cat=<?= $cat['id_cat'] ?>" class="cat-item">
                        <span class="cat-ico"><?= htmlspecialchars($cat['emoji']) ?></span>
                        <span class="cat-name"><?= htmlspecialchars($cat['nom_cat']) ?></span>
                        <span class="cat-count"><?= $cat['nb_products'] ?></span>
                    </a>
                    <div class="cat-actions">
                        <button class="cat-act-btn" title="Edit" onclick="openEditCatModal(<?= $cat['id_cat'] ?>, '<?= htmlspecialchars(addslashes($cat['nom_cat']), ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['emoji']) ?>')">✏️</button>
                        <?php if ($cat['nb_products'] == 0): ?>
                        <a href="actions/category_actions.php?delete_cat=<?= $cat['id_cat'] ?>&redirect_to=../index.php" class="cat-act-btn cat-act-del" title="Delete" onclick="return confirm('Are you sure?')">🗑️</a>
                        <?php else: ?>
                        <button class="cat-act-btn cat-act-disabled" title="Has <?= $cat['nb_products'] ?> product(s) — cannot delete" disabled>🗑️</button>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- Add Category -->
            <form action="actions/category_actions.php" method="POST" class="add-cat-form">
                <input type="hidden" name="action" value="add_category">
                <input type="hidden" name="redirect_to" value="../index.php">
                <input type="hidden" name="emoji" value="📁" id="newCatEmoji">
                <div class="add-cat-row">
                    <button type="button" class="emoji-pick-btn" id="newCatEmojiBtn" onclick="openEmojiPicker('newCatEmoji', 'newCatEmojiBtn')" title="Pick emoji">📁</button>
                    <input type="text" name="nom_cat" placeholder="New category..." maxlength="50" required class="add-cat-input">
                    <button type="submit" class="btn-add-cat" title="Add Category">+</button>
                </div>
            </form>
        </nav>

        <div class="sidebar-footer">
            <p>&copy; <?= date('Y') ?> LAFORMATIK</p>
            <p>Developped by Adam Abida and Hedi Moalla</p>

        </div>
    </aside>

    <!-- ══════════ MAIN CONTENT ══════════ -->
    <main class="main-content" id="mainContent">
        <button class="sidebar-toggle" id="sidebarToggle">☰</button>

        <!-- Flash Messages -->
        <?php if (!empty($_GET['success'])): ?>
            <div class="flash flash-success"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'success'): ?>
            <div class="flash flash-success">Action completed successfully.</div>
        <?php elseif (!empty($_GET['msg'])): ?>
            <div class="flash flash-success"><?= htmlspecialchars($_GET['msg']) ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="flash flash-error"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <!-- Page Header -->
        <header class="page-header">
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Overview of your IT inventory at a glance.</p>
        </header>

        <!-- Dashboard Cards -->
        <section class="dashboard" id="dashboard">
            <div class="dash-card card-value">
                <div class="dash-icon">💰</div>
                <div class="dash-body">
                    <span class="dash-label">Total Inventory Value</span>
                    <span class="dash-val"><?= number_format($dashValue['val'], 2) ?> TND</span>
                </div>
            </div>
            <div class="dash-card card-products">
                <div class="dash-icon">📋</div>
                <div class="dash-body">
                    <span class="dash-label">Total Products</span>
                    <span class="dash-val"><?= $dashTotal['total'] ?></span>
                </div>
            </div>
            <div class="dash-card card-cats">
                <div class="dash-icon">🏷️</div>
                <div class="dash-body">
                    <span class="dash-label">Categories</span>
                    <span class="dash-val"><?= $dashCats['total'] ?></span>
                </div>
            </div>
            <div class="dash-card card-lowstock">
                <div class="dash-icon">⚠️</div>
                <div class="dash-body">
                    <span class="dash-label">Low Stock Items</span>
                    <span class="dash-val"><?= $dashLow['total'] ?><?php if ($dashOut['total'] > 0): ?> <small class="out-label">(<?= $dashOut['total'] ?> out)</small><?php endif; ?></span>
                </div>
            </div>
            <div class="dash-card card-topcat">
                <div class="dash-icon">🏆</div>
                <div class="dash-body">
                    <span class="dash-label">Top Category</span>
                    <span class="dash-val"><?= $dashTopCat ? htmlspecialchars($dashTopCat['nom_cat']) . ' (' . $dashTopCat['total_qty'] . ')' : 'N/A' ?></span>
                </div>
            </div>
        </section>

        <!-- Quick Links -->
        <section class="quick-links">
            <a href="products.php" class="btn btn-primary">📋 View All Products</a>
            <a href="products.php?form=add" class="btn btn-accent">＋ Add New Product</a>
        </section>
    </main>

    <!-- ══════════ EDIT CATEGORY MODAL ══════════ -->
    <div class="modal-overlay" id="editCatModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">Edit Category</h3>
                <button class="modal-close" onclick="closeEditCatModal()">&times;</button>
            </div>
            <form action="actions/category_actions.php" method="POST" class="modal-form">
                <input type="hidden" name="action" value="edit_category">
                <input type="hidden" name="id_cat" id="editCatId">
                <input type="hidden" name="redirect_to" value="../index.php">
                <input type="hidden" name="emoji" id="editCatEmojiVal">

                <div class="form-group">
                    <label>Category Emoji</label>
                    <div class="emoji-selector">
                        <button type="button" class="emoji-preview-btn" id="editCatEmojiBtn" onclick="openEmojiPicker('editCatEmojiVal', 'editCatEmojiBtn')">📁</button>
                        <span class="emoji-hint">Click to change icon</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="editCatName">Category Name *</label>
                    <input type="text" id="editCatName" name="nom_cat" maxlength="50" required placeholder="Category name">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditCatModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════ EMOJI PICKER MODAL ══════════ -->
    <div class="modal-overlay" id="emojiPickerModal">
        <div class="modal-box emoji-picker-box">
            <div class="modal-header">
                <h3 class="modal-title">Pick an Emoji</h3>
                <button class="modal-close" onclick="closeEmojiPicker()">&times;</button>
            </div>
            <div class="emoji-grid" id="emojiGrid">
                <!-- Populated by JS -->
            </div>
        </div>
    </div>

    <script src="assets/script.js"></script>
</body>
</html>
