<?php
namespace App\Controller\BotV2;

use App\Controller\BotV2\BaseController;
use App\Model\Registration;
use App\Model\TelegramPersonalUser;
use App\Model\Regional;
use App\Model\Witel;

class PicController extends BaseController
{
    public static $conversationKeys;

    public function __construct($command)
    {
        parent::__construct($command);

        static::$conversationKeys = new \stdClass();
        static::$conversationKeys->regist = 'regist_pic';
    }

    public function getPicRegistConversation()
    {
        return $this->getConversation(static::$conversationKeys->regist);
    }

    public function register()
    {
        if(!$this->getMessage()->getChat()->isPrivateChat()) {
            
            $request = $this->request('Pic/TextErrorNotInPrivate');
            $request->setTarget($this->getRequestTarget());
            return $request->send();

        }

        if(!$this->getUser()) {

            $request = $this->request('Error/TextUserUnidentified');
            $request->setTarget($this->getRequestTarget());
            return $request->send();

        }

        $response = $this->isRegistOnReviewed();
        if($response) return $response;

        $conversation = $this->getPicRegistConversation();
        if($conversation->isExists()) {

            $conversationStep = $conversation->getStep();

            if($conversationStep > 0) {
                if($conversation->locations && count($conversation->locations) < 1) {
                    return $this->askLocations();
                }
            }

        }

        return $this->askAgreement();

    }

    public function isRegistOnReviewed()
    {
        $regist = Registration::query(function($db, $table) use ($chatId) {
            $query = "SELECT * FROM $table WHERE request_type='pic' AND status='unprocessed' AND chat_id=%i";
            $data = $db->queryFirstRow($query, $chatId);
            if(isset($data['data'])) $data['data'] = json_decode($data['data'], true);
            return $data ?? null;
        });

        if(!$regist) return null;

        $locations = [];
        if(isset($regist['data']['locations']) && count($regist['data']['locations']) > 0) {
            $locations = RtuLocation::getByIds($regist['data']['locations']);
        }

        if($regist['data']['has_regist']) {

            $request = $this->request('Registration/TextPicUpdateOnReview');
            $request->setTarget($this->getRequestTarget());
            $request->setLocations($locations);
            
            $telgUserId = $regist['data']['telegram_user_id'];
            $telgPersUser = TelegramPersonalUser::findByUserId($telgUserId);
            $request->setTelegramPersonalUser($telgPersUser);

            return $request->send();

        }

        $request = $this->request('Registration/TextPicOnReview');
        $request->setTarget($this->getRequestTarget());
        $request->setRegistration($regist);
        $request->setLocations($locations);

        if(isset($regist['data']['regional_id'])) {
            $regional = Regional::find($regist['data']['regional_id']);
            $request->setRegional($regional);
        }

        if(isset($regist['data']['witel_id'])) {
            $witel = Witel::find($regist['data']['witel_id']);
            $request->setWitel($witel);
        }

        return $request->send();
    }

    public function askAgreement()
    {
        $request = $this->request('Registration/SelectPicTouApproval');
        $request->setTarget($this->getRequestTarget());
        $request->setUser( $this->getUser() );
        $request->setInKeyboard(function($inkeyboardData) {
            $inkeyboardData['agree']['callback_data'] = 'pic.set_start.continue';
            $inkeyboardData['disagree']['callback_data'] = 'pic.set_start.cancel';
            return $inkeyboardData;
        });
        return $request->send();
    }

    public function askLocations()
    {
        $conversation = $this->getPicRegistConversation();
        $locIds = $conversation->locations;

        $request = BotController::getRequest('Registration/PicSetLocation', [ $chatId, $locIds ]);
        $request->setRequest(function($inkeyboardData) {
            $inkeyboardData['next']['callback_data'] = 'pic.update_loc.next';
            $inkeyboardData['add']['callback_data'] = 'pic.update_loc.add';
            $inkeyboardData['remove']['callback_data'] = 'pic.update_loc.remove';
            return $inkeyboardData;
        });
        
        return $request->send();
    }
}