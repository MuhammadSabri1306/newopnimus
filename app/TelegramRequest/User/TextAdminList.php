<?php
namespace App\TelegramRequest\User;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Core\TelegramRequest\TextList;
use App\Core\TelegramRequest\PortFormat;
use App\Helper\ArrayHelper;

class TextAdminList extends TelegramRequest
{
    use TextList, PortFormat;

    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getLocalAdminsTreeText($tree, $leftSpace = 0)
    {
        if(count($tree) < 1) return '';
        $text = TelegramText::create();
        foreach($tree as $item) {

            $text->newLine(2)->addSpace($leftSpace);
            
            if($item['level'] == 'regional') {
                $text->addText('🏤 ')->addBold($item['regional_name']);
            } elseif($item['level'] == 'witel') {
                $text->addText('🏬 ')->addBold($item['witel_name']);
            }

            foreach($item['admins'] as $admin) {
                $adminName = implode(' ', array_filter([ $admin['first_name'], $admin['last_name'] ]));
                $text->newLine()
                    ->addSpace($leftSpace)
                    ->addText(' - ')
                    ->addMentionByName($admin['chat_id'], $adminName);
            }

            if(isset($item['childs'])) {
                $text->addText($this->getLocalAdminsTreeText($item['childs'], $leftSpace + 3));
            }

        }
        return $text->get();
    }

    public function getText()
    {
        $nasionalAdmins = $this->getData('nasional_admins', []);
        $localAdmins = $this->getData('local_admins', []);

        $text = TelegramText::create()
            ->addBold('List Admin terdaftar')->newLine(2)
            ->addText('🏢 ')->addBold('NASIONAL');
        if(count($nasionalAdmins) < 1) {
            $text->newLine()->addItalic('- Belum ada admin Nasional');
        } else {
            foreach($nasionalAdmins as $admin) {
                $adminName = implode(' ', array_filter([ $admin['first_name'], $admin['last_name'] ]));
                $text->newLine()
                    ->addText(' - ')
                    ->addMentionByName($admin['chat_id'], $adminName);
            }
        }

        if(count($localAdmins) > 0) {
            $text->addText($this->getLocalAdminsTreeText($localAdmins));
        }

        return $text;
    }

    public function setAdmins($admins)
    {
        if(is_array($admins)) {
            
            $nasionalAdmins = [];
            $localAdmins = [];
            foreach($admins as $admin) {
                if($admin['level'] != 'nasional') {

                    $regionalId = $admin['regional_id'];
                    $rIndex = ArrayHelper::findIndex($localAdmins, fn($item) => $item['regional_id'] == $regionalId);
                    if($rIndex < 0) {
                        $rIndex = count($localAdmins);
                        array_push($localAdmins, [
                            'level' => 'regional',
                            'regional_id' => $regionalId,
                            'regional_name' => $admin['regional_name'],
                            'admins' => [],
                            'childs' => []
                        ]);
                    }

                    if($admin['level'] == 'witel') {

                        $witelId = $admin['witel_id'];
                        $wIndex = ArrayHelper::findIndex($localAdmins[$rIndex]['childs'], fn($item) => $item['witel_id'] == $witelId);
                        if($wIndex < 0) {
                            array_push($localAdmins[$rIndex]['childs'], [
                                'level' => 'witel',
                                'witel_id' => $witelId,
                                'witel_name' => $admin['witel_name'],
                                'admins' => [ $admin ]
                            ]);
                        } else {
                            array_push($localAdmins[$rIndex]['childs'][$wIndex]['admins'], $admin);
                        }

                    } elseif($admin['level'] == 'regional') {

                        array_push($localAdmins[$rIndex]['admins'], $admin);

                    }

                } else {
                    array_push($nasionalAdmins, $admin);
                }
            }

            $this->setData('nasional_admins', $nasionalAdmins);
            $this->setData('local_admins', $localAdmins);
            $this->params->text = $this->getText()->get();

        }
    }

    public function send(): ServerResponse
    {
        $text = $this->params->text;
        $messageTextList = $this->splitText($text, 50);

        if(count($messageTextList) < 2) {
            return Request::sendMessage($this->params->build());
        }
        return $this->sendList($messageTextList);
    }
}