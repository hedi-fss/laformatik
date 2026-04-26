<?php
/**
 * LAFORMATIK — Products Page
 * Lists products with filter, search, sort. Includes Add/Edit/Sell forms.
 */
require_once __DIR__ . '/db_connect.php';

// ─── Fetch categories (for sidebar + dropdowns) ───
$catStmt = $pdo->query("
    SELECT c.id_cat, c.nom_cat, c.emoji, COUNT(p.ref_prod) AS nb_products
    FROM categories c LEFT JOIN produits p ON c.id_cat = p.id_cat
    GROUP BY c.id_cat ORDER BY c.nom_cat ASC
");
$categories = $catStmt->fetchAll();
$totalProducts = array_sum(array_column($categories, 'nb_products'));

// ─── Current filters ───
$currentCat = $_GET['cat'] ?? '';
$search     = trim($_GET['search'] ?? '');
$sortCol    = $_GET['sort'] ?? 'designation';
$sortDir    = (strtoupper($_GET['dir'] ?? 'ASC') === 'DESC') ? 'DESC' : 'ASC';
$formMode   = $_GET['form'] ?? '';
$editRef    = $_GET['ref'] ?? '';

$allowedSorts = ['designation','prix','marque','quantite','ref_prod'];
if (!in_array($sortCol, $allowedSorts)) $sortCol = 'designation';

// ─── Build product query ───
$where  = [];
$params = [];
if ($currentCat !== '') {
    $where[]  = "p.id_cat = ?";
    $params[] = intval($currentCat);
}
if ($search !== '') {
    $where[]  = "p.designation LIKE ?";
    $params[] = "%{$search}%";
}
$sql = "SELECT p.*, c.nom_cat FROM produits p JOIN categories c ON p.id_cat = c.id_cat";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY p.{$sortCol} {$sortDir}";
$prodStmt = $pdo->prepare($sql);
$prodStmt->execute($params);
$products = $prodStmt->fetchAll();

// ─── Category title ───
$catTitle = 'All Products';
if ($currentCat !== '') {
    foreach ($categories as $c) {
        if ($c['id_cat'] == $currentCat) { $catTitle = htmlspecialchars($c['nom_cat']); break; }
    }
}

// ─── Load edit/sell data ───
$editProduct = null;
if ($formMode === 'edit' && $editRef !== '') {
    $es = $pdo->prepare("SELECT * FROM produits WHERE ref_prod = ?");
    $es->execute([$editRef]);
    $editProduct = $es->fetch();
}
$sellProduct = null;
if ($formMode === 'sell' && $editRef !== '') {
    $ss = $pdo->prepare("SELECT ref_prod, designation, quantite FROM produits WHERE ref_prod = ?");
    $ss->execute([$editRef]);
    $sellProduct = $ss->fetch();
}

// Helper to keep filters in links
function filterQS($extra = []) {
    global $currentCat, $search, $sortCol, $sortDir;
    $p = array_merge(['cat' => $currentCat, 'search' => $search, 'sort' => $sortCol, 'dir' => $sortDir], $extra);
    return http_build_query(array_filter($p, fn($v) => $v !== ''));
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | LAFORMATIK</title>
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
                <img src="Images/logo.png" alt="LAFORMATIK" class="logo-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                <div class="logo-fallback" style="display:none;">
                    <span class="logo-text">LAFORMATIK</span>
                </div>
            </a>
            <p class="logo-sub">IT Stock Management</p>
        </div>
        <div class="theme-toggle-wrap">
            <button class="theme-toggle" id="themeToggle" title="Toggle Theme">
                <span class="tgl-icon" id="themeIcon">🌙</span>
                <span class="tgl-label" id="themeLabel">Dark Mode</span>
                <span class="tgl-track"><span class="tgl-thumb"></span></span>
            </button>
        </div>
        <nav class="sidebar-nav">
            <h3 class="nav-heading">Categories</h3>
            <ul class="cat-list">
                <li><a href="products.php" class="cat-item <?= $currentCat === '' ? 'active-cat' : '' ?>">
                    <span class="cat-ico">📦</span><span class="cat-name">All Products</span><span class="cat-count"><?= $totalProducts ?></span>
                </a></li>
                <?php foreach ($categories as $cat): ?>
                <li class="cat-li">
                    <a href="products.php?cat=<?= $cat['id_cat'] ?>" class="cat-item <?= ($currentCat == $cat['id_cat']) ? 'active-cat' : '' ?>">
                        <span class="cat-ico"><?= htmlspecialchars($cat['emoji']) ?></span>
                        <span class="cat-name"><?= htmlspecialchars($cat['nom_cat']) ?></span>
                        <span class="cat-count"><?= $cat['nb_products'] ?></span>
                    </a>
                    <div class="cat-actions">
                        <button class="cat-act-btn" title="Edit" onclick="openEditCatModal(<?= $cat['id_cat'] ?>, '<?= htmlspecialchars(addslashes($cat['nom_cat']), ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['emoji']) ?>')">✏️</button>
                        <?php if ($cat['nb_products'] == 0): ?>
                        <a href="actions/category_actions.php?delete_cat=<?= $cat['id_cat'] ?>&redirect_to=../products.php" class="cat-act-btn cat-act-del" title="Delete" onclick="return confirm('Are you sure?')">🗑️</a>
                        <?php else: ?>
                        <button class="cat-act-btn cat-act-disabled" title="Has <?= $cat['nb_products'] ?> product(s)" disabled>🗑️</button>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <form action="actions/category_actions.php" method="POST" class="add-cat-form">
                <input type="hidden" name="action" value="add_category">
                <input type="hidden" name="redirect_to" value="../products.php">
                <input type="hidden" name="emoji" value="📁" id="newCatEmoji">
                <div class="add-cat-row">
                    <button type="button" class="emoji-pick-btn" id="newCatEmojiBtn" onclick="openEmojiPicker('newCatEmoji', 'newCatEmojiBtn')" title="Pick emoji">📁</button>
                    <input type="text" name="nom_cat" placeholder="New category..." maxlength="50" required class="add-cat-input">
                    <button type="submit" class="btn-add-cat" title="Add">+</button>
                </div>
            </form>
        </nav>
        <div class="sidebar-footer">
            <p>&copy; <?= date('Y') ?> LAFORMATIK</p>
            <p>Developped by Adam Abida and Hedi Moalla</p>
        </div>
    </aside>

    <!-- ══════════ MAIN ══════════ -->
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
            <div>
                <h1 class="page-title"><?= $catTitle ?></h1>
                <p class="page-subtitle"><?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?> found</p>
            </div>
            <a href="products.php?<?= filterQS(['form' => 'add']) ?>" class="btn btn-primary">＋ Add Product</a>
        </header>

        <!-- Search & Sort -->
        <div class="toolbar">
            <form method="GET" action="products.php" class="search-form">
                <?php if ($currentCat): ?><input type="hidden" name="cat" value="<?= (int)$currentCat ?>"><?php endif; ?>
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sortCol) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($sortDir) ?>">
                <div class="search-bar">
                    <span class="search-ico">🔍</span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by designation...">
                    <button type="submit" class="btn btn-sm">Search</button>
                </div>
            </form>
            <div class="sort-controls">
                <span class="sort-label">Sort:</span>
                <?php
                $sb = ($currentCat ? "cat={$currentCat}&" : '') . ($search ? "search=".urlencode($search)."&" : '');
                $nd = $sortDir === 'ASC' ? 'DESC' : 'ASC';
                $cols = ['designation'=>'Name','prix'=>'Price','marque'=>'Brand','quantite'=>'Stock'];
                foreach ($cols as $col => $label): ?>
                <a href="products.php?<?= $sb ?>sort=<?= $col ?>&dir=<?= $sortCol === $col ? $nd : 'ASC' ?>" class="sort-btn <?= $sortCol === $col ? 'active-sort' : '' ?>"><?= $label ?> <?= $sortCol === $col ? ($sortDir === 'ASC' ? '↑' : '↓') : '' ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ─── ADD / EDIT FORM ─── -->
        <?php if ($formMode === 'add' || ($formMode === 'edit' && $editProduct)): ?>
        <section class="form-section">
            <h2 class="form-title"><?= $formMode === 'edit' ? 'Edit Product' : 'Add New Product' ?></h2>
            <form action="actions/product_actions.php" method="POST" enctype="multipart/form-data" class="product-form">
                <input type="hidden" name="action" value="<?= $formMode ?>">
                <input type="hidden" name="current_cat" value="<?= htmlspecialchars($currentCat) ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="fRef">Reference *</label>
                        <input type="text" id="fRef" name="<?= $formMode === 'edit' ? '_ref_display' : 'ref_prod' ?>" maxlength="20" required
                            value="<?= $editProduct ? htmlspecialchars($editProduct['ref_prod']) : '' ?>"
                            <?= $formMode === 'edit' ? 'disabled' : '' ?> placeholder="e.g. LAP-004">
                        <?php if ($formMode === 'edit'): ?>
                        <input type="hidden" name="ref_prod" value="<?= htmlspecialchars($editProduct['ref_prod']) ?>">
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="fDesig">Designation *</label>
                        <input type="text" id="fDesig" name="designation" maxlength="100" required
                            value="<?= $editProduct ? htmlspecialchars($editProduct['designation']) : '' ?>" placeholder="Product name">
                    </div>
                    <div class="form-group">
                        <label for="fBrand">Brand</label>
                        <input type="text" id="fBrand" name="marque" maxlength="50"
                            value="<?= $editProduct ? htmlspecialchars($editProduct['marque']) : '' ?>" placeholder="e.g. HP, Dell">
                    </div>
                    <div class="form-group">
                        <label for="fCat">Category *</label>
                        <select id="fCat" name="id_cat" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id_cat'] ?>" <?= ($editProduct && $editProduct['id_cat'] == $cat['id_cat']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['nom_cat']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fPrice">Price (TND) *</label>
                        <input type="number" id="fPrice" name="prix" step="0.01" min="0.01" required
                            value="<?= $editProduct ? $editProduct['prix'] : '' ?>" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="fQty">Quantity *</label>
                        <input type="number" id="fQty" name="quantite" min="0" required
                            value="<?= $editProduct ? $editProduct['quantite'] : '0' ?>" placeholder="0">
                    </div>
                    <div class="form-group form-full">
                        <label for="fDesc">Description</label>
                        <textarea id="fDesc" name="description" rows="3" placeholder="Product description..."><?= $editProduct ? htmlspecialchars($editProduct['description']) : '' ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="fPhoto">Photo</label>
                        <input type="file" id="fPhoto" name="photo" accept="image/*" onchange="previewImage(this)">
                        <div class="img-preview" id="imgPreview">
                            <?php if ($editProduct && $editProduct['photo']): ?>
                                <img src="uploads/<?= htmlspecialchars($editProduct['photo']) ?>" alt="Current photo">
                            <?php else: ?>
                                <span class="preview-text">Image preview</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="products.php?<?= filterQS() ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?= $formMode === 'edit' ? 'Update Product' : 'Save Product' ?></button>
                </div>
            </form>
        </section>
        <?php endif; ?>

        <!-- ─── SELL FORM ─── -->
        <?php if ($formMode === 'sell' && $sellProduct): ?>
        <section class="form-section">
            <h2 class="form-title">Record a Sale</h2>
            <form action="actions/product_actions.php" method="POST" class="product-form">
                <input type="hidden" name="action" value="sell">
                <input type="hidden" name="ref_prod" value="<?= htmlspecialchars($sellProduct['ref_prod']) ?>">
                <input type="hidden" name="current_cat" value="<?= htmlspecialchars($currentCat) ?>">
                <div class="sell-info">
                    <strong><?= htmlspecialchars($sellProduct['designation']) ?></strong>
                    &mdash; Current stock: <strong><?= $sellProduct['quantite'] ?></strong>
                </div>
                <div class="form-group" style="max-width:300px;">
                    <label for="fSellQty">Quantity Sold *</label>
                    <input type="number" id="fSellQty" name="qty_sold" min="1" max="<?= $sellProduct['quantite'] ?>" required value="1">
                </div>
                <div class="form-actions">
                    <a href="products.php?<?= filterQS() ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success">Confirm Sale</button>
                </div>
            </form>
        </section>
        <?php endif; ?>

        <!-- ─── PRODUCTS TABLE ─── -->
        <div class="table-wrap">
        <?php if (empty($products)): ?>
            <div class="empty-state"><span class="empty-icon">📭</span><p>No products found.</p></div>
        <?php else: ?>
            <table class="prod-table">
                <thead><tr>
                    <th>Photo</th><th>Reference</th><th>Designation</th><th>Brand</th>
                    <th>Price (TND)</th><th>Stock</th><th>Category</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($products as $p):
                    $qty = intval($p['quantite']);
                    $isLow = $qty > 0 && $qty < 5;
                    $isZero = $qty === 0;
                    $sCls = $isZero ? 'stock-zero' : ($isLow ? 'stock-danger' : ($qty < 10 ? 'stock-warn' : 'stock-ok'));
                    $rCls = ($isLow || $isZero) ? 'low-stock-row' : '';
                    $fq = filterQS();
                ?>
                <tr class="<?= $rCls ?>">
                    <td><?php if ($p['photo']): ?><img src="uploads/<?= htmlspecialchars($p['photo']) ?>" class="prod-thumb"><?php else: ?><div class="prod-thumb-empty">📷</div><?php endif; ?></td>
                    <td><strong><?= htmlspecialchars($p['ref_prod']) ?></strong></td>
                    <td><?= htmlspecialchars($p['designation']) ?></td>
                    <td><?= htmlspecialchars($p['marque'] ?: '—') ?></td>
                    <td><?= number_format($p['prix'], 2) ?></td>
                    <td><span class="stock-badge <?= $sCls ?>"><?= $isLow ? '⚠ ' : '' ?><?= $qty ?></span></td>
                    <td><?= htmlspecialchars($p['nom_cat']) ?></td>
                    <td><div class="action-btns">
                        <a href="products.php?<?= $fq ?>&form=edit&ref=<?= urlencode($p['ref_prod']) ?>" class="btn-icon" title="Edit">✏️</a>
                        <?php if (!$isZero): ?>
                        <a href="products.php?<?= $fq ?>&form=sell&ref=<?= urlencode($p['ref_prod']) ?>" class="btn-icon btn-icon-success" title="Sell">🛒</a>
                        <?php endif; ?>
                        <?php if ($qty === 0): ?>
                        <a href="actions/product_actions.php?delete_ref=<?= urlencode($p['ref_prod']) ?>&cat=<?= urlencode($currentCat) ?>" class="btn-icon btn-icon-danger" title="Delete" onclick="return confirm('Are you sure?')">🗑️</a>
                        <?php else: ?>
                        <span class="btn-icon btn-icon-disabled" title="Stock must be 0">🗑️</span>
                        <?php endif; ?>
                    </div></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        </div>
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
                <input type="hidden" name="redirect_to" value="../products.php">
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
            <div class="emoji-grid" id="emojiGrid"></div>
        </div>
    </div>

    <script src="assets/script.js"></script>
</body>
</html>
