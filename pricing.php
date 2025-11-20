<?php
if (!function_exists('pricing_base_url')) {
function pricing_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($dir === '/' || $dir === '.') {
        $dir = '';
    }
    $dir = trim($dir, '/');
    return rtrim($scheme.'://'.$host.(!empty($dir) ? '/'.$dir : ''), '/').'/';
}
}

$bootstrapPath = __DIR__.'/api/bootstrap.php';
$pricingOriginalCwd = getcwd();
$pricingChatDir = __DIR__.'/chat';

if (is_file($bootstrapPath)) {
    $pricingTemporarilyChangedCwd = false;

    if (is_dir($pricingChatDir) && !defined('APP_BOOTSTRAPPED')) {
        chdir($pricingChatDir);
        $pricingTemporarilyChangedCwd = true;
    }

    require_once $bootstrapPath;

    if ($pricingTemporarilyChangedCwd) {
        chdir($pricingOriginalCwd);
    }

    if (
        class_exists('Registry')
        && method_exists('Registry', 'stored')
        && Registry::stored('config')
    ) {
        $pricingConfig = Registry::load('config');
        if (!isset($pricingConfig->current_page) || empty($pricingConfig->current_page)) {
            $pricingScript = $_SERVER['SCRIPT_NAME'] ?? 'pricing.php';
            $pricingConfig->current_page = trim(str_replace('.php', '', basename($pricingScript)));
        }

        $pricingConfig->site_url = pricing_base_url();
    }
}

$isPricingUserLoggedIn = false;
if (
    class_exists('Registry')
    && method_exists('Registry', 'load')
    && method_exists('Registry', 'stored')
    && Registry::stored('current_user')
) {
    $pricingCurrentUser = Registry::load('current_user');
    if (isset($pricingCurrentUser->logged_in)) {
        $isPricingUserLoggedIn = (bool)$pricingCurrentUser->logged_in;
    }
}

$chatEntryUrl = pricing_base_url().'chat/entry';

if (!function_exists('pricing_plan_href')) {
    function pricing_plan_href(string $planName, bool $isLoggedIn, string $chatEntryUrl): string
    {
        $subscriptionUrl = 'subscription.php?plan='.urlencode($planName);
        if ($isLoggedIn) {
            return $subscriptionUrl;
        }

        return $chatEntryUrl.'?redirect='.urlencode($subscriptionUrl);
    }
}

$pricingPlanLinks = [
    'Free' => pricing_plan_href('Free', $isPricingUserLoggedIn, $chatEntryUrl),
    'Starter' => pricing_plan_href('Starter', $isPricingUserLoggedIn, $chatEntryUrl),
    'Professional' => pricing_plan_href('Professional', $isPricingUserLoggedIn, $chatEntryUrl),
    'Creator' => pricing_plan_href('Creator', $isPricingUserLoggedIn, $chatEntryUrl),
];
?>
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
                    <p>Click “Get Plan” to continue. You will be prompted to log in before the checkout opens inside the chat workspace.</p>
                </div>

                <div class="tab pricing-list-tab">
                    <ul class="tabs">
                        <li><a href="#">
                            <i class="bx bxs-calendar-check"></i> INR
                        </a></li>
                        
                        <li><a href="#">
                            <i class="bx bxs-calendar-check"></i> $-USD
                        </a></li>
                    </ul>

                    <div class="tab_content">
                        <div class="tabs_item">
                            <div class="row">
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="single-pricing-table">
                                        <div class="pricing-header">
                                            <h3>Starter</h3>
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
                                            <a href="<?php echo htmlspecialchars($pricingPlanLinks['Starter'], ENT_QUOTES, 'UTF-8'); ?>" class="default-btn plan-cta" data-plan="Starter"><i class="bx bxs-hot"></i> Get Plan <span></span></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="single-pricing-table">
                                        <div class="pricing-header">
                                            <h3>Professional</h3>
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
                                            <a href="<?php echo htmlspecialchars($pricingPlanLinks['Professional'], ENT_QUOTES, 'UTF-8'); ?>" class="default-btn plan-cta" data-plan="Professional"><i class="bx bxs-hot"></i> Get Plan <span></span></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-6 col-sm-6 offset-lg-0 offset-md-3 offset-sm-3">
                                    <div class="single-pricing-table">
                                        <div class="pricing-header">
                                            <h3>Creator</h3>
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
                                            <a href="<?php echo htmlspecialchars($pricingPlanLinks['Creator'], ENT_QUOTES, 'UTF-8'); ?>" class="default-btn plan-cta" data-plan="Creator"><i class="bx bxs-hot"></i> Get Plan <span></span></a>
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
                                            <a href="<?php echo htmlspecialchars($pricingPlanLinks['Free'], ENT_QUOTES, 'UTF-8'); ?>" class="default-btn plan-cta" data-plan="Free"><i class="bx bxs-hot"></i> Get Plan <span></span></a>
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
                                            <a href="<?php echo htmlspecialchars($pricingPlanLinks['Starter'], ENT_QUOTES, 'UTF-8'); ?>" class="default-btn plan-cta" data-plan="Starter"><i class="bx bxs-hot"></i> Get Plan <span></span></a>
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
                                            <a href="<?php echo htmlspecialchars($pricingPlanLinks['Professional'], ENT_QUOTES, 'UTF-8'); ?>" class="default-btn plan-cta" data-plan="Professional"><i class="bx bxs-hot"></i> Get Plan <span></span></a>
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
