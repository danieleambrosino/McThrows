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


/**
 * User entity.
 *
 * @author Daniele Ambrosino <mail@danieleambrosino.it>
 */
class User implements SplSubject
{

  /**
   * UserDao handle.
   * @var UserDao
   */
  private $dao;

  /**
   * Telegram message.
   * @var array
   */
  private $message;

  /**
   *
   * @var bool
   */
  private $isStored;

  /**
   *
   * @var bool
   */
  private $inLimbo;

  /**
   *
   * @var bool
   */
  private $isInserting;

  /**
   *
   * @var string
   */
  private $username;
  
  /**
   *
   * @var SplObserver
   */
  private $observers;
  
  /**
   *
   * @var array
   */
  private $sendingInfo;

  /**
   * 
   * @param string $update JSON-serialized update.
   */
  public function __construct($update)
  {
    assert(!empty($update), new ErrorException('No update'));
    $update = json_decode($update, TRUE);
    assert(!empty($update), new ErrorException('Decoding failed'));
    if (!isset($update['message']['text']))
    {
      exit;
    }
    $this->message = $update['message'];
    
    if (!defined('DEVELOPMENT'))
    {
      $this->sendingInfo['chatId'] = $this->message['chat']['id'];
      $this->sendingInfo['contentType'] = COMMUNICATOR_CONTENT_TEXT; // di default, invia un messaggio testuale
    }

    $this->dao = new UserDaoSQLite($this->message['from']['id']);
    $this->username = $this->dao->getUsername();
    $this->isStored = $this->dao->isStored();
    $this->inLimbo = $this->dao->inLimbo();
    $this->isInserting = $this->dao->isInserting();
  }
  
  public function attach(SplObserver $observer)
  {
    $this->observers[] = $observer;
  }

  public function detach(SplObserver $observer)
  {
    foreach ($this->observers as $key => $value)
    {
      if ($value === $observer) {
        unset($this->observers[$key]);
      }
    }
  }

  public function notify()
  {
    foreach ($this->observers as $observer)
    {
      $observer->update($this);
    }
  }
  
  public function getSendingInfo()
  {
    return $this->sendingInfo;
  }

  public function run()
  {
    $this->handleText();
  }

  private function handleText()
  {
    if ($this->inLimbo)
    {
      if ($this->message['text'] === '/annulla')
      {
        $ok = $this->dao->removeFromLimbo();
        if (!$ok)
        {
          $text = "Scusa, qualcosa è andato storto \u{2639}";
        }
        else
        {
          $text = 'Ok, registrazione annullata. Clicca su /registrami per iniziare.';
        }
        $this->setAndNotify($text);
        return;
      }
      $this->handleRegistration($this->message['from']['id']);
      return;
    }
    elseif (!$this->isStored)
    {
      if ($this->message['text'] === '/registrami')
      {
        $this->handleRegistration($this->message['from']['id']);
        return;
      }
      elseif ($this->message['text'] === '/start')
      {
        $text = 'Ciao disgraziato, per registrarti clicca su /registrami';
        $this->setAndNotify($text);
        return;
      }
      $text = "Prima ti registri, poi ne parliamo.\n\nTocca /registrami per iniziare.";
      $this->setAndNotify($text);
      return;
    }
    elseif ($this->isInserting)
    {
      if ($this->message['text'] === '/stop')
      {
        $ok = $this->dao->finalyzeInsertions();
        if (!$ok)
        {
          $text = "Scusa, qualcosa è andato storto \u{2639}";
          $this->setAndNotify($text);
          return;
        }
        $text = "Ok, tiri salvati!\n\nConsulta le tue statistiche aggiornate su https://www.danieleambrosino.it/mcthrows";
        $replyMarkup = ['remove_keyboard' => TRUE];
        $this->setAndNotify($text, $replyMarkup);
        return;
      }
      elseif ($this->message['text'] === '/annulla')
      {
        $ok = $this->dao->cancelInsertions();
        if (!$ok)
        {
          $text = "Scusa, qualcosa è andato storto \u{2639}";
          $this->setAndNotify($text);
          return;
        }
        $text = "Ok, tiri cancellati!";
        $replyMarkup = ['remove_keyboard' => TRUE];
        $this->setAndNotify($text, $replyMarkup);
        return;
      }
      $this->handleInsertion();
      return;
    }
    else // per forza di cose, siamo in isStored -> uso un else superfluo per chiarezza
    {
      if ($this->message['text'] === '/start')
      {
        $text = "Bentornato, $this->username!";
        $this->setAndNotify($text);
        return;
      }
      elseif ($this->message['text'] === '/registrami')
      {
        $text = "Oh scemo, sei già registrato \u{1F926}\u{200D}\u{2642}\u{FE0F}";
        $this->setAndNotify($text);
        return;
      }
      elseif ($this->message['text'] === '/inserisci')
      {
        $text = "Bene, ora inserisci i tuoi tiri. Quando hai finito, digita /stop per salvarli, oppure /annulla.";
        $this->setAndNotify($text);
        $this->handleInsertion();
        return;
      }
      elseif ($this->message['text'] === '/annulla')
      {
        $text = 'Nessuna operazione in sospeso';
        $this->setAndNotify($text);
        return;
      }
      elseif ($this->message['text'] === '/elimina')
      {
        $text = "ATTENZIONE: eliminando il tuo account, saranno eliminati definitivamente anche i tuoi tiri."
            . "\n\n"
            . "Se sei sicuro di voler continuare, scrivi 'Sono assolutamente sicuro di voler eliminare il mio account'";
        $this->setAndNotify($text);
        return;
      }
      elseif ($this->message['text'] === 'Sono assolutamente sicuro di voler eliminare il mio account')
      {
        $result = $this->dao->deleteUser();
        if ($result)
        {
          $text = "Ok, ho eliminato il tuo account \u{2639}";
        }
        else
        {
          $text = "Mi dispiace, qualcosa è andato storto. Riprova";
        }
        $this->setAndNotify($text);
        return;
      }
      elseif ($this->message['text'] === '/info')
      {
        $this->printInfos();
        return;
      }
      elseif ($this->message['text'] === '/modifica_nome')
      {
        $text = "Per modificare il tuo nickname, scrivi:\n\n"
            . "Nuovo nome: `NuovoNome`";
        $this->setAndNotify($text, NULL, COMMUNICATOR_PARSE_MARKDOWN);
      }
      elseif (substr($this->message['text'], 0, 12) === 'Nuovo nome: ')
      {
        $oldName = $this->dao->getUsername();
        $name = substr($this->message['text'], 12);
        $patternsAndCallbacks = [
            '/\b\w/' => function ($matches) {
              return strtoupper($matches[0]);
            },
            '/\W/' => function ($matches) {
              return '';
            }
        ];
        $name = preg_replace_callback_array($patternsAndCallbacks, $name);
        $ok = $this->dao->setUsername($name);
        if (!$ok)
        {
          $text = "Scusa, qualcosa è andato storto \u{2639}";
        }
        else
        {
          $text = "Ok, il tuo nickname è passato da \"$oldName\" a \"$name\" \u{1F44D}";
        }
        $this->setAndNotify($text);
      }
    }
  }

  private function getThrowReplyKeyboard(int $die)
  {
    switch ($die):
      case 4:
        $keyboard = [
            [['text' => 1], ['text' => 2]],
            [['text' => 3], ['text' => 4]]
        ];
        break;
      case 6:
        $keyboard = [
            [['text' => 1], ['text' => 2], ['text' => 3]],
            [['text' => 4], ['text' => 5], ['text' => 6]]
        ];
        break;
      case 8:
        $keyboard = [
            [['text' => 1], ['text' => 2], ['text' => 3], ['text' => 4]],
            [['text' => 5], ['text' => 6], ['text' => 7], ['text' => 8]]
        ];
        break;
      case 10:
        $keyboard = [
            [['text' => 1], ['text' => 2], ['text' => 3], ['text' => 4], ['text' => 5]],
            [['text' => 6], ['text' => 7], ['text' => 8], ['text' => 9], ['text' => 10]]
        ];
        break;
      case 12:
        $keyboard = [
            [['text' => 1], ['text' => 2], ['text' => 3], ['text' => 4]],
            [['text' => 5], ['text' => 6], ['text' => 7], ['text' => 8]],
            [['text' => 9], ['text' => 10], ['text' => 11], ['text' => 12]]
        ];
        break;
      case 20:
        $keyboard = [
            [['text' => 1], ['text' => 2], ['text' => 3], ['text' => 4], ['text' => 5]],
            [['text' => 6], ['text' => 7], ['text' => 8], ['text' => 9], ['text' => 10]],
            [['text' => 11], ['text' => 12], ['text' => 13], ['text' => 14], ['text' => 15]],
            [['text' => 16], ['text' => 17], ['text' => 18], ['text' => 19], ['text' => 20]]
        ];
        break;
    endswitch;

    return ['keyboard' => $keyboard];
  }

  private function handleRegistration()
  {
    if ($this->inLimbo)
    {
      if (strtolower($this->message['text']) === 'ok')
      {
        $name = $this->message['from']['first_name'];
      }
      else
      {
        $name = $this->message['text'];
      }
      $patternsAndCallbacks = [
          '/\b\w/' => function ($matches) {
            return strtoupper($matches[0]);
          },
          '/\W/' => function ($matches) {
            return '';
          }
      ];
      $name = preg_replace_callback_array($patternsAndCallbacks, $name);
      $text = "Molto bene, userò $name come nome.";
      $this->setAndNotify($text);
      $result = $this->dao->addUser($name);
      if ($result)
      {
        $this->dao->removeFromLimbo();
        $text = "Ok, registrazione riuscita!\n"
            . "Puoi consultare le tue statistiche all'indirizzo https://www.danieleambrosino.it/mcthrows/ premendo sul bottone col tuo nickname.\n\n"
            . "Se vuoi cambiare il tuo nickname \"$name\", scrivi:\n\n"
            . "Nuovo nome: `nuovo nome`";
      }
      else
      {
        $text = 'Mi dispiace, la registrazione non è andata a buon fine. Riprova -> /registrami';
      }
      $this->setAndNotify($text, NULL, COMMUNICATOR_PARSE_MARKDOWN);
      return;
    }
    $text = "Ok, cominciamo! Il tuo nome su Telegram è {$this->message['from']['first_name']}.\n\nSe vuoi usare questo nome per McThrows, scrivi semplicemente 'Ok'. Altrimenti scrivimi un nickname a tua scelta.\n\nSe vuoi annullare la registrazione, clicca su /annulla";
    $this->setAndNotify($text);
    $this->dao->addToLimbo();
  }

  private function handleInsertion()
  {
    $replyKeyboardMarkup = [
        'keyboard' => [
            [['text' => 'd20']],
            [['text' => 'd4'], ['text' => 'd6'], ['text' => 'd8']],
            [['text' => 'd10'], ['text' => 'd12']]
        ],
        'resize_keyboard' => TRUE,
        'one_time_keyboard' => TRUE
    ];

    $insertionStep = $this->dao->getInsertionStep();
    if ($insertionStep === 0)
    {
      $ok = $this->dao->startInsertion();
      if (!$ok)
      {
        $text = "Scusa, qualcosa è andato storto \u{1F612} Riprova.";
        $this->setAndNotify($text);
        return;
      }
      $text = "Che dado hai tirato?";
      $this->setAndNotify($text, $replyKeyboardMarkup);
      return;
    }
    elseif ($insertionStep == 1) // ovvero: se siamo in attesa del tipo di dado
    {
      if (!preg_match('/^d(4|6|8|10|12|20)$/', trim($this->message['text'])))
      {
        $text = "Non fare il furbetto, inserisci un dado valido";
        $this->setAndNotify($text);
        return;
      }
      $dieType = intval(substr(trim($this->message['text']), 1));
      $ok = $this->dao->insertDie($dieType);
      if (!$ok)
      {
        $test = "Scusa, qualcosa è andato storto \u{2639}";
        $this->setAndNotify($text);
        return;
      }
      $text = "Molto bene. Il tuo tiro?";
      $replyKeyboardMarkup = $this->getThrowReplyKeyboard($dieType);
      $this->setAndNotify($text, $replyKeyboardMarkup);
      return;
    }
    elseif ($insertionStep == 2) // ovvero: se siamo in attesa del tiro
    {
      $dieType = $this->dao->getPendingDieType();
      if (!$dieType)
      {
        $text = "Scusa, qualcosa è andato storto \u{2639}";
        $this->setAndNotify($text);
        return;
      }

      $throwCandidates = [];
      preg_match('/^\d{1,2}/', trim($this->message['text']), $throwCandidates);
      if (empty($throwCandidates))
      {
        $this->setAndNotify('È un concetto bellissimo, però ora inserisci un tiro valido');
        return;
      }
      $chosenThrow = $throwCandidates[0];
      if ($chosenThrow !== trim($this->message['text']))
      {
        $text = "Da bravo mentecatto quale sei, mi hai scritto \"{$this->message['text']}\"; assumo che il tuo tiro sia $chosenThrow";
        $this->setAndNotify($text);
      }
      if ($chosenThrow < 1)
      {
        $this->setAndNotify('Questo tiro non potrebbe farlo neanche Olmo. Forse.');
        return;
      }
      if (trim($this->message['text']) > $dieType)
      {
        $text = "Seh, ti piacerebbe aver fatto $chosenThrow... Non fare il furbo, inserisci un tiro valido";
        $this->setAndNotify($text);
        return;
      }
      $this->dao->insertThrow($chosenThrow);
      $this->dao->startInsertion();

      $text = "Ok, tiro salvato!\n\nInserisci un altro tiro oppure clicca /stop.";
      $this->setAndNotify($text, $replyKeyboardMarkup);
    }
    else
    {
      $text = "Scusa, qualcosa è andato storto \u{2639}";
      $this->setAndNotify($text);
      return;
    }
    return;
  }

  private function printInfos()
  {
    $text = "Queste sono le tue informazioni:\n\n"
        . "Nome utente: $this->username\n"
        . "Tiri totali inseriti: {$this->dao->getThrowsCount()}";
    $this->setAndNotify($text);
  }
  
  
  /**
   * Set sending info and notify observers.
   * 
   * @param string $content
   * @param type $replyMarkup
   * @param type $parseMode
   * @param type $contentType
   * @param type $caption
   * @param type $replyToMessageId
   */
  private function setAndNotify(string $content, $replyMarkup = NULL, $parseMode = NULL, $contentType = NULL, $caption = NULL, $replyToMessageId = NULL)
  {
    $this->sendingInfo['content'] = $content;
    if (!empty($replyMarkup))
    {
      $this->sendingInfo['replyMarkup'] = $replyMarkup;
    }
    if (!empty($parseMode))
    {
      $this->sendingInfo['parseMode'] = $parseMode;
    }
    if (!empty($contentType))
    {
      $this->sendingInfo['contentType'] = $contentType;
    }
    if (!empty($caption))
    {
      $this->sendingInfo['caption'] = $caption;
    }
    if (!empty($replyToMessageId))
    {
      $this->sendingInfo['replyToMessageId'] = $replyToMessageId;
    }
    $this->notify();
  }

}
