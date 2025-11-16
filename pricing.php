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
include('assets/php/pages_nav.php');
?>
        <!-- End Navbar Area -->

        <!-- Start Pricing Area -->
        <section class="pricing-area pt-100 pb-70 bg-f4f5fe">
            <div class="container">
                <div class="section-title">
                    <h2>Choose The Pricing Plan</h2>
                </div>

                <div class="tab pricing-list-tab">
                    <ul class="tabs">
                        <li><a href="#">
                            <i class="bx bxs-calendar-check"></i> Monthly
                        </a></li>
                        
                        <li><a href="#">
                            <i class="bx bxs-calendar-check"></i> Yearly
                        </a></li>
                    </ul>

                    <div class="tab_content">
                        <div class="tabs_item">
                            <div class="row">
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="single-pricing-table">
                                        <div class="pricing-header">
                                            <h3>Free</h3>
                                        </div>

                                        <div class="price">
                                            <sup>Rs</sup>0<sub>/m</sub>
                                        </div>

                                        <ul class="pricing-features">
                                            <li><i class="bx bxs-badge-check"></i> Up to 3 chat operators <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> 100 ChatBot Triggers</li>
                                            <li><i class="bx bxs-badge-check"></i> 24/7 Live Chat</li>
                                            <li><i class="bx bxs-badge-check"></i> Email Integration <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Messenger Integration</li>
                                            <li><i class="bx bxs-badge-check"></i> Visitor Info</li>
                                            <li><i class="bx bxs-badge-check"></i> Mobile + Desktop Apps</li>
                                            <li><i class="bx bxs-badge-check"></i> Quick Responses <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Drag & Drop Widgets</li>
                                            <li><i class="bx bxs-badge-check"></i> Visitor Notes <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Google Analytics</li>
                                        </ul>

                                        <div class="btn-box">
                                            <a href="https://onmilap.com/chat/entry" class="default-btn"><i class="bx bxs-hot"></i> Try It Free Now <span></span></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="single-pricing-table">
                                        <div class="pricing-header">
                                            <h3>Starter</h3>
                                        </div>

                                        <div class="price">
                                            <sup>Rs</sup>4900<sub>/m</sub>
                                        </div>

                                        <ul class="pricing-features">
                                            <li><i class="bx bxs-badge-check"></i> Up to 4 chat operators <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> 150 ChatBot Triggers</li>
                                            <li><i class="bx bxs-badge-check"></i> 24/7 Live Chat</li>
                                            <li><i class="bx bxs-badge-check"></i> Email Integration <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Messenger Integration</li>
                                            <li><i class="bx bxs-badge-check"></i> Visitor Info</li>
                                            <li><i class="bx bxs-badge-check"></i> Mobile + Desktop Apps</li>
                                            <li><i class="bx bxs-badge-check"></i> Quick Responses <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Drag & Drop Widgets</li>
                                            <li><i class="bx bxs-badge-check"></i> Visitor Notes <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Google Analytics</li>
                                        </ul>

                                        <div class="btn-box">
                                            <a href="https://onmilap.com/chat/entry" class="default-btn"><i class="bx bxs-hot"></i> Try It Free Now <span></span></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-6 col-sm-6 offset-lg-0 offset-md-3 offset-sm-3">
                                    <div class="single-pricing-table">
                                        <div class="pricing-header">
                                            <h3>Professional</h3>
                                        </div>

                                        <div class="price">
                                            <sup>Rs</sup>7900<sub>/m</sub>
                                        </div>

                                        <ul class="pricing-features">
                                            <li><i class="bx bxs-badge-check"></i> Up to 5 chat operators <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> 200 ChatBot Triggers</li>
                                            <li><i class="bx bxs-badge-check"></i> 24/7 Live Chat</li>
                                            <li><i class="bx bxs-badge-check"></i> Email Integration <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Messenger Integration</li>
                                            <li><i class="bx bxs-badge-check"></i> Visitor Info</li>
                                            <li><i class="bx bxs-badge-check"></i> Mobile + Desktop Apps</li>
                                            <li><i class="bx bxs-badge-check"></i> Quick Responses <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Drag & Drop Widgets</li>
                                            <li><i class="bx bxs-badge-check"></i> Visitor Notes <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Google Analytics</li>
                                        </ul>

                                        <div class="btn-box">
                                            <a href="https://onmilap.com/chat/entry" class="default-btn"><i class="bx bxs-hot"></i> Try It Free Now <span></span></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tabs_item">
                            <div class="row">
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="single-pricing-table">
                                        <div class="pricing-header">
                                            <h3>Free</h3>
                                        </div>

                                        <div class="price">
                                            <sup>Rs</sup>0<sub>/y</sub>
                                        </div>

                                        <ul class="pricing-features">
                                            <li><i class="bx bxs-badge-check"></i> Up to 5 chat operators <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> 150 ChatBot Triggers</li>
                                            <li><i class="bx bxs-badge-check"></i> 24/7 Live Chat</li>
                                            <li><i class="bx bxs-badge-check"></i> Email Integration <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Messenger Integration</li>
                                            <li><i class="bx bxs-badge-check"></i> Visitor Info</li>
                                            <li><i class="bx bxs-badge-check"></i> Mobile + Desktop Apps</li>
                                            <li><i class="bx bxs-badge-check"></i> Quick Responses <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Drag & Drop Widgets</li>
                                            <li><i class="bx bxs-badge-check"></i> Visitor Notes <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Google Analytics</li>
                                        </ul>

                                        <div class="btn-box">
                                            <a href="https://onmilap.com/chat/entry" class="default-btn"><i class="bx bxs-hot"></i> Try It Free Now <span></span></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="single-pricing-table">
                                        <div class="pricing-header">
                                            <h3>Starter</h3>
                                        </div>

                                        <div class="price">
                                            <sup>Rs</sup>7900<sub>/y</sub>
                                        </div>

                                        <ul class="pricing-features">
                                            <li><i class="bx bxs-badge-check"></i> Up to 6 chat operators <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> 200 ChatBot Triggers</li>
                                            <li><i class="bx bxs-badge-check"></i> 24/7 Live Chat</li>
                                            <li><i class="bx bxs-badge-check"></i> Email Integration <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Messenger Integration</li>
                                            <li><i class="bx bxs-badge-check"></i> Visitor Info</li>
                                            <li><i class="bx bxs-badge-check"></i> Mobile + Desktop Apps</li>
                                            <li><i class="bx bxs-badge-check"></i> Quick Responses <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Drag & Drop Widgets</li>
                                            <li><i class="bx bxs-badge-check"></i> Visitor Notes <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Google Analytics</li>
                                        </ul>

                                        <div class="btn-box">
                                            <a href="https://onmilap.com/chat/entry" class="default-btn"><i class="bx bxs-hot"></i> Try It Free Now <span></span></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-6 col-sm-6 offset-lg-0 offset-md-3 offset-sm-3">
                                    <div class="single-pricing-table">
                                        <div class="pricing-header">
                                            <h3>Professional</h3>
                                        </div>

                                        <div class="price">
                                            <sup>Rs</sup>9900<sub>/y</sub>
                                        </div>

                                        <ul class="pricing-features">
                                            <li><i class="bx bxs-badge-check"></i> Up to 7 chat operators <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> 250 ChatBot Triggers</li>
                                            <li><i class="bx bxs-badge-check"></i> 24/7 Live Chat</li>
                                            <li><i class="bx bxs-badge-check"></i> Email Integration <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Messenger Integration</li>
                                            <li><i class="bx bxs-badge-check"></i> Visitor Info</li>
                                            <li><i class="bx bxs-badge-check"></i> Mobile + Desktop Apps</li>
                                            <li><i class="bx bxs-badge-check"></i> Quick Responses <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Drag & Drop Widgets</li>
                                            <li><i class="bx bxs-badge-check"></i> Visitor Notes <span class="tooltips bx bxs-info-circle"  data-bs-toggle="tooltip" data-bs-placement="right" title="Tight pants next level keffiyeh you probably haven't heard of them."></span></li>
                                            <li><i class="bx bxs-badge-check"></i> Google Analytics</li>
                                        </ul>

                                        <div class="btn-box">
                                            <a href="https://onmilap.com/chat/entry" class="default-btn"><i class="bx bxs-hot"></i> Try It Free Now <span></span></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Pricing Area -->
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
