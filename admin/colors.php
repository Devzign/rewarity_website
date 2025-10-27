<?php
require __DIR__ . '/layout/init.php';

$currentPage = 'colors';
$headerTitle = 'Colors';
$assetBase = '/Dashborad';
$pageScripts = ['/admin/js/colors.js'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Colors - Rewarity Admin</title>
    <base href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES); ?>/">
    <link href="vendor/jquery-nice-select/css/nice-select.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/nouislider/nouislider.min.css">
    <script>try{var t=localStorage.getItem('rewarity_theme')||'light';var a=localStorage.getItem('rewarity_accent')||'green';var h=document.documentElement;h.setAttribute('data-theme',t);h.setAttribute('data-accent',a);}catch(e){}</script>
    <link href="css/style.css" rel="stylesheet">
    <link href="css/theme.css" rel="stylesheet">
    <style>
      .color-swatch{display:inline-block;width:28px;height:28px;border-radius:6px;border:1px solid #ddd;vertical-align:middle;margin-right:8px}
      .form-text-error{color:#c44;font-size:0.875rem;margin-top:4px}
    </style>
    <script>
      try { window.dispatchEvent(new Event('rewarity:data-loading')); window.addEventListener('load', function(){ setTimeout(function(){ window.dispatchEvent(new Event('rewarity:data-ready')); }, 350); }); } catch(e) {}
    </script>
  </head>
  <body>
    <div id="preloader"><div class="waviy"><span style="--i:1">R</span><span style="--i:2">E</span><span style="--i:3">W</span><span style="--i:4">A</span><span style="--i:5">R</span><span style="--i:6">I</span><span style="--i:7">T</span><span style="--i:8">Y</span></div></div>
    <?php require __DIR__ . '/layout/header.php'; ?>
    <?php require __DIR__ . '/layout/sidebar.php'; ?>

    <div class="content-body">
      <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0"><i class="las la-palette me-2"></i>Colors</h4>
          <button type="button" class="btn btn-success" id="openColorModal"><i class="las la-plus-circle me-2"></i>Add Color</button>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover" id="colorsTable">
                <thead>
                  <tr>
                    <th>Color</th>
                    <th>Code</th>
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

    <div class="modal fade" id="colorModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="colorModalTitle">Add Color</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="colorForm">
            <div class="modal-body">
              <input type="hidden" name="id" id="colorId">

              <div class="mb-3">
                <label class="form-label">Color Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" id="colorName" placeholder="Red" required>
                <div class="form-text-error d-none" id="colorNameErr">Color Name is required.</div>
              </div>

              <div class="mb-2">
                <label class="form-label">Color Code <span class="text-danger">*</span></label>
                <div class="d-flex align-items-center" style="gap:10px;">
                  <input type="color" id="colorPicker" value="#ff0000" title="Pick color" style="width:56px;height:38px;padding:2px">
                  <input type="text" class="form-control" name="hex" id="colorHex" placeholder="#FF0000" maxlength="7" style="max-width:160px">
                  <span class="color-swatch" id="colorSwatch" style="background:#ff0000"></span>
                </div>
                <div class="form-text-error d-none" id="colorHexErr">Color Code is required.</div>
              </div>

              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="is_active" id="colorActive" checked>
                <label class="form-check-label" for="colorActive">Active</label>
              </div>

              <div class="alert alert-danger d-none mt-3" id="colorError"></div>
              <div class="alert alert-success d-none mt-3" id="colorSuccess"></div>
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

