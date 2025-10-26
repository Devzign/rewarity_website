<?php
require __DIR__ . '/layout/init.php';

$currentPage = 'categories';
$headerTitle = 'Categories';
$assetBase = '/Dashborad';
$pageScripts = ['/admin/js/categories.js'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Categories - Rewarity Admin</title>
    <base href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES); ?>/">
    <link href="vendor/jquery-nice-select/css/nice-select.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/nouislider/nouislider.min.css">
    <script>try{var t=localStorage.getItem('rewarity_theme')||'light';var a=localStorage.getItem('rewarity_accent')||'green';var h=document.documentElement;h.setAttribute('data-theme',t);h.setAttribute('data-accent',a);}catch(e){}</script>
    <link href="css/style.css" rel="stylesheet">
    <link href="css/theme.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/layout/header.php'; ?>
  <?php require __DIR__ . '/layout/sidebar.php'; ?>

  <div class="content-body">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="las la-tags me-2"></i>Categories</h4>
        <button type="button" class="btn btn-success" id="openCategoryModal"><i class="las la-plus-circle me-2"></i>Add Category</button>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover" id="categoriesTable">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Description</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr><td colspan="5" class="text-center text-muted py-4">Loading...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="categoryForm">
          <div class="modal-body">
            <input type="hidden" name="id" id="categoryId">
            <div class="mb-3">
              <label class="form-label">Name<span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" id="categoryName" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" id="categoryDescription" rows="3"></textarea>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_active" id="categoryActive" checked>
              <label class="form-check-label" for="categoryActive">Active</label>
            </div>
            <div class="alert alert-danger d-none mt-3" id="categoryError"></div>
            <div class="alert alert-success d-none mt-3" id="categorySuccess"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php require __DIR__ . '/layout/footer.php'; ?>
</body>
</html>
