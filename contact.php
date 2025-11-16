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
        <!-- Start Page Title Area -->
        <div class="page-title-area">
            <div class="container">
                <div class="page-title-content">
                    <h2>Contact Us</h2>
                    <p>Drop us Message for any Query</p>
                </div>
            </div>
        </div>
        <!-- End Page Title Area -->
        <!-- Start Contact Area -->
        <section class="contact-area ptb-100">
            <div class="container">
                <div class="contact-inner">
                    <div class="row">
                        <div class="col-lg-6 col-md-12">
                            <div class="contact-features-list">
                                <h3>Some other great reasons to choose us</h3>
                                <p>Take the first steps towards increased productivity and reduced stress with onmilap.</p>
                                <p>We are a highly trained, dedicated team, helping entrepreneurs, professionals, and small teams streamline success - not just in work, but in life too. We help spare your time to focus on the bigger picture by taking care of the little details.</p>
                                <ul>
                                    <li><i class='bx bx-badge-check'></i> Staff works from our offices in USA</li>
                                    <li><i class='bx bx-badge-check'></i> Scale up/ down on demand</li>
                                    <li><i class='bx bx-badge-check'></i> One time or on going projects</li>
                                    <li><i class='bx bx-badge-check'></i> Hire based on skill set</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="contact-form">
                                <h3>Let's Start Your Free Trial</h3>
                                <form id="contactForm">
                                    <div class="row">
                                        <div class="col-lg-6 col-md-6">
                                            <div class="form-group">
                                                <input type="text" name="name" id="name" class="form-control" required data-error="Please enter your name" placeholder="Your Name">
                                                <div class="help-block with-errors"></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-6">
                                            <div class="form-group">
                                                <input type="email" name="email" id="email" class="form-control" required data-error="Please enter your email" placeholder="Your Email">
                                                <div class="help-block with-errors"></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-6">
                                            <div class="form-group">
                                                <input type="text" name="phone_number" id="phone_number" required data-error="Please enter your number" class="form-control" placeholder="Your Phone">
                                                <div class="help-block with-errors"></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-6">
                                            <div class="form-group">
                                                <input type="text" name="msg_subject" id="msg_subject" class="form-control" required data-error="Please enter your subject" placeholder="Your Subject">
                                                <div class="help-block with-errors"></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 col-md-12">
                                            <div class="form-group">
                                                <textarea name="message" class="form-control" id="message" cols="30" rows="6" required data-error="Write your message" placeholder="Your Message"></textarea>
                                                <div class="help-block with-errors"></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 col-md-12">
                                            <button type="submit" class="default-btn"><i class='bx bxs-paper-plane'></i>Send Message<span></span></button>
                                            <div id="msgSubmit" class="h3 text-center hidden"></div>
                                            <div class="clearfix"></div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="contact-info">
                        <div class="contact-info-content">
                            <h3>Contact us by Email Address</h3>
                            <h2>
<!--                                <a href="tel:+0881306298615">+088 130 629 8615</a>
                                <span>OR</span>-->
                                <a href="mailto:hello@onmilap.com">care@onmilap.com</a>
                            </h2>
                            <ul class="social">
                                <li><a href="#" target="_blank"><i class="bx bxl-twitter"></i></a></li>
                                <li><a href="#" target="_blank"><i class="bx bxl-youtube"></i></a></li>
                                <li><a href="#" target="_blank"><i class='bx bxl-facebook'></i></a></li>
                                <li><a href="#" target="_blank"><i class="bx bxl-linkedin"></i></a></li>
                                <li><a href="#" target="_blank"><i class="bx bxl-instagram"></i></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Contact Area -->
        <!-- Start Our Loving Clients Area -->
        <section class="our-loving-clients ptb-100 bg-f4f5fe">
            <div class="container">
                <div class="section-title">
                    <h2>Our Loving Clients</h2>
                </div>
                <div class="clients-logo-list align-items-center">
                    <div class="single-clients-logo">
                        <a href="#">
                            <img src="assets/img/clients-image/1.png" alt="image">
                        </a>
                    </div>
                    <div class="single-clients-logo">
                        <a href="#">
                            <img src="assets/img/clients-image/2.png" alt="image">
                        </a>
                    </div>
                    <div class="single-clients-logo">
                        <a href="#">
                            <img src="assets/img/clients-image/3.png" alt="image">
                        </a>
                    </div>
                    <div class="single-clients-logo">
                        <a href="#">
                            <img src="assets/img/clients-image/4.png" alt="image">
                        </a>
                    </div>
                    <div class="single-clients-logo">
                        <a href="#">
                            <img src="assets/img/clients-image/5.png" alt="image">
                        </a>
                    </div>
                    <div class="single-clients-logo">
                        <a href="#">
                            <img src="assets/img/clients-image/6.png" alt="image">
                        </a>
                    </div>
                    <div class="single-clients-logo">
                        <a href="#">
                            <img src="assets/img/clients-image/7.png" alt="image">
                        </a>
                    </div>
                    <div class="single-clients-logo">
                        <a href="#">
                            <img src="assets/img/clients-image/8.png" alt="image">
                        </a>
                    </div>
                    <div class="single-clients-logo">
                        <a href="#">
                            <img src="assets/img/clients-image/9.png" alt="image">
                        </a>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Our Loving Clients Area -->
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
