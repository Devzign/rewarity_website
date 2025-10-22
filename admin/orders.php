<?php
require __DIR__ . '/layout/init.php';

$currentPage = 'orders';
$headerTitle = 'Purchase Transactions';

$totalOrders = 0;
$totalQuantity = 0;
$totalAmount = 0.0;
$avgAmount = 0.0;

try {
  if ($result = $conn->query('SELECT COUNT(*) AS total, COALESCE(SUM(TotalAmount),0) AS amount FROM order_master')) {
    if ($row = $result->fetch_assoc()) {
      $totalOrders = (int)$row['total'];
      $totalAmount = (float)$row['amount'];
    }
    $result->free();
  }

  if ($result = $conn->query('SELECT COALESCE(SUM(Quantity),0) AS qty FROM order_items')) {
    if ($row = $result->fetch_assoc()) {
      $totalQuantity = (float)$row['qty'];
    }
    $result->free();
  }

  if ($totalOrders > 0) {
    $avgAmount = $totalAmount / $totalOrders;
  }
} catch (mysqli_sql_exception $exception) {
  $totalOrders = 0;
  $totalQuantity = 0;
  $totalAmount = 0.0;
  $avgAmount = 0.0;
}

$pageScripts = ['/admin/js/orders.js'];
$assetBase = '/Dashborad';
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Purchase Transactions - Rewarity Admin</title>
    <base href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES); ?>/">

	<link href="vendor/jquery-nice-select/css/nice-select.css" rel="stylesheet">
	<link rel="stylesheet" href="vendor/nouislider/nouislider.min.css">
	<link href="css/style.css" rel="stylesheet">

</head>
<body>

    <div id="preloader">
        <div class="waviy">
           <span style="--i:1">R</span>
           <span style="--i:2">E</span>
           <span style="--i:3">W</span>
           <span style="--i:4">A</span>
           <span style="--i:5">R</span>
           <span style="--i:6">I</span>
           <span style="--i:7">T</span>
           <span style="--i:8">Y</span>
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
                            <h3 class="text-white mb-0"><?php echo number_format($totalOrders); ?></h3>
                            <span class="text-white">Total Orders</span>
                        </div>
                        <div class="card-icon text-white">
                            <i class="las la-shopping-cart fs-40"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="card gradient-3">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-auto">
                            <h3 class="text-white mb-0"><?php echo number_format($totalQuantity, 2); ?></h3>
                            <span class="text-white">Total Quantity</span>
                        </div>
                        <div class="card-icon text-white">
                            <i class="las la-box-open fs-40"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="card gradient-2">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-auto">
                            <h3 class="text-white mb-0">₹<?php echo number_format($totalAmount, 2); ?></h3>
                            <span class="text-white">Total Amount</span>
                        </div>
                        <div class="card-icon text-white">
                            <i class="las la-rupee-sign fs-40"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="card gradient-4">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-auto">
                            <h3 class="text-white mb-0">₹<?php echo number_format($avgAmount, 2); ?></h3>
                            <span class="text-white">Average Order</span>
                        </div>
                        <div class="card-icon text-white">
                            <i class="las la-chart-line fs-40"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Product</label>
                        <select id="filterProduct" class="form-select">
                            <option value="">All Products</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Dealer</label>
                        <select id="filterDealer" class="form-select">
                            <option value="">All Dealers</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Distributor</label>
                        <select id="filterDistributor" class="form-select">
                            <option value="">All Distributors</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Salesperson</label>
                        <select id="filterSalesperson" class="form-select">
                            <option value="">All Salespersons</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" id="filterStartDate" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" id="filterEndDate" class="form-control" />
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button id="searchOrders" class="btn btn-primary w-100"><i class="las la-search me-1"></i>Search</button>
                        <button id="resetOrders" class="btn btn-light border w-100"><i class="las la-sync me-1"></i>Reset</button>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <button id="exportOrders" class="btn btn-info text-white me-2"><i class="las la-file-download me-1"></i>Export</button>
                        <button id="openOrderModal" class="btn btn-success"><i class="las la-plus-circle me-1"></i>Add New Order</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Dealer</th>
                                <th>Distributor</th>
                                <th>Salesperson</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Unit Price (₹)</th>
                                <th>Total (₹)</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="11" class="text-center text-muted">Loading orders...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Purchase / Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="orderForm" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Dealer<span class="text-danger">*</span></label>
              <select class="form-select" name="dealer_id" id="orderDealer" required></select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Distributor<span class="text-danger">*</span></label>
              <select class="form-select" name="distributor_id" id="orderDistributor" required></select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Assign Salesperson<span class="text-danger">*</span></label>
              <select class="form-select" name="salesperson_id" id="orderSalesperson" required></select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Product<span class="text-danger">*</span></label>
              <select class="form-select" name="product_id" id="orderProduct" required></select>
              <small class="text-muted" id="orderProductStock">Current Stock: 0</small>
            </div>
            <div class="col-md-4">
              <label class="form-label">Quantity<span class="text-danger">*</span></label>
              <input type="number" min="0" step="0.01" class="form-control" name="quantity" id="orderQuantity" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Unit Price (₹)<span class="text-danger">*</span></label>
              <input type="number" min="0" step="0.01" class="form-control" name="unit_price" id="orderUnitPrice" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Order Date<span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="order_date" id="orderDate" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Upload Attachment</label>
              <input type="file" class="form-control" name="attachment" id="orderAttachment" accept="image/*,application/pdf">
            </div>
            <div class="col-md-6">
              <label class="form-label">Total Amount (₹)</label>
              <input type="number" min="0" step="0.01" class="form-control" name="total_amount" id="orderTotal" readonly>
            </div>
            <div class="col-12">
              <label class="form-label">Notes</label>
              <textarea class="form-control" name="notes" id="orderNotes" rows="3" placeholder="Optional notes"></textarea>
            </div>
          </div>
          <div class="alert alert-danger d-none mt-3" id="orderError"></div>
          <div class="alert alert-success d-none mt-3" id="orderSuccess"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Order</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
