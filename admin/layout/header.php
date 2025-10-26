<?php
$assetBase = $assetBase ?? '/Dashborad';
$adminName = $adminName ?? 'Admin';
$adminEmail = $adminEmail ?? '';
$headerTitle = $headerTitle ?? 'Dashboard';
?>
<div id="main-wrapper">
  <div class="nav-header">
      <a href="/admin/dashboard.php" class="brand-logo">
        <svg class="logo-abbr" width="53" height="53" viewBox="0 0 53 53">
          <path class="svg-logo-primary-path" d="M48.3418 41.8457H41.0957C36.8148 41.8457 33.332 38.3629 33.332 34.082C33.332 29.8011 36.8148 26.3184 41.0957 26.3184H48.3418V19.2275C48.3418 16.9408 46.4879 15.0869 44.2012 15.0869H4.14062C1.85386 15.0869 0 16.9408 0 19.2275V48.8594C0 51.1462 1.85386 53 4.14062 53H44.2012C46.4879 53 48.3418 51.1462 48.3418 48.8594V41.8457Z" fill="#5BCFC5"/>
          <path class="svg-logo-primary-path" d="M51.4473 29.4238H41.0957C38.5272 29.4238 36.4375 31.5135 36.4375 34.082C36.4375 36.6506 38.5272 38.7402 41.0957 38.7402H51.4473C52.3034 38.7402 53 38.0437 53 37.1875V30.9766C53 30.1204 52.3034 29.4238 51.4473 29.4238ZM41.0957 35.6348C40.2382 35.6348 39.543 34.9396 39.543 34.082C39.543 33.2245 40.2382 32.5293 41.0957 32.5293C41.9532 32.5293 42.6484 33.2245 42.6484 34.082C42.6484 34.9396 41.9532 35.6348 41.0957 35.6348Z" fill="#5BCFC5"/>
        </svg>
        <p class="brand-title" style="font-size: 30px;">Rewarity</p>
      </a>
      <div class="nav-control">
          <div class="hamburger">
              <span class="line"></span><span class="line"></span><span class="line"></span>
          </div>
      </div>
  </div>
  <div class="header">
      <div class="header-content">
          <div class="page-loader"><div class="spinner"></div></div>
          <nav class="navbar navbar-expand">
              <div class="collapse navbar-collapse justify-content-between">
                  <div class="header-left">
            <div class="dashboard_bar">
                <?php echo htmlspecialchars($headerTitle); ?>
            </div>
          </div>
                  <ul class="navbar-nav header-right">
            <!-- Theme switcher -->
            <!-- <li class="nav-item dropdown me-2">
              <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Theme">
                <i class="las la-adjust"></i>
              </a>
              <div class="dropdown-menu p-3 theme-menu" style="min-width:260px;">
                <div class="mb-2 section-title">Theme</div>
                <div class="d-flex gap-2 mb-3 flex-wrap">
                  <button class="theme-chip" data-set-theme="light">Light</button>
                  <button class="theme-chip" data-set-theme="grey">Grey</button>
                  <button class="theme-chip" data-set-theme="dark">Dark</button>
                </div>
                <div class="mb-2 section-title">Accent</div>
                <div class="d-flex gap-3">
                  <span class="accent-dot" style="background:#1DB954" data-set-accent="green" title="Green"></span>
                  <span class="accent-dot" style="background:#3b82f6" data-set-accent="blue" title="Blue"></span>
                  <span class="accent-dot" style="background:#8b5cf6" data-set-accent="violet" title="Violet"></span>
                  <span class="accent-dot" style="background:#f59e0b" data-set-accent="orange" title="Orange"></span>
                </div>
              </div>
            </li> -->
            <!-- Search temporarily hidden -->
            <!--
            <li class="nav-item">
              <div class="input-group search-area">
                <input type="text" class="form-control" placeholder="Search here...">
                <span class="input-group-text"><a href="javascript:void(0)"><i class="flaticon-381-search-2"></i></a></span>
              </div>
            </li>
            -->
            <!-- Gift/Message temporarily hidden -->
            <!--
            <li class="nav-item dropdown notification_dropdown">
                <a class="nav-link" href="javascript:void(0);" data-bs-toggle="dropdown">
                  ...
                </a>
            </li>
            <li class="nav-item dropdown notification_dropdown">
                <a class="nav-link  ai-icon" href="javascript:void(0);" role="button" data-bs-toggle="dropdown">
                  ...
                </a>
            </li>
            -->
            <li class="nav-item dropdown notification_dropdown">
                <a class="nav-link bell bell-link" href="javascript:void(0);">
                  <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M27 7.88883C27 5.18897 24.6717 3 21.8 3C17.4723 3 10.5277 3 6.2 3C3.3283 3 1 5.18897 1 7.88883V23.7776C1 24.2726 1.31721 24.7174 1.80211 24.9069C2.28831 25.0963 2.8473 24.9912 3.2191 24.6417C3.2191 24.6417 5.74629 22.2657 7.27769 20.8272C7.76519 20.3688 8.42561 20.1109 9.11591 20.1109H21.8C24.6717 20.1109 27 17.922 27 15.2221V7.88883ZM24.4 7.88883C24.4 6.53951 23.2365 5.44441 21.8 5.44441C17.4723 5.44441 10.5277 5.44441 6.2 5.44441C4.7648 5.44441 3.6 6.53951 3.6 7.88883V20.8272L5.4382 19.0989C6.4132 18.1823 7.73661 17.6665 9.11591 17.6665H21.8C23.2365 17.6665 24.4 16.5726 24.4 15.2221V7.88883ZM7.5 15.2221H17.9C18.6176 15.2221 19.2 14.6745 19.2 13.9999C19.2 13.3252 18.6176 12.7777 17.9 12.7777H7.5C6.7824 12.7777 6.2 13.3252 6.2 13.9999C6.2 14.6745 6.7824 15.2221 7.5 15.2221ZM7.5 10.3333H20.5C21.2176 10.3333 21.8 9.7857 21.8 9.11104C21.8 8.43638 21.2176 7.88883 20.5 7.88883H7.5C6.7824 7.88883 6.2 8.43638 6.2 9.11104C6.2 9.7857 6.7824 10.3333 7.5 10.3333Z" fill="#4f7086"/>
                  </svg>
                  <span class="badge light text-white bg-primary rounded-circle">5</span>
                </a>
            </li>
            <li class="nav-item dropdown header-profile ms-3">
              <a class="nav-link d-flex align-items-center px-3 py-2 border rounded-pill" href="javascript:void(0);" role="button" data-bs-toggle="dropdown">
                <img src="<?php echo htmlspecialchars($profileImageUrl ?? 'images/ion/man (1).png', ENT_QUOTES); ?>" width="44" height="44" style="object-fit:cover;border-radius:50%" alt=""/>
                <div class="header-info ms-2 text-start">
                  <span class="font-w600 d-block">Hi, <b><?php echo htmlspecialchars($adminName); ?></b></span>
                  <?php if ($adminEmail): ?>
                  <small class="text-muted"><?php echo htmlspecialchars($adminEmail); ?></small>
                  <?php endif; ?>
                </div>
              </a>
              <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" href="/admin/profile.php">View Profile</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="/admin/logout.php">Logout</a>
              </div>
            </li>
          </ul>
              </div>
          </nav>
      </div>
  </div>
</div>
