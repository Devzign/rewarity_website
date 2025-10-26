<?php $assetBase = $assetBase ?? '/Dashborad'; ?>
        <link rel="stylesheet" href="css/theme.css">
        <div class="footer">
            <div class="copyright">
                <p>© Designed &amp; by <a href="#" target="_blank">Animation Coding</a> 2022</p>
            </div>
        </div>
    </div> <!-- End of main-wrapper -->

    <script src="vendor/global/global.min.js"></script>
    <script src="vendor/chart.js/Chart.bundle.min.js"></script>
    <script src="vendor/jquery-nice-select/js/jquery.nice-select.min.js"></script>
    <script src="vendor/apexchart/apexchart.js"></script>
    <script src="vendor/nouislider/nouislider.min.js"></script>
    <script src="vendor/wnumb/wNumb.js"></script>
    <script src="js/dashboard/dashboard-1.js"></script>
    <script src="js/custom.min.js"></script>
    <script src="js/dlabnav-init.js"></script>
    <script src="js/demo.js"></script>
    <script src="js/styleSwitcher.js"></script>
    <script src="js/boot.js"></script>
    <script src="js/theme.js"></script>
    <?php
    if (!empty($pageScripts)) {
        foreach ((array)$pageScripts as $scriptPath) {
            $src = htmlspecialchars($scriptPath, ENT_QUOTES);
            echo "    <script src=\"{$src}\"></script>\n";
        }
    }
    ?>
</body>
</html>
