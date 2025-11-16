<?php

if (role(['permissions' => ['groups' => 'join_group']])) {

    $todo = 'add';
    $group_id = 0;

    $form['loaded'] = new stdClass();
    $form['fields'] = new stdClass();

    if (isset($load['group_id'])) {
        $load["group_id"] = filter_var($load["group_id"], FILTER_SANITIZE_NUMBER_INT);
        if (!empty($load['group_id'])) {
            $group_id = $load["group_id"];
        }
    }

    if (!empty($group_id)) {

        $columns = $where = null;
        $columns = [
            'groups.group_id', 'groups.name', 'groups.slug',
            'groups.description', 'groups.password', 'groups.paid_group', 'groups.joining_fees'
        ];

        $where["groups.group_id"] = $group_id;
        $where["LIMIT"] = 1;

        $group = DB::connect()->select('groups', $columns, $where);

        if (!isset($group[0]) || isset($group[0]) && empty($group[0]['password']) && empty($group[0]['paid_group'])) {
            return false;
        }

        $group = $group[0];

        $language_id = Registry::load('current_user')->language;
        $group_info = data_cache(['folder' => 'group_trans/'.$group_id, 'filename' => $language_id, 'method' => 'get', 'fs_cache' => true]);

        if (!empty($group_info)) {
            if (isset($group_info['name']) && !empty($group_info['name'])) {
                $group['name'] = $group_info['name'];
            }

            if (isset($group_info['description']) && !empty($group_info['description'])) {
                $group['description'] = $group_info['description'];
            }
        }

        $form['fields']->group_id = [
            "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => $group_id
        ];

        $form['loaded']->title = Registry::load('strings')->join_group;
        $form['loaded']->button = Registry::load('strings')->join;

        $form['fields']->process = [
            "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => "add"
        ];

        $form['fields']->add = [
            "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => "group_members"
        ];

        $form['fields']->group_name = [
            "title" => Registry::load('strings')->group_name, "tag" => 'input', "type" => "disabled",
            "class" => 'field', "value" => $group['name']
        ];
        $form['fields']->group_name['attributes']['disabled'] = 'disabled';

        if (!empty($group['slug'])) {
            $form['fields']->slug = [
                "title" => Registry::load('strings')->slug, "tag" => 'input', "type" => "text", "class" => 'field',
                "value" => $group['slug'],
            ];

            $form['fields']->slug['attributes']['disabled'] = 'disabled';
        }

        if (!empty($group['description'])) {
            $form['fields']->description = [
                "title" => Registry::load('strings')->description, "tag" => 'textarea', "class" => 'field',
                "value" => $group['description']
            ];

            $form['fields']->description['attributes']['disabled'] = 'disabled';
            $form['fields']->description['attributes']['row'] = 6;

        }

        if (!empty($group['paid_group'])) {

            $form['fields']->notice = [
                "title" => Registry::load('strings')->notice, "tag" => 'textarea', "class" => 'field',
                "value" => Registry::load('strings')->paid_group_notice, "attributes" => ["disabled" => "disabled"]
            ];

            $joining_fees = $group['joining_fees'];

            if (Registry::load('settings')->currency_symbol_placement === 'right') {
                $joining_fees = $joining_fees  . ' ' . Registry::load('settings')->default_currency_symbol;
            } else {
                $joining_fees = Registry::load('settings')->default_currency_symbol . ' ' . $joining_fees;
            }

            $form['fields']->joining_fees = [
                "title" => Registry::load('strings')->joining_fees, "tag" => 'input', "type" => 'text', "class" => 'field',
                "value" => $joining_fees, "attributes" => ["disabled" => "disabled"]
            ];
        }


        if (!empty($group['password'])) {
            $form['fields']->password = [
                "title" => Registry::load('strings')->password, "tag" => 'input', "type" => 'password', "class" => 'field group_password',
                "placeholder" => Registry::load('strings')->password,
            ];
        }

    }
}
?>