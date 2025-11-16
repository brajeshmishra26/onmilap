<?php

require_once 'fns/dompdf/autoload.php';
require 'fns/template_engine/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;


$file_name = '';

if (role(['permissions' => ['wallet' => 'download_invoice']])) {

    if (isset($download["wallet_transaction_id"])) {

        $columns = $join = $where = null;
        $columns = [
            "site_users_wallet.user_id", "site_users_wallet.transaction_info", "site_users_wallet.wallet_amount",
            "site_users_wallet.currency_code", "site_users_wallet.transaction_info", "site_users_wallet.transaction_status",
            'payment_gateways.identifier', 'payment_gateways.credentials', 'site_users_wallet.created_on', 'site_users_wallet.wallet_transaction_id',
            "site_users.display_name"
        ];


        $join["[>]payment_gateways"] = ['site_users_wallet.payment_gateway_id' => 'payment_gateway_id'];
        $join["[>]site_users"] = ['site_users_wallet.user_id' => 'user_id'];

        if (!role(['permissions' => ['memberships' => 'view_site_transactions']])) {
            $where["site_users_wallet.user_id"] = Registry::load('current_user')->id;
        }

        $where["site_users_wallet.transaction_status"] = 1;
        $where["site_users_wallet.transaction_type"] = 1;
        $where["site_users_wallet.wallet_transaction_id"] = $download["wallet_transaction_id"];
        $where["LIMIT"] = 1;
        $wallet_transaction = DB::connect()->select('site_users_wallet', $join, $columns, $where);

        if (!empty($wallet_transaction)) {
            if (isset($download['validate'])) {
                $output = array();
                $output['success'] = true;
                $output['download_link'] = Registry::load('config')->site_url.'download/invoice/';
                $output['download_link'] .= 'wallet_transaction_id/'.$download['wallet_transaction_id'].'/';
            } else {
                $wallet_transaction = $wallet_transaction[0];
                $options = new Options();
                $options->set('isRemoteEnabled', true);
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isPhpEnabled', true);
                $dompdf = new Dompdf($options);

                $transaction_info = $wallet_transaction['transaction_info'];

                if (!empty($transaction_info)) {
                    $transaction_info = json_decode($transaction_info);
                }

                $template_variables = array();
                $template_variables['site_url'] = Registry::load('config')->site_url;
                $template_variables['invoice'] = Registry::load('strings')->invoice;
                $template_variables['invoice_from'] = Registry::load('strings')->invoice_from;
                $template_variables['invoice_to'] = Registry::load('strings')->invoice_to;

                $template_variables['invoice_title'] = 'INVOICE_'.$wallet_transaction['wallet_transaction_id'];


                $template_variables['billed_to'] = $wallet_transaction['display_name'];
                $template_variables['client_address'] = '';


                $columns = $join = $where = null;
                $columns = ['billed_to', 'street_address', 'city', 'state', 'country', 'postal_code'];
                $where["billing_address.user_id"] = $wallet_transaction['user_id'];
                $billing_address = DB::connect()->select('billing_address', $columns, $where);

                if (!empty($billing_address)) {
                    $billing_address = $billing_address[0];

                    if (!empty($billing_address['billed_to'])) {

                        $billing_address['country'] = str_replace('-', ' ', $billing_address['country']);
                        $billing_address['country'] = ucwords($billing_address['country']);

                        $template_variables['billed_to'] = $billing_address['billed_to'];
                        $template_variables['client_address'] = nl2br($billing_address['street_address']).'<br>';
                        $template_variables['client_address'] .= $billing_address['city'].', '.$billing_address['state'].'<br>';
                        $template_variables['client_address'] .= $billing_address['country'].'<br>'.$billing_address['postal_code'];
                    }
                }

                $template_variables['billed_from'] = Registry::load('settings')->invoice_from;
                $template_variables['business_address'] = nl2br(Registry::load('settings')->company_address);

                $template_variables['invoice_id_text'] = Registry::load('strings')->invoice_id;
                $template_variables['invoice_id'] = $wallet_transaction['wallet_transaction_id'];

                $template_variables['order_id_text'] = '#';
                $template_variables['description_text'] = Registry::load('strings')->transaction;
                $template_variables['price_text'] = Registry::load('strings')->price;
                $template_variables['date_text'] = Registry::load('strings')->date_text;
                $template_variables['invoice_total'] = Registry::load('strings')->invoice_total;


                $template_variables['order_id'] = 1;
                $template_variables['order_description'] = Registry::load('strings')->credit;

                $template_variables['order_price'] = $wallet_transaction['currency_code'].' '.$wallet_transaction['wallet_amount'];

                $order_date['date'] = $wallet_transaction['created_on'];
                $order_date['auto_format'] = true;
                $order_date['include_time'] = true;
                $order_date['timezone'] = Registry::load('current_user')->time_zone;
                $template_variables['order_date'] = get_date($order_date)['date'];
                $template_variables['payment_method_text'] = $template_variables['payment_method_image'] = '';

                $template_variables['invoice_footer_note'] = Registry::load('settings')->invoice_footer;

                if (isset($wallet_transaction['identifier']) && !empty($wallet_transaction['identifier'])) {
                    $image_url = Registry::load('config')->site_url;
                    $image_url = $image_url.'assets/files/payment_gateways/light/'.$wallet_transaction['identifier'].'.png';

                    $template_variables['payment_method_text'] = Registry::load('strings')->payment_method;
                    $template_variables['payment_method_image'] = '<img src="'.$image_url.'"/>';
                }

                $template = new Latte\Engine;
                $html = $template->renderToString('fns/download/template_invoice.php', $template_variables);

                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $dompdf->stream('membership_invoice.pdf', array('Attachment' => 0));

                exit;
            }
        }
    }
}

if (!isset($output['download_link'])) {
    $output['error'] = Registry::load('strings')->something_went_wrong;
}

?>