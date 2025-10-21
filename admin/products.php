<?php
require __DIR__ . '/layout/init.php';

$currentPage = 'products';
$headerTitle = 'Products';
$assetBase = '/Dashborad';

$summary = [
  'total_products' => 0,
  'total_stock' => 0,
  'inventory_value' => 0,
  'average_price' => 0,
];

try {
  if ($result = $conn->query('SELECT COUNT(*) AS total, COALESCE(SUM(CurrentStock),0) AS stock, COALESCE(SUM(CurrentStock * UnitPrice),0) AS inventory_value, COALESCE(AVG(UnitPrice),0) AS avg_price FROM product_master')) {
    if ($row = $result->fetch_assoc()) {
      $summary['total_products'] = (int)$row['total'];
      $summary['total_stock'] = (float)$row['stock'];
      $summary['inventory_value'] = (float)$row['inventory_value'];
      $summary['average_price'] = (float)$row['avg_price'];
    }
    $result->free();
  }
} catch (mysqli_sql_exception $exception) {
  // keep defaults
}

$pageScripts = ['/admin/js/products.js'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Products - Rewarity Admin</title>
    <base href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES); ?>/">
    <link href="vendor/jquery-nice-select/css/nice-select.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/nouislider/nouislider.min.css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div id="preloader">
        <div class="waviy">
           <span style="--i:1">L</span>
           <span style="--i:2">o</span>
           <span style="--i:3">a</span>
           <span style="--i:4">d</span>
           <span style="--i:5">i</span>
           <span style="--i:6">n</span>
           <span style="--i:7">g</span>
           <span style="--i:8">.</span>
           <span style="--i:9">.</span>
           <span style="--i:10">.</span>
        </div>
    </div>

    <?php require __DIR__ . '/layout/header.php'; ?>
    <?php require __DIR__ . '/layout/sidebar.php'; ?>

    <div class="content-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-3 col-lg-6 col-sm-6">
                    <div class="card gradient-1">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-auto">
                                <h3 class="text-white mb-0"><?php echo number_format($summary['total_products']); ?></h3>
                                <span class="text-white">Total Products</span>
                            </div>
                            <div class="card-icon text-white"><i class="las la-box fs-40"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-sm-6">
                    <div class="card gradient-3">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-auto">
                                <h3 class="text-white mb-0"><?php echo number_format($summary['total_stock'], 2); ?></h3>
                                <span class="text-white">Total Stock</span>
                            </div>
                            <div class="card-icon text-white"><i class="las la-cubes fs-40"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-sm-6">
                    <div class="card gradient-2">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-auto">
                                <h3 class="text-white mb-0">₹<?php echo number_format($summary['inventory_value'], 2); ?></h3>
                                <span class="text-white">Inventory Value</span>
                            </div>
                            <div class="card-icon text-white"><i class="las la-warehouse fs-40"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-sm-6">
                    <div class="card gradient-4">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-auto">
                                <h3 class="text-white mb-0">₹<?php echo number_format($summary['average_price'], 2); ?></h3>
                                <span class="text-white">Average Price</span>
                            </div>
                            <div class="card-icon text-white"><i class="las la-tag fs-40"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" id="filterSearch" class="form-control" placeholder="Product name or code">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select id="filterStatus" class="form-select">
                                <option value="">All</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Min Stock</label>
                            <input type="number" min="0" step="0.01" id="filterMinStock" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button id="searchProducts" class="btn btn-primary w-100"><i class="las la-search me-1"></i>Search</button>
                            <button id="resetProducts" class="btn btn-light border w-100"><i class="las la-sync me-1"></i>Reset</button>
                        </div>
                        <div class="col-12 text-end">
                            <button id="exportProducts" class="btn btn-info text-white me-2"><i class="las la-file-export me-1"></i>Export</button>
                            <button id="openProductModal" class="btn btn-success"><i class="las la-plus-circle me-1"></i>Add Product</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="productsTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Unit Price (₹)</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Loading products...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="productForm">
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Product Name<span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="product_name" id="productName" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Product Code<span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="product_code" id="productCode" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Unit Price (₹)<span class="text-danger">*</span></label>
                  <input type="number" min="0" step="0.01" class="form-control" name="unit_price" id="productPrice" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Initial Stock<span class="text-danger">*</span></label>
                  <input type="number" min="0" step="0.01" class="form-control" name="current_stock" id="productStock" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Start Date</label>
                  <input type="date" class="form-control" name="start_date" id="productStartDate">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Status</label>
                  <select class="form-select" name="is_active" id="productStatus">
                    <option value="1" selected>Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label">Notes</label>
                  <textarea class="form-control" name="notes" id="productNotes" rows="3" placeholder="Optional details"></textarea>
                </div>
              </div>
              <div class="alert alert-danger d-none mt-3" id="productError"></div>
              <div class="alert alert-success d-none mt-3" id="productSuccess"></div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Product</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <?php require __DIR__ . '/layout/footer.php'; ?>
</body>
</html>
