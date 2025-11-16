<?php
include 'fns/firewall/load.php';
include_once 'fns/sql/load.php';
include 'fns/variables/load.php';


$url_parameters = explode('/', get_url(['path' => true]));

if (isset($url_parameters[1])) {
    if ($url_parameters[1] === 'authorize') {
        $wh_directory = "fns/payments/authorizenet/webhooks/";

        if (!is_dir($wh_directory)) {
            mkdir($wh_directory, 0755, true);
        }
        $webhookData = file_get_contents("php://input");
        $signatureHeader = $_SERVER['HTTP_X_ANET_SIGNATURE'] ?? '';

        $webhookData = json_decode($webhookData, true);

        if (!empty($signatureHeader)) {
            if (isset($webhookData['payload']['merchantReferenceId'])) {
                $merchantReferenceId = $webhookData['payload']['merchantReferenceId'];
                $filePath = $wh_directory . $merchantReferenceId . ".hk";

                if (!file_exists($filePath)) {
                    file_put_contents($filePath, json_encode($webhookData, JSON_PRETTY_PRINT));
                }
            }
        }
        exit;
    }
}
if (isset($_GET['razorpay']) && !empty($_GET['razorpay'])) {
    $wallet_transaction_id = filter_var($_GET['razorpay'], FILTER_SANITIZE_NUMBER_INT);
    if (!empty($wallet_transaction_id)) {
        $rp_info = DB::connect()->select('site_users_wallet', ['transaction_info'], ['wallet_transaction_id' => $wallet_transaction_id]);

        if (!isset($rp_info[0])) {
            exit;
        } else {
            $rp_info = $rp_info[0]['transaction_info'];
            $rp_info = json_decode($rp_info, true);
        }
        echo '
<!DOCTYPE html>
<html>
    <head>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <style>
            body, html {
                height: 100%;
                margin: 0;
            }
            .center-container {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100%;
            }
            #rzp-button1 {
                padding: 12px 24px;
                background-color: #3399cc;
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 18px;
                cursor: pointer;
                transition: background-color 0.3s ease;
            }
            #rzp-button1:hover {
                background-color: #287ba0;
            }
        </style>
    </head>
    <body>
        <div class="center-container">
            <button id="rzp-button1">Pay Now</button>
        </div>
        <script>
            var options = {
                "key": "' . $rp_info['rp_api_key'] . '",
                "amount": "' . $rp_info['rp_amount'] . '",
                "currency": "' . $rp_info['rp_currency'] . '",
                "name": "' . $rp_info['rp_name'] . '",
                "description": "' . $rp_info['rp_description'] . '",
                "order_id": "' . $rp_info['rp_order_id'] . '",
                "callback_url": "' . $rp_info['rp_callback'] . '",
                "theme": {
                    "color": "#3399cc"
                }
            };
            var rzp1 = new Razorpay(options);
            document.getElementById("rzp-button1").onclick = function(e) {
                rzp1.open();
                e.preventDefault();
            }
        </script>
    </body>
</html>
';
    }
    exit;
}
if (isset($_GET['embed_url']) && !empty($_GET['embed_url'])) {
    if (Registry::load('current_user')->logged_in) {

        $embed_url = urldecode($_GET['embed_url']);
        $embed_url = htmlspecialchars($embed_url, ENT_QUOTES, 'UTF-8');

        $allowed_hosts = ['paymentwall.com', 'api.paymentwall.com'];

        if (!empty($embed_url)) {

            $parsed_url = parse_url($embed_url);
            $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';

            if (!in_array($host, $allowed_hosts)) {
                $embed_url = null;
            }
        }

        if (!empty($embed_url)) {
            echo '<style> body, html { margin: 0; padding: 0; height: 100%; } iframe { border: none;width: 100%; height: 100%; }</style>';
            echo '<iframe src="' . $embed_url . '" allowfullscreen></iframe>';
            exit;
        }
    }
} else if (isset($_GET['embed_form']) && !empty($_GET['embed_form'])) {
    if (Registry::load('current_user')->logged_in) {

        $form_url = urldecode($_GET['embed_form']);
        $form_url = htmlspecialchars($form_url, ENT_QUOTES, 'UTF-8');
        $wallet_trans_id = null;

        $allowed_hosts = ['accept.authorize.net', 'test.authorize.net'];

        if (!empty($form_url)) {

            $parsed_url = parse_url($form_url);
            $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';

            if (!in_array($host, $allowed_hosts)) {
                $form_url = null;
            }

            if (isset($_GET['wallet_trans_id'])) {
                $wallet_trans_id = $_GET['wallet_trans_id'];
                $wallet_trans_id = filter_var($wallet_trans_id, FILTER_SANITIZE_NUMBER_INT);
            }
        }

        if (!empty($form_url) && !empty($wallet_trans_id)) {

            $token_code = DB::connect()->select('site_users_wallet', ['transaction_info'], ['wallet_transaction_id' => $wallet_trans_id, 'user_id' => Registry::load('current_user')->id]);

            if (isset($token_code[0])) {

                $token_code = $token_code[0]['transaction_info'];
                $token_code = json_decode($token_code, true);

                if (!empty($token_code) && isset($token_code['authorize_token'])) {
                    $token_code = $token_code['authorize_token'];

                    echo '<style> body, html { margin: 0; padding: 0; height: 100%; } iframe { border: none;width: 100%; height: 100%; }</style>';
                    ?>

                        <body
                            style="font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;">

                            <div
                                style="background: white; padding: 20px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1); border-radius: 10px; text-align: center; width: 100%; max-width: 400px;">
                                <div style="font-size: 40px; color: #28a745; margin-bottom: 10px;">
                                    🔒
                                </div>
                                <h2 style="color: #333; margin-bottom: 15px;">Payment</h2>
                                <p style="color: #555; margin-bottom: 20px;">
                                    Click below to proceed to the payment page.
                                </p>

                                <form method="post" action="<?php echo $form_url; ?>">
                                    <input type="hidden" name="token" value="<?php echo $token_code; ?>" />
                                    <input type="submit" value="Continue"
                                        style="background-color: #007bff; color: white; border: none; padding: 10px 15px; font-size: 16px; border-radius: 5px; cursor: pointer; width: 100%; display: block;" />
                                </form>
                            </div>

                        </body>
                        <?php
                        exit;
                }
            }
        }
    }
}

$domain_url_path = urldecode(Registry::load('config')->url_path);
$domain_url_path = parse_url($domain_url_path);
$domain_url_path = $domain_url_path['path'];
$domain_url_path = preg_split('/\//', $domain_url_path);

$wallet_transaction_id = null;

if (isset($domain_url_path[1])) {
    $wallet_transaction_id = filter_var($domain_url_path[1], FILTER_SANITIZE_NUMBER_INT);
}

if (empty($wallet_transaction_id) && isset($_COOKIE['current_wallet_tp_trans']) && !empty($_COOKIE['current_wallet_tp_trans'])) {
    $wallet_transaction_id = filter_var($_COOKIE['current_wallet_tp_trans'], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($wallet_transaction_id)) {
        $validation_url = Registry::load('config')->site_url . 'topup_wallet/' . $wallet_transaction_id . '/';
        redirect($validation_url);
    }
}

if (isset($_POST['error']) && isset($_POST['error']['code']) || isset($_POST['razorpay_payment_id']) || isset($_POST['token']) || isset($_POST['mer_txnid'])) {
    $validation_url = Registry::load('config')->site_url . 'topup_wallet/' . $wallet_transaction_id . '/';
    ?>
    <script>
        window.setTimeout(function () {
            window.location.href = "<?php echo $validation_url ?>";
        }, 500);
    </script>
    <?php
    exit;
}

if (Registry::load('current_user')->logged_in) {

    if (!empty($wallet_transaction_id)) {

        add_cookie('current_wallet_tp_trans', 0);

        $columns = $join = $where = null;
        $columns = [
            "site_users_wallet.user_id",
            "site_users_wallet.transaction_info",
            "site_users_wallet.wallet_amount",
            "site_users_wallet.payment_gateway_id",
            "site_users_wallet.transaction_info",
            "site_users_wallet.transaction_status",
            'payment_gateways.identifier',
            'payment_gateways.credentials'
        ];
        $join["[>]payment_gateways"] = ['site_users_wallet.payment_gateway_id' => 'payment_gateway_id'];
        $where = [
            "site_users_wallet.wallet_transaction_id" => $wallet_transaction_id,
            "site_users_wallet.transaction_type" => 1,
            "site_users_wallet.transaction_status" => 0,
            "site_users_wallet.payment_gateway_id[!]" => null,
            "site_users_wallet.user_id" => Registry::load('current_user')->id
        ];

        $wallet_transaction = DB::connect()->select('site_users_wallet', $join, $columns, $where);

        if (isset($wallet_transaction[0])) {
            $wallet_transaction = $wallet_transaction[0];
        }

        if (isset($wallet_transaction['wallet_amount']) && isset($wallet_transaction['identifier']) && !empty($wallet_transaction['identifier'])) {

            $validation_url = Registry::load('config')->site_url . 'topup_wallet/' . $wallet_transaction_id . '/';

            if ($wallet_transaction['identifier'] === 'bank_transfer') {
                $bank_transfer_url = Registry::load('config')->site_url . 'bank_transfer/' . $wallet_transaction_id . '/';
                redirect($bank_transfer_url);
            }

            if ((int) $wallet_transaction['transaction_status'] === 0) {

                include_once 'fns/payments/load.php';

                $transaction_info = array();

                $validate_data = [
                    'validate_purchase' => $wallet_transaction_id,
                    'gateway' => $wallet_transaction['identifier'],
                    'credentials' => $wallet_transaction['credentials']
                ];

                if (!empty($wallet_transaction['transaction_info'])) {
                    $payment_session_data = json_decode($wallet_transaction['transaction_info'], true);


                    if (!empty($payment_session_data)) {

                        $transaction_info = $payment_session_data;

                        $validate_data['transaction_info'] = $wallet_transaction['transaction_info'];

                        if (isset($payment_session_data['payment_session_id'])) {
                            $validate_data['payment_session_id'] = $payment_session_data['payment_session_id'];
                        } else if (isset($payment_session_data['payment_session_data'])) {
                            $validate_data['payment_session_data'] = $payment_session_data['payment_session_data'];
                        }

                        if (isset($payment_session_data['NP_id'])) {
                            $validate_data['NP_id'] = $payment_session_data['NP_id'];
                        }
                    }
                }

                $payment_status = payment_module($validate_data);

                if (isset($payment_status['transaction_info'])) {
                    $transaction_info = array_merge($transaction_info, $payment_status['transaction_info']);

                    $transaction_info = json_encode($transaction_info);
                }


                if (isset($payment_status['success']) && $payment_status['success']) {

                    include_once 'fns/wallet/load.php';

                    $wallet_data = [
                        'credit' => $wallet_transaction['wallet_amount'],
                        'user_id' => Registry::load('current_user')->id
                    ];
                    UserWallet($wallet_data);

                    DB::connect()->update(
                        'site_users_wallet',
                        [
                            'transaction_status' => 1,
                            'transaction_info' => $transaction_info,
                            'wallet_fund_status' => 1,
                            "updated_on" => Registry::load('current_user')->time_stamp
                        ],
                        ['wallet_transaction_id' => $wallet_transaction_id]
                    );

                    $layout_variable = array();
                    $layout_variable['title'] = $layout_variable['status'] = Registry::load('strings')->success;
                    $layout_variable['description'] = Registry::load('strings')->transaction_successful_message;
                    $layout_variable['button'] = Registry::load('strings')->continue_text;
                    $layout_variable['successful'] = true;
                    include_once 'layouts/transaction_status/layout.php';
                    exit;

                } else {
                    DB::connect()->update('site_users_wallet', ['transaction_status' => 2, 'transaction_info' => $transaction_info], ['wallet_transaction_id' => $wallet_transaction_id]);

                    $layout_variable = array();
                    $layout_variable['title'] = $layout_variable['status'] = Registry::load('strings')->failed;
                    $layout_variable['description'] = Registry::load('strings')->transaction_failed_message;
                    $layout_variable['button'] = Registry::load('strings')->continue_text;
                    $layout_variable['successful'] = false;

                    include_once 'layouts/transaction_status/layout.php';
                    exit;
                }


            } else {

                $layout_variable = array();
                $layout_variable['title'] = $layout_variable['status'] = Registry::load('strings')->failed;
                $layout_variable['description'] = Registry::load('strings')->transaction_failed_message;
                $layout_variable['button'] = Registry::load('strings')->continue_text;
                $layout_variable['successful'] = false;

                include_once 'layouts/transaction_status/layout.php';
                exit;
            }

        } else {
            $wallet_transaction_id = null;
        }

    }

    if (empty($wallet_transaction_id)) {
        $layout_variable = array();
        $layout_variable['title'] = $layout_variable['status'] = Registry::load('strings')->failed;
        $layout_variable['description'] = Registry::load('strings')->invalid_transaction;
        $layout_variable['button'] = Registry::load('strings')->continue_text;
        $layout_variable['successful'] = false;

        include_once 'layouts/transaction_status/layout.php';
        exit;
    }
} else {
    redirect('404');
}

?>