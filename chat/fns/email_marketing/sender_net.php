<?php

$sender_api_token = Registry::load('settings')->sender_net_access_token;
$sender_groupId = Registry::load('settings')->sender_net_group_id;

$sender_api_token = trim($sender_api_token);

if (empty($sender_api_token) || !preg_match('/^[a-zA-Z0-9-_\.]+$/', $sender_api_token)) {
    $sender_api_token = null;
}

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;

if (isset($data['import'])) {

    if (!empty($sender_api_token) && !empty($sender_groupId)) {
        $curl = curl_init();

        $url = "https://api.sender.net/v2/subscribers";

        $contacts = $data['import'];
        $import_data = array();

        if (isset($contacts['email_address'])) {
            $prev_contacts = $contacts;
            $contacts = array();
            $contacts[] = $prev_contacts;
        }

        try {
            if (!empty($contacts)) {

                foreach ($contacts as $contact) {

                    $import_data = [
                        'email' => $contact['email_address'],
                        "firstname" => $contact['display_name'],
                        "groups" => [$sender_groupId],
                        "phone" => $contact['phone_number'],
                    ];

                    if (!empty($import_data)) {

                        $import_data = json_encode($import_data);

                        curl_setopt_array($curl, [
                            CURLOPT_URL => $url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS => $import_data,
                            CURLOPT_HTTPHEADER => [
                                'Content-Type: application/json',
                                'Accept: application/json',
                                'Authorization: Bearer ' . $sender_api_token
                            ],

                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_SSL_VERIFYHOST => false
                        ]);

                        $response = curl_exec($curl);

                        $response = json_decode($response);

                        if (!empty($response)) {
                            $rmsg = null;
                            if (isset($response->message) && !is_array($response->message)) {
                                $rmsg = trim($response->message);
                                $rmsg = str_replace('.', '', strtolower($rmsg));
                            }
                            if ($rmsg === 'unauthenticated') {
                                $result['error_message'] = 'Please verify your email marketing platform API credentials.';
                                return;
                            }
                        }

                        if (curl_errno($curl)) {
                            throw new Exception('cURL Error: ' . curl_error($curl));
                        }
                    }
                }

                $result = array();
                $result['success'] = true;
            }

        } catch (Exception $e) {
            $result['success'] = false;
        } finally {
            curl_close($curl);
        }
    } else {
        $result['error_message'] = 'Please verify your email marketing platform API credentials.';
    }
} else if (isset($data['remove'])) {
    if (!empty($sender_api_token) && !empty($sender_groupId) && !empty($data['remove'])) {

        try {
            $curl = curl_init();

            if ($curl === false) {
                throw new Exception('Failed to initialize cURL');
            }

            $jsonData = json_encode([
                "subscribers" => $data['remove']
            ]);

            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.sender.net/v2/subscribers',
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer '.$sender_api_token,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_POSTFIELDS => $jsonData,
            ]);

            $response = curl_exec($curl);

            if ($response === false) {
                $error = curl_error($curl);
                curl_close($curl);
                throw new Exception('cURL error: ' . $error);
            }

            curl_close($curl);

            $result = array();
            $result['success'] = true;
        } catch (Exception $e) {
            $result['success'] = false;
        }
    }
}