<!DOCTYPE html>
<html lang="zxx">
    <head>
<?php
include('assets/php/head.php');
?>
    </head>
    <body>
        <!-- Dark/Light Toggle -->
		<div class="dark-version">
            <label id="switch" class="switch">
                <input type="checkbox" onchange="toggleTheme()" id="slider">
                <span class="slider round"></span>
            </label>
        </div>

        <!-- Start Preloader Area -->
        <div class="preloader-area">
            <div class="spinner">
                <div class="inner">
                    <div class="disc"></div>
                    <div class="disc"></div>
                    <div class="disc"></div>
                </div>
            </div>
        </div>
        <!-- End Preloader Area -->

        <!-- Start Navbar Area -->
        <?php
include('assets/php/nav.php');
?>
        <!-- End Navbar Area -->

        <!-- ...existing code from blog-1.php main content... -->

        <!-- Start Footer Area -->
       <?php
include('assets/php/footer.php');
?>
        <!-- End Footer Area -->

        <div class="go-top"><i class='bx bx-chevron-up'></i></div>
        <!-- ...existing JS includes... -->
    </body>
</html>
