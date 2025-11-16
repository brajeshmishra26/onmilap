<?php

if (role(['permissions' => ['bank_transfer_receipts' => 'view']])) {

    $form = array();
    $form['loaded'] = new stdClass();
    $form['fields'] = new stdClass();

    if (isset($load["bank_transfer_receipt_id"])) {

        $load["bank_transfer_receipt_id"] = filter_var($load["bank_transfer_receipt_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($load['bank_transfer_receipt_id'])) {

            $columns = [
                'wallet_bank_receipts.bank_transfer_receipt_id',
                'wallet_bank_receipts.wallet_transaction_id',
                'wallet_bank_receipts.receipt_status',
                'wallet_bank_receipts.updated_on',
                'site_users.display_name',
                'site_users_wallet.currency_code',
                'site_users_wallet.wallet_amount',
                'wallet_bank_receipts.receipt_file_name',
                'wallet_bank_receipts.fund_status',
                'site_users_wallet.wallet_fund_status',
            ];

            $join["[>]site_users_wallet"] = ["wallet_bank_receipts.wallet_transaction_id" => "wallet_transaction_id"];
            $join["[>]site_users"] = ["site_users_wallet.user_id" => "user_id"];

            $where["wallet_bank_receipts.bank_transfer_receipt_id"] = $load["bank_transfer_receipt_id"];
            $where["LIMIT"] = 1;

            $bank_receipt = DB::connect()->select('wallet_bank_receipts', $join, $columns, $where);

            if (isset($bank_receipt[0])) {

                $bank_receipt = $bank_receipt[0];

                $form['loaded']->title = Registry::load('strings')->bank_receipts;

                if (role(['permissions' => ['bank_transfer_receipts' => 'validate']])) {
                    $form['loaded']->button = Registry::load('strings')->update;
                }

                $form['fields']->bank_transfer_receipt_id = [
                    "tag" => 'input',
                    "type" => 'hidden',
                    "class" => 'd-none',
                    "value" => $load["bank_transfer_receipt_id"]
                ];

                $form['fields']->update = [
                    "tag" => 'input',
                    "type" => 'hidden',
                    "class" => 'd-none',
                    "value" => "bank_transfer_receipts"
                ];

                $form['fields']->full_name = [
                    "title" => Registry::load('strings')->full_name,
                    "tag" => 'input',
                    "type" => "text",
                    "class" => 'field',
                    "value" => $bank_receipt['display_name'],
                    "attributes" => ['disabled' => 'disabled']
                ];

                $form['fields']->wallet_transaction_id = [
                    "title" => Registry::load('strings')->transaction_id,
                    "tag" => 'input',
                    "type" => "text",
                    "class" => 'field',
                    "value" => $bank_receipt['wallet_transaction_id'],
                    "attributes" => ['disabled' => 'disabled']
                ];


                $bank_receipt_file = Registry::load('config')->site_url . 'assets/files/wallet_bank_receipts/' . $bank_receipt['receipt_file_name'];

                $form['fields']->bank_receipt = [
                    "title" => Registry::load('strings')->bank_receipt,
                    "tag" => 'link',
                    "type" => 'external_link',
                    "text" => Registry::load('strings')->view_receipt,
                    "link" => $bank_receipt_file,
                    "class" => 'field',
                    "link_target" => "_blank"
                ];

                if (role(['permissions' => ['bank_transfer_receipts' => 'validate']])) {
                    $form['fields']->take_action = [
                        "title" => Registry::load('strings')->take_action,
                        "tag" => 'select',
                        "class" => 'field',
                    ];

                    if ((int)$bank_receipt['fund_status'] === 0 && (int)$bank_receipt['wallet_fund_status'] === 0) {
                        $form['fields']->take_action['options'] = [
                            "approve_topup" => Registry::load('strings')->approve_topup,
                            "approve" => Registry::load('strings')->approve,
                            "disapprove" => Registry::load('strings')->disapprove,
                        ];
                    } else {

                        $form['fields']->take_action['options'] = [
                            "approve" => Registry::load('strings')->approve,
                            "disapprove" => Registry::load('strings')->disapprove,
                        ];
                    }
                }

                $bank_receipt['wallet_amount'] = $bank_receipt['currency_code'] . ' ' . $bank_receipt['wallet_amount'];

                $form['fields']->wallet_amount = [
                    "title" => Registry::load('strings')->amount,
                    "tag" => 'input',
                    "type" => "text",
                    "class" => 'field',
                    "value" => $bank_receipt['wallet_amount'],
                    "attributes" => ['disabled' => 'disabled']
                ];


                $uploaded_on = array();
                $uploaded_on['date'] = $bank_receipt['updated_on'];
                $uploaded_on['auto_format'] = true;
                $uploaded_on['include_time'] = true;
                $uploaded_on['timezone'] = Registry::load('current_user')->time_zone;
                $uploaded_on = get_date($uploaded_on);

                $form['fields']->uploaded_on = [
                    "title" => Registry::load('strings')->uploaded_on,
                    "tag" => 'input',
                    "type" => "text",
                    "class" => 'field',
                    "value" => $uploaded_on['date'] . ' ' . $uploaded_on['time'],
                    "attributes" => ['disabled' => 'disabled']
                ];

            }
        }
    }
}
?>