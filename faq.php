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

        <!-- Start FAQ Area -->
        <section class="faq-area ptb-100">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-lg-7 col-md-12">
                        <div class="faq-accordion">
                            <h2>Frequently Asked Questions about <span>onMilap</span></h2>
                            <ul class="accordion">
                                <li class="accordion-item">
                                    <a class="accordion-title" href="javascript:void(0)">
                                        <i class="bx bx-plus"></i>
                                        What is onMilap and what can it be used for?
                                    </a>
                                    <p class="accordion-content">
                                        onMilap is a versatile video chat platform designed for seamless virtual communication. It can be used for webinars, online dating, remote work, customer support, and live streaming, all with HD video quality.
                                    </p>
                                </li>
                                <li class="accordion-item">
                                    <a class="accordion-title" href="javascript:void(0)">
                                        <i class="bx bx-plus"></i>
                                        Does onMilap support HD video calls?
                                    </a>
                                    <p class="accordion-content">
                                        Yes, onMilap is equipped with HD video call capabilities to ensure clear and reliable communication for all users.
                                    </p>
                                </li>
                                <li class="accordion-item">
                                    <a class="accordion-title" href="javascript:void(0)">
                                        <i class="bx bx-plus"></i>
                                        Can I use onMilap for business meetings and remote work?
                                    </a>
                                    <p class="accordion-content">
                                        Absolutely! onMilap is ideal for remote work, team collaboration, and business meetings, offering features that support productivity and engagement.
                                    </p>
                                </li>
                                <li class="accordion-item">
                                    <a class="accordion-title" href="javascript:void(0)">
                                        <i class="bx bx-plus"></i>
                                        Is onMilap suitable for hosting webinars and live events?
                                    </a>
                                    <p class="accordion-content">
                                        Yes, onMilap is designed to handle webinars and live streaming events, providing interactive tools and a stable platform for large audiences.
                                    </p>
                                </li>
                                <li class="accordion-item">
                                    <a class="accordion-title" href="javascript:void(0)">
                                        <i class="bx bx-plus"></i>
                                        How does onMilap help with customer support?
                                    </a>
                                    <p class="accordion-content">
                                        onMilap enables real-time video support, allowing businesses to connect with customers face-to-face, resolve issues quickly, and build stronger relationships.
                                    </p>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-12">
                        <div class="faq-image">
                            <img src="assets/img/faq-img1.png" alt="image">
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End FAQ Area -->

        <!-- Start Footer Area -->
       <?php
include('assets/php/footer.php');
?>
        <!-- End Footer Area -->

        <div class="go-top"><i class='bx bx-chevron-up'></i></div>
        <!-- jQuery Min JS -->
        <script src="assets/js/jquery.min.js"></script>
        <!-- Bootstrap Min JS -->
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <!-- Magnific Popup Min JS -->
        <script src="assets/js/jquery.magnific-popup.min.js"></script>
        <!-- Appear Min JS -->
        <script src="assets/js/jquery.appear.min.js"></script>
        <!-- Odometer Min JS -->
        <script src="assets/js/odometer.min.js"></script>
        <!-- Owl Carousel Min JS -->
        <script src="assets/js/owl.carousel.min.js"></script>
        <!-- MeanMenu JS -->
        <script src="assets/js/jquery.meanmenu.js"></script>
        <!-- WOW Min JS -->
        <script src="assets/js/wow.min.js"></script>
        <!-- Message Conversation JS -->
        <script src="assets/js/conversation.js"></script>
        <!-- AjaxChimp Min JS -->
        <script src="assets/js/jquery.ajaxchimp.min.js"></script>
        <!-- Form Validator Min JS -->
        <script src="assets/js/form-validator.min.js"></script>
        <!-- Contact Form Min JS -->
        <script src="assets/js/contact-form-script.js"></script>
        <!-- Particles Min JS -->
        <script src="assets/js/particles.min.js"></script>
        <script src="assets/js/custom-particles.js"></script>
        <!-- Main JS -->
        <script src="assets/js/main.js"></script>
    </body>
</html>
