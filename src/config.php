<?php
return [
    "bots" => [
        "pets" => [
            "token" => "####",
            "db" => [
                "driver" => "mysql",
                "host" => "localhost",
                "dbname" => "ci96779_petspets",
                "charset" => "utf8",
                "user" => "ci96779_petspets",
                "password" => "####"
            ],
            "adminChatIds" => [
                "549853091"
            ],
            "allowExtensionsArray" => [
                "jpg",
                "jpeg",
                "png"
            ],
            "cats" => [
                "курага" => [
                    "en_nom" => "kuraga",
                    "ru_ins" => "Курагой"
                ],
                "василиса" => [
                    "en_nom" => "vasilisa",
                    "ru_ins" => "Васечкой"
                ],
                "ватсон" => [
                    "en_nom" => "watson",
                    "ru_ins" => "Ватсоном"
                ]
            ]
        ],
        "googleTranslateBot" => [
            "token" => "####",
            "googleApiUrl" => "https://translate.googleapis.com/translate_a/single?"
        ]
    ],
    "telegram" => [
        "url" => "https://api.telegram.org/bot"
    ],
    "log" => [
        "writeLog" => true,
        "logFile" => "log.log",
    ]
];