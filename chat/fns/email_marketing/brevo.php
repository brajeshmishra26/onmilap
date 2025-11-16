<?php

$brevo_apiKey = Registry::load('settings')->brevo_api_key;
$brevo_listIds = Registry::load('settings')->brevo_list_id;

$brevo_apiKey = trim($brevo_apiKey);

if (empty($brevo_apiKey) || !preg_match('/^[a-zA-Z0-9-_\.]+$/', $brevo_apiKey)) {
    $brevo_apiKey = null;
}

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;


if (isset($data['import'])) {

    if (!empty($brevo_apiKey) && !empty($brevo_listIds)) {
        $curl = curl_init();

        $url = 'https://api.brevo.com/v3/contacts/import';

        $contacts = $data['import'];
        $import_data = array();
        $brevo_listIds = (int)$brevo_listIds;

        if (isset($contacts['email_address'])) {
            $prev_contacts = $contacts;
            $contacts = array();
            $contacts[] = $prev_contacts;
        }

        foreach ($contacts as $contact) {
            $import_data[] = [
                'email' => $contact['email_address'],
                'attributes' => [
                    'FIRSTNAME' => $contact['display_name'],
                    'SMS' => $contact['phone_number'],
                    'EXT_ID' => $contact['user_id'],
                ]
            ];
        }

        if (!empty($import_data)) {

            $import_data = json_encode([
                'jsonBody' => $import_data,
                'listIds' => [$brevo_listIds],
                'emailBlacklist' => false,
                'smsBlacklist' => false,
                'updateExistingContacts' => true,
                'emptyContactsAttributes' => true
            ]);
        }
        try {
            if (!empty($import_data)) {
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
                        'api-key: ' . $brevo_apiKey
                    ],
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false
                ]);

                $response = curl_exec($curl);

                if (curl_errno($curl)) {
                    throw new Exception('cURL Error: ' . curl_error($curl));
                }

                $response = json_decode($response);

                if (!empty($response)) {
                    if (isset($response->code) && $response->code === 'unauthorized') {
                        $result['error_message'] = 'Please verify your email marketing platform API credentials.';
                    } else {
                        $result = array();
                        $result['success'] = true;
                    }
                }
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
    if (!empty($brevo_apiKey) && !empty($brevo_listIds) && !empty($data['remove'])) {

        try {
            foreach ($data['remove'] as $email) {
                $curl = curl_init();

                if ($curl === false) {
                    throw new Exception('Failed to initialize cURL');
                }

                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://api.brevo.com/v3/contacts/' . urlencode($email),
                    CURLOPT_CUSTOMREQUEST => 'DELETE',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'api-key: '.$brevo_apiKey,
                        'Accept: application/json',
                    ],
                ]);

                $response = curl_exec($curl);

                if ($response === false) {
                    $error = curl_error($curl);
                    curl_close($curl);
                    throw new Exception('cURL error: ' . $error);
                }

                curl_close($curl);
            }

            $result = array();
            $result['success'] = true;

        } catch (Exception $e) {

            $result['success'] = false;
        }

    }
}