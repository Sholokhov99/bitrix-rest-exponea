<?php

$aMenu = [
    [
        'parent_menu' => 'global_menu_services',
        'sort' => 150,
        'text' => "Короткие ссылки",
        'title' => "Настройки коротких ссылок",
        'icon' => 'sale_menu_icon_statisti',
        'page_icon' => 'sale_menu_icon_statisti',
        'items_id' => 'rest_exponea_shortlink',
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
    [
        'parent_menu' => 'global_menu_services',
        'sort' => 150,
        'text' => "REST EXPONEA",
        'title' => "Настройки rest exponea",
        'icon' => 'sale_menu_icon_statisti',
        'page_icon' => 'sale_menu_icon_statisti',
        'items_id' => 'rest_exponea',
        'items' => [
            [
                'text' => "Доступ к REST API",
                'title' => "Доступ к REST API",
                'url' => 'rest_exponea_api_security?lang=' . LANGUAGE_ID,
            ],
        ]
    ],
];

return (!empty($aMenu) ? $aMenu : false);