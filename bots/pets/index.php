<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

use JetBrains\PhpStorm\ArrayShape;
use Project\Exceptions\TypeErrorException;
use Project\Exceptions\ConnException;
use Project\Exceptions\DbException;
use Project\Controllers\UserController;
use Project\Services\Telegram;
use Project\Models\Users\User;
use Project\Exceptions\AccessModifiersException;

spl_autoload_register(function ($className): void {
    $className = str_replace("\\", DIRECTORY_SEPARATOR, $className);
    require_once __DIR__ . "/../../src/$className.php";
});

$config = (include __DIR__ . "/../../src/config.php")["bots"]["pets"];

$allowExtensionsArray = $config["allowExtensionsArray"];
$cats = $config["cats"];
$token = $config["token"];
$text = "";
$from = null;

$telegram = new Telegram($token);

$defaultKeyboard = [
    "keyboard" => [
        [
            ["text" => "Обо мне"],
            ["text" => "Список команд"]
        ],
        [
            ["text" => "Курага"],
            ["text" => "Ватсон"],
            ["text" => "Василиса"]
        ]
    ],
    "resize_keyboard" => true
];

$inputData = file_get_contents("php://input");

try {
    if ($inputData = json_decode($inputData, true)) {

        $inputData = $inputData["message"] ?? $inputData["callback_query"] ?? $inputData["my_chat_member"];

        $from = (object)$inputData["from"];
        $text = $inputData["text"] ?? $inputData["data"];
        $status = "member";

        if (!empty($inputData["new_chat_member"])) {
            $status = $inputData["new_chat_member"]["status"];
        }

        try {
            (new UserController())->writeUserDataToDB($from, $status);
        } catch (TypeErrorException $e) {
            $e->writeLog();
            $e->showError();
        }

        if (!$text) return;

        $text = trim(mb_strtolower($text));

        switch ($text) {
            case "/start":
                $telegram->sendMessage("Бот активирован", $from->id, json_encode($defaultKeyboard));
                break;
            case "обо мне":
                aboutBot($from->id, $telegram, $defaultKeyboard);
                break;
            case "список команд":
                commandsList($from, $telegram, $defaultKeyboard);
                break;
            case "курага":
            case "ватсон":
            case "василиса":
                $telegram->sendChatAction($from->id, "upload_photo");
                $photoData = getRandomPhoto($cats[$text]["en_nom"], $allowExtensionsArray);
                showCatImage($from->id, $telegram, $photoData);

                if (!in_array($from->id, $config["adminChatIds"])) {
                    foreach ($config["adminChatIds"] as $oneAdminChatId) {

                        $notifyForAdmin = "$from->first_name $from->last_name сейчас любуется {$cats[$text]["ru_ins"]}"
                            . "\nПоказано это замечательное фото 🤩";

                        $telegram->sendMessage($notifyForAdmin, $oneAdminChatId, json_encode($defaultKeyboard));
                        showCatImage($oneAdminChatId, $telegram, $photoData, $defaultKeyboard);
                    }
                }
                break;
            // callback действия
            case "like":
            case "unlike":
                sendReaction($text, $telegram, $inputData["id"]);
                sendReactionToAdmin($text, $from, $telegram, $config, $defaultKeyboard);
                break;
            default:
                $telegram->sendMessage("Используй кнопки с командами", $from->id, json_encode($defaultKeyboard));
                break;
        }
    } else {
        // массовое уведомление
        $filter = [
            "=|notification" => true,
            "=|status" => "member"
        ];

        if ($users = User::findAllByParams($filter)) {
            $dailyPhotoData = getImageForDailyNotification($allowExtensionsArray, $cats);
            foreach ($users as $user) {
                /** @var User $user */
                $dailyNotifyMessage = "Скучаешь, {$user->getFirstName()} {$user->getLastName()}? Вот полюбуйся!";
                $telegram->sendMessage($dailyNotifyMessage, $user->getChatId(), json_encode($defaultKeyboard));
                showCatImage($user->getChatId(), $telegram, $dailyPhotoData);
            }
        }
    }

} catch (ConnException|DbException|AccessModifiersException $e) {
    $e->writeLog();
    $e->showError();
}

/**
 * @param string $chatId
 * @param Telegram $telegram
 * @param array $replyMarkup
 * @return void
 * @throws ConnException
 */
function aboutBot(string $chatId, Telegram $telegram, array $replyMarkup): void
{
    $text = "Любимцы бот:\nЯ - простой бот, который умеет только показывать фотки шикарных котиков 😀";
    $telegram->sendMessage($text, $chatId, json_encode($replyMarkup));
}

/**
 * @param stdClass $from
 * @param Telegram $telegram
 * @param array $replyMarkup
 * @return void
 * @throws ConnException
 */
function commandsList(stdClass $from, Telegram $telegram, array $replyMarkup): void
{
    $text = "Привет, $from->first_name $from->last_name, вот команды, что я понимаю:"
        . "\n<b><i>Обо мне</i></b> - информация обо мне"
        . "\n<b><i>Список команд</i></b> - что я умею"
        . "\n<b><i>Курага</i></b> - показать фото Кураги"
        . "\n<b><i>Ватсон</i></b> - показать фото Ватсона"
        . "\n<b><i>Василиса</i></b> - показать фото Василисы";

    $telegram->sendMessage($text, $from->id, json_encode($replyMarkup));
}

/**
 * @param string $chatId
 * @param Telegram $telegram
 * @param array $photoData
 * @param array|null $replyMarkup
 * @return void
 * @throws ConnException
 */
function showCatImage(string $chatId, Telegram $telegram, array $photoData, null|array $replyMarkup = null): void
{
    $replyMarkup ??= [
        "inline_keyboard" => [
            [
                [
                    "text" => "👍",
                    "callback_data" => "like"
                ],
                [
                    "text" => "👎",
                    "callback_data" => "unlike"
                ]
            ]
        ]
    ];

    $telegram->sendPhoto($photoData, $chatId, json_encode($replyMarkup));
}

/**
 * @param string $catName
 * @param array $allowExtensionsArray
 * @return array
 */
#[ArrayShape(shape: ["caption" => "array|string|string[]", "photo" => "string"])]
function getRandomPhoto(string $catName, array $allowExtensionsArray): array
{
    $files = [];
    foreach (scandir(__DIR__ . "/cats/$catName") as $file) {
        if (in_array(mb_strtolower(pathinfo($file)["extension"]), $allowExtensionsArray)) {
            $files[] = $file;
        }
    }
    $randomPhoto = $files[rand(0, count($files) - 1)];
    $caption = pathinfo($randomPhoto, PATHINFO_FILENAME);
    $photo = __DIR__ . "/cats/$catName/$randomPhoto";
    return [
        "caption" => $caption,
        "photo" => $photo
    ];
}

/**
 * @param string $text
 * @param Telegram $telegram
 * @param string $callbackQueryId
 * @return void
 * @throws ConnException
 */
function sendReaction(string $text, Telegram $telegram, string $callbackQueryId): void
{
    $reactions = [
        "like" => "Вам нравится это фото 😊",
        "unlike" => "Вам не нравится это фото 😢"
    ];

    $telegram->getAnswerCallbackQuery($reactions[$text], $callbackQueryId);
}

/**
 * @param string $text
 * @param stdClass $from
 * @param Telegram $telegram
 * @param array $config
 * @param array $defaultKeyboard
 * @return void
 * @throws ConnException
 */
function sendReactionToAdmin(string $text, stdClass $from, Telegram $telegram, array $config, array $defaultKeyboard): void
{
    $reactions = [
        "like" => "ставит 👍 показаному фото",
        "unlike" => "ставит 👎 показаному фото"
    ];

    if (!in_array($from->id, $config["adminChatIds"])) {
        foreach ($config["adminChatIds"] as $oneAdminChatId) {
            $notifyForAdmin = "$from->first_name $from->last_name $reactions[$text]";
            $telegram->sendMessage($notifyForAdmin, $oneAdminChatId, json_encode($defaultKeyboard));
        }
    }
}

/**
 * @param array $allowExtensionsArray
 * @param array $cats
 * @return array
 */
#[ArrayShape(shape: ["caption" => "\array|string|string[]", "photo" => "string"])]
function getImageForDailyNotification(array $allowExtensionsArray, array $cats): array
{
    $catNamesArray = [];
    foreach ($cats as $catName) {
        $catNamesArray[] = $catName["en_nom"];
    }
    $randomCatName = $catNamesArray[rand(0, count($catNamesArray) - 1)];
    return getRandomPhoto($randomCatName, $allowExtensionsArray);
}