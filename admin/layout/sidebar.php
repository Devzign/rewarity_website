<?php
$currentPage = $currentPage ?? '';
?>
<div class="dlabnav">
    <div class="dlabnav-scroll">
		<ul class="metismenu" id="menu">
            <li class="<?php echo $currentPage === 'dashboard' ? 'mm-active' : ''; ?>">
              <a href="/admin/dashboard.php" aria-expanded="false">
                <i class="flaticon-025-dashboard"></i>
                <span class="nav-text">Dashboard</span>
              </a>
            </li>
            <li class="<?php echo $currentPage === 'users' ? 'mm-active' : ''; ?>">
              <a href="/admin/users.php" aria-expanded="false">
                <i class="las la-users"></i>
                <span class="nav-text">User Management</span>
              </a>
            </li>
            <?php if (!empty($canManageRoles) && $canManageRoles === true): ?>
            <li class="<?php echo $currentPage === 'roles' ? 'mm-active' : ''; ?>">
              <a href="/admin/roles.php" aria-expanded="false">
                <i class="las la-id-badge"></i>
                <span class="nav-text">User Roles</span>
              </a>
            </li>
            <?php endif; ?>
            <li class="<?php echo $currentPage === 'products' ? 'mm-active' : ''; ?>">
              <a href="/admin/products.php" aria-expanded="false">
                <i class="las la-box"></i>
                <span class="nav-text">Products</span>
              </a>
            </li>
            <li class="<?php echo $currentPage === 'colors' ? 'mm-active' : ''; ?>">
              <a href="/admin/colors.php" aria-expanded="false">
                <i class="las la-palette"></i>
                <span class="nav-text">Colors</span>
              </a>
            </li>
            <li class="<?php echo $currentPage === 'categories' ? 'mm-active' : ''; ?>">
              <a href="/admin/categories.php" aria-expanded="false">
                <i class="las la-tags"></i>
                <span class="nav-text">Categories</span>
              </a>
            </li>
            <li class="<?php echo $currentPage === 'orders' ? 'mm-active' : ''; ?>">
              <a href="/admin/orders.php" aria-expanded="false">
                <i class="las la-shopping-cart"></i>
                <span class="nav-text">Orders &amp; Purchases</span>
              </a>
            </li>
        </ul>
	</div>
</div>
