<?php
namespace App\TelegramRequest\Pic;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Libraries\TelegramText\MarkdownText;
use App\Core\TelegramRequest\TextList;
use App\Core\TelegramRequest\PortFormat;

class TextListInWitel extends TelegramRequest
{
    use TextList, PortFormat;

    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $witel = $this->getData('witel', null);
        $locs = $this->getData('locs', null);
        $currDate = date('Y-m-d H:i');

        if(!$witel || !$locs) return MarkdownText::create();
        $text = MarkdownText::create()
            ->addBold('List PIC '. $witel['witel_name'])->newLine(2)
            ->addItalic('posisi '.$currDate)->newLine();
        
        $maxLineChars = 66;
        for($i=0; $i<count($locs); $i++) {
            $locText = strval($i + 1).'. '.$locs[$i]['sname'].' - ';
            $text->newLine()->addText($locText);

            if(count($locs[$i]['pics']) < 1) {

                $text->addBold('KOSONG');

            } else {
                foreach($locs[$i]['pics'] as $picIndex => $pic) {

                    if($picIndex > 0) $text->addText(', ');

                    $picName = $pic['full_name'] ?? implode(' ', array_filter([ $pic['first_name'], $pic['last_name'] ]));
                    // if()
                    $text->addMention($pic['user_id'], $picName);

                }
            }
        }

        return $text;
    }

    public function setWitel($witel)
    {
        if(is_array($witel)) {
            $this->setData('witel', $witel);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setPics($pics)
    {
        if(is_array($pics)) {

            $locs = array_reduce($pics, function($result, $item) {

                $locId = $item['location_id'];
                if(!array_key_exists($locId, $result)) {
                    $result[$locId] = [
                        'id' => $locId,
                        'name' => $item['location_name'],
                        'sname' => $item['location_sname'],
                        'pics' => []
                    ];
                }

                if($item['user_id']) {
                    array_push($result[$locId]['pics'], [
                        'user_id' => $item['user_id'],
                        'username' => $item['username'],
                        'first_name' => $item['first_name'],
                        'last_name' => $item['last_name'],
                        'full_name' => $item['full_name'],
                    ]);
                }

                return $result;

            }, []);

            $this->setData('locs', array_values($locs));
            $this->params->text = $this->getText()->get();
        }
    }

    public function send(): ServerResponse
    {
        if(!$this->getText()->isLengthExceeded()) {
            return Request::sendMessage($this->params->build());
        }
        return $this->sendList($this->getText()->split());
    }
}