<?php

$aMenu = [
    [
        'parent_menu' => 'global_menu_rest_exponea',
        'sort' => 150,
        'text' => "Короткие ссылки",
        'title' => "YOURMODULE_MENUTITLE",
        'icon' => 'sale_menu_icon_statisti',
        'page_icon' => 'sale_menu_icon_statisti',
        'items_id' => 'some_id',
        'items' => [
            [
                'text' => "Источник короткис ссылок по продукту",
                'title' => "Источник короткис ссылок по продукту",
                'url' => 'rest_exponea_shortlink_iblock_edit.php?lang=' . LANGUAGE_ID,
            ],
            [
                'text' => "Токены",
                'title' => "Токены",
                'url' => 'rest_exponea_token_edit.php?lang=' . LANGUAGE_ID,
            ],
        ]
    ],
];

return (!empty($aMenu) ? $aMenu : false);