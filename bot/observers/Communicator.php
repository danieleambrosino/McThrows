<?php

/*
 * This file is part of the McThrows package.
 * 
 * (c) 2018 Daniele Ambrosino <mail@danieleambrosino.it>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file distributed with this source code.
 */

require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/*
$sendingInfo = [
    'chatId' => 99881252,
    'contentType' => COMMUNICATOR_CONTENT_TEXT,
    'content' => 'testo',
    'caption' => NULL,
    'parseMode' => NULL,
    'replyToMessageId' => NULL,
    'replyMarkup' => NULL
];*/

/**
 * Class to communicate with Telegram's servers.
 * 
 * @author Daniele Ambrosino <mail@danieleambrosino.it>
 */
class Communicator implements SplObserver
{

  public function update(SplSubject $subject)
  {
    $sendingInfo = $subject->getSendingInfo();
    switch ($sendingInfo['contentType'])
    {
      case COMMUNICATOR_CONTENT_TEXT:
        self::sendMessage($sendingInfo['chatId'], isset($sendingInfo['content']) ? $sendingInfo['content'] : NULL, isset($sendingInfo['parseMode']) ? $sendingInfo['parseMode'] : NULL, isset($sendingInfo['replyToMessageId']) ? $sendingInfo['replyToMessageId'] : NULL, isset($sendingInfo['replyMarkup']) ? $sendingInfo['replyMarkup'] : NULL);
        return;
      case COMMUNICATOR_CONTENT_PHOTO:
        self::sendPhoto($sendingInfo['chatId'], $sendingInfo['content'], $sendingInfo['caption'], $sendingInfo['parseMode'], $sendingInfo['replyToMessageId'], $sendingInfo['replyMarkup']);
        return;
      case COMMUNICATOR_CONTENT_AUDIO:
        self::sendAudio($sendingInfo['chatId'], $sendingInfo['content'], $sendingInfo['caption'], $sendingInfo['parseMode'], $sendingInfo['replyToMessageId'], $sendingInfo['replyMarkup']);
        return;
      case COMMUNICATOR_CONTENT_DOCUMENT:
        self::sendDocument($sendingInfo['chatId'], $sendingInfo['content'], $sendingInfo['caption'], $sendingInfo['parseMode'], $sendingInfo['replyToMessageId'], $sendingInfo['replyMarkup']);
        return;
      case COMMUNICATOR_CONTENT_VIDEO:
        self::sendVideo($sendingInfo['chatId'], $sendingInfo['content'], $sendingInfo['caption'], $sendingInfo['parseMode'], $sendingInfo['replyToMessageId'], $sendingInfo['replyMarkup']);
        return;
      case COMMUNICATOR_CONTENT_VOICE:
        self::sendVoice($sendingInfo['chatId'], $sendingInfo['content'], $sendingInfo['caption'], $sendingInfo['parseMode'], $sendingInfo['replyToMessageId'], $sendingInfo['replyMarkup']);
        return;
    }
  }

  
  /**
   * Get the path of a file stored on Telegram servers.
   * 
   * @param string $file_id Telegram file ID.
   * @return string File path on Telegram servers.
   */
  public static function getFile(string $file_id): string
  {
    $method = 'getFile';
    $parameters = [
        'file_id' => $file_id
    ];
    
    $response = self::makeRequest($method, $parameters);
    assert(isset($response['file_path']));
    return $response['file_path'];
  }
  
  /**
   * Send a text message.
   * 
   * @param int|string $chat_id             Chat ID.
   * @param string     $text                Text to send.
   * @param int        $parse_mode          Markup mode (PARSE_MARKDOWN or PARSE_HTML).
   * @param int        $reply_to_message_id Message ID to which reply.
   * @param array      $reply_markup        Array with custom reply options.
   * 
   * @return array|bool Returns an array with Telegram's response, or FALSE on failure.
   * @throws TypeError
   */
  public static function sendMessage($chat_id, string $text, int $parse_mode = NULL, int $reply_to_message_id = NULL, array $reply_markup = NULL)
  {
    return self::send($chat_id, COMMUNICATOR_CONTENT_TEXT, $text, NULL, $parse_mode, $reply_to_message_id, $reply_markup);
  }

  /**
   * Send a photo.
   * 
   * @param int|string $chat_id             Chat ID.
   * @param string     $photo_id            Photo's Telegram ID to send.
   * @param int        $parse_mode          Markup mode (PARSE_MARKDOWN or PARSE_HTML).
   * @param int        $reply_to_message_id Message ID to which reply.
   * @param array      $reply_markup        Array with custom reply options.
   * 
   * @return array|bool Returns an array with Telegram's response, or FALSE on failure.
   * @throws TypeError
   */
  public static function sendPhoto($chat_id, string $photo_id, string $caption = NULL, int $parse_mode = NULL, int $reply_to_message_id = NULL, array $reply_markup = NULL)
  {
    return self::send($chat_id, $reply_to_message_id, $caption, $photo_id, $parse_mode, $reply_to_message_id, $reply_markup);
  }

  /**
   * Send an audio message.
   * 
   * @param int|string $chat_id             Chat ID.
   * @param string     $audio_id            Text to send.
   * @param int        $parse_mode          Markup mode (PARSE_MARKDOWN or PARSE_HTML).
   * @param int        $reply_to_message_id Message ID to which reply.
   * @param array      $reply_markup        Array with custom reply options.
   * 
   * @return array|bool Returns an array with Telegram's response, or FALSE on failure.
   * @throws TypeError
   */
  public static function sendAudio($chat_id, string $audio_id, string $caption = NULL, int $parse_mode = NULL, int $reply_to_message_id = NULL, array $reply_markup = NULL)
  {
    return self::send($chat_id, $reply_to_message_id, $caption, $audio_id, $parse_mode, $reply_to_message_id, $reply_markup);
  }

  /**
   * Send a document.
   * 
   * @param int|string $chat_id             Chat ID.
   * @param string     $document_id         Document's Telegram ID to send.
   * @param int        $parse_mode          Markup mode (PARSE_MARKDOWN or PARSE_HTML).
   * @param int        $reply_to_message_id Message ID to which reply.
   * @param array      $reply_markup        Array with custom reply options.
   * 
   * @return array|bool Returns an array with Telegram's response, or FALSE on failure.
   * @throws TypeError
   */
  public static function sendDocument($chat_id, string $document_id, string $caption = NULL, int $parse_mode = NULL, int $reply_to_message_id = NULL, array $reply_markup = NULL)
  {
    return self::send($chat_id, $reply_to_message_id, $caption, $document_id, $parse_mode, $reply_to_message_id, $reply_markup);
  }

  /**
   * Send a video message.
   * 
   * @param int|string $chat_id             Chat ID.
   * @param string     $video_id               Video's Telegram ID to send.
   * @param int        $parse_mode          Markup mode (PARSE_MARKDOWN or PARSE_HTML).
   * @param int        $reply_to_message_id Message ID to which reply.
   * @param array      $reply_markup        Array with custom reply options.
   * 
   * @return array|bool Returns an array with Telegram's response, or FALSE on failure.
   * @throws TypeError
   */
  public static function sendVideo($chat_id, string $video_id, string $caption = NULL, int $parse_mode = NULL, int $reply_to_message_id = NULL, array $reply_markup = NULL)
  {
    return self::send($chat_id, $reply_to_message_id, $caption, $video_id, $parse_mode, $reply_to_message_id, $reply_markup);
  }

  /**
   * Send a voice message.
   * 
   * @param int|string $chat_id             Chat ID.
   * @param string     $voice_id            Voice's Telegram ID to send.
   * @param int        $parse_mode          Markup mode (PARSE_MARKDOWN or PARSE_HTML).
   * @param int        $reply_to_message_id Message ID to which reply.
   * @param array      $reply_markup        Array with custom reply options.
   * 
   * @return array|bool Returns an array with Telegram's response, or FALSE on failure.
   * @throws TypeError
   */
  public static function sendVoice($chat_id, string $voice_id, string $caption = NULL, int $parse_mode = NULL, int $reply_to_message_id = NULL, array $reply_markup = NULL)
  {
    return self::send($chat_id, $reply_to_message_id, $caption, $voice_id, $parse_mode, $reply_to_message_id, $reply_markup);
  }

  //private static function send($chat_id, int $message_type, string $content, string $caption, int $parse_mode, int $reply_to_message_id, array $reply_markup)
  private static function send($chat_id, $content_type, $content, $caption, $parse_mode, $reply_to_message_id, $reply_markup)
  {
    assert(is_int($chat_id) || is_string($chat_id), new TypeError(__METHOD__ . ': chat ID must be an integer or a string (' . gettype($chat_id) . ' provided)'));
    assert(!empty($chat_id), __METHOD__ . ': chat_id is empty');
    assert($content_type >= 5 && $content_type <= 10, __METHOD__ . ': invalid content type');

    $parameters['chat_id'] = $chat_id;

    switch ($content_type) {
      case 5:
        $key = 'text';
        $method = 'sendMessage';
        break;
      case 6:
        $key = 'photo';
        $method = 'sendPhoto';
        break;
      case 7:
        $key = 'audio';
        $method = 'sendAudio';
        break;
      case 8:
        $key = 'document';
        $method = 'sendDocument';
        break;
      case 9:
        $key = 'video';
        $method = 'sendVideo';
        break;
      case 10:
        $key = 'voice';
        $method = 'sendVoice';
        break;
    }

    $parameters[$key] = $content;

    if (!empty($caption)) {
      $parameters['caption'] = $caption;
    }

    if (!empty($parse_mode)) {
      if (!in_array($parse_mode, [COMMUNICATOR_PARSE_MARKDOWN, COMMUNICATOR_PARSE_HTML])) {
        throw new TypeError(__METHOD__ . ": parse mode must be PARSE_MARKDOWN or PARSE_HTML ('$parse_mode' provided)");
      }
      if ($parse_mode === COMMUNICATOR_PARSE_MARKDOWN) {
        $parameters['parse_mode'] = 'Markdown';
      } else {
        $parameters['parse_mode'] = 'HTML';
      }
    }

    if (!empty($reply_to_message_id)) {
      $parameters['reply_to_message_id'] = $reply_to_message_id;
    }

    if (!empty($reply_markup)) {
      $parameters['reply_markup'] = $reply_markup;
    }

    return self::makeRequest($method, $parameters);
  }

  /**
   * Make a request to Telegram.
   * 
   * @param string $method     Telegram's method.
   * @param array  $parameters Request's parameters.
   * @param int    $mode       HTTP mode to use (COMMUNICATOR_MODE_GET if not set).
   * 
   * @return array|bool Returns an array with Telegram's response, or FALSE on failure.
   * @throws ErrorException
   */
  private static function makeRequest(string $method, array $parameters = NULL, int $mode = COMMUNICATOR_MODE_GET)
  {
    if (!in_array($mode, [1, 2], TRUE)) {
      throw new ErrorException(__METHOD__ . ': unexpected HTTP mode value');
    }
    
    foreach ($parameters as $key => &$val) {
    // encoding to JSON array parameters, for example reply_markup
      if (!is_numeric($val) && !is_string($val))
      {
        $val = json_encode($val);
      }
    }

    if ($mode === COMMUNICATOR_MODE_GET) {
      $url = API_URL . '/' . $method;
      if ($parameters) {
        $parameters = http_build_query($parameters);
        $url = $url . '?' . $parameters;
      }
    } else {
      $url = API_URL;
      if ($parameters) {
        $parameters = json_encode($parameters);
      }
    }

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);

    if ($mode === COMMUNICATOR_MODE_POST) {
      curl_setopt_array($handle, [
          CURLOPT_POST => TRUE,
          CURLOPT_POSTFIELDS => $parameters,
          CURLOPT_HTTPHEADER => ['ContentType: application/json']
      ]);
    }

    return self::execute($handle);
  }

  /**
   * Execute and handle cURL transfer.
   * 
   * @param resource $handle The cURL handle.
   * 
   * @return array|bool Returns an array with Telegram's response, or FALSE on failure.
   * @throws ErrorException
   */
  private static function execute($handle)
  {
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); // HACK remove me!
    $response = curl_exec($handle);

    if (empty($response)) {
      $errno = curl_errno($handle);
      $error = curl_error($handle);
      Logger::log(__METHOD__ . ": cURL transfer failed with error $errno: $error\n");
      curl_close($handle);
      return FALSE;
    }

    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
    curl_close($handle);

    if ($http_code >= 500) {
      Logger::log(__METHOD__ . ": Telegram servers error\n");
      return FALSE;
    } elseif ($http_code != 200) {
      $response = json_decode($response, TRUE);
      Logger::log(__METHOD__ . ": Telegram request failed with error {$response['error_code']}: {$response['description']}\n");
      if ($http_code === 401) {
        throw new ErrorException(__METHOD__ . ': invalid access token provided');
      }
      return FALSE;
    }

    return json_decode($response, TRUE)['result'];
  }

}
