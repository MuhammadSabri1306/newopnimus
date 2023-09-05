<?php
namespace App\TelegramResponse\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramResponse;
use App\Core\RequestData;
use App\Core\TelegramText;

class Tou extends TelegramResponse
{
    private $chatId;
    private $isPrivateChat;
    private $requestData = [];

    public function __construct($chatId, $isPrivateChat)
    {
        $this->chatId = $chatId;
        $this->isPrivateChat = $isPrivateChat;

        $this->setAnimationRequest();
        $this->setTouTextRequest();
        $this->setBtnApprovalRequest();
    }

    public function setAnimationRequest(callable $callRequest = null)
    {
        $reqData = new RequestData();
        $reqData->chatId = $this->chatId;
        $reqData->parseMode = 'markdown';
        $reqData->replyMarkup = $this->isPrivateChat ? Keyboard::remove(['selective' => true])
            : Keyboard::forceReply(['selective' => true]);
        $reqData->animation = 'https://media1.giphy.com/media/v1.Y2lkPTc5MGI3NjExcXVmeGxnY21sMGQ5ZG94ZDA2emNiZzZodWk0NW9pamRjejNtYmdoZCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/Bf3Anv7HuOPHEPkiOx/giphy.gif';
        $reqData->caption = TelegramText::create()
            ->addText('Hello! Welcome Heroes! selamat datang di ')
            ->startBold()->addText('OPNIMUS')->endBold()
            ->newLine()->newLine()
            ->startCode()
            ->addText('OPNIMUS (Operational Network Infra Monitoring & Surveillance System)')
            ->addText(' adalah sebuah sistem monitoring kondisi kesehatan network')
            ->addText(' element secara realtime dan sistem early warning terhadap potensi gangguan')
            ->addText(' karena permasalahan fisik (catuan, suhu, arus listrik).')
            ->endCode()
            ->get();
        
        $this->requestData['animation'] = is_callable($callRequest) ? $callRequest($reqData) : $reqData;
    }

    public function setTouTextRequest(callable $callRequest = null)
    {
        $reqData = new RequestData();
        $reqData->chatId = $this->chatId;
        $reqData->parseMode = 'markdown';
        $reqData->replyMarkup = $this->isPrivateChat ? Keyboard::remove(['selective' => true])
            : Keyboard::forceReply(['selective' => true]);
        $reqData->text = TelegramText::create()
            ->startBold()->addText('Term of Use | Ketentuan Penggunaan OPNIMUS')->endBold()->newLine(2)
            ->addTabspace()->addText('Pembaca diharuskan memahami beberapa persyaratan berikut yang ditetapkan oleh OPNIMUS. Jika anda tidak menyetujuinya, disarankan untuk tidak menggunakan tools ini.')->newLine(2)
            ->addTabspace()->startItalic()->addText('Konten')->endItalic()->newLine()
            ->addTabspace()->addText('1. Semua materi di dalam ')->startBold()->addText('OPNIMUS')->endBold()->addText(' tidak boleh disalin, diproduksi ulang, dipublikasikan, dan disebarluaskan baik berupa informasi, data, gambar, foto, logo dan lainnya secara utuh maupun sebagian dengan cara apapun dan melalui media apapun kecuali untuk keperluan Operasional.')->newLine()
            ->addTabspace()->addText('2. Aplikasi  ini berikut seluruh materinya tidak boleh dimanfaatkan untuk hal-hal yang bertujuan melanggar hukum atau norma agama dan masyarakat atau hak asasi manusia.')->newLine(2)
            ->addTabspace()->startItalic()->addText('Dalam Penggunaan OPNIMUS, anda setuju untuk:')->endItalic()->newLine()
            ->addTabspace()->addText('1. Memberikan informasi yang akurat, baru dan lengkap tentang diri Anda saat Mendaftar ')->startBold()->addText('OPNIMUS')->endBold()->addText('.')->newLine()
            ->addTabspace()->addText('2. ')->startBold()->addText('OPNIMUS')->endBold()->addText(' tidak bertanggungjawab atas dampak yang ditimbulkan atas penggunaan materi di dalam aplikasi.')->newLine()
            ->addTabspace()->addText('3. Anda tidak diperbolehkan menggunakan ')->startBold()->addText('OPNIMUS')->endBold()->addText(' dalam kondisi atau cara apapun yang dapat merusak, melumpuhkan, membebani dan/atau mengganggu server atau jaringan ')->startBold()->addText('OPNIMUS')->endBold()->addText('. Anda juga tidak diperbolehkan untuk mengakses layanan, akun pengguna, sistem komputer atau jaringan secara tidak sah, dengan cara perentasan (hacking), password mining, dan/atau cara lainnya.')->newLine(2)
            ->addTabspace()->addText('Dengan menggunakan atau mengakses (termasuk berbagai perubahan) ')->startBold()->addText('OPNIMUS')->endBold()->addText(' ini, anda berarti menyetujui syarat-syarat penggunaan ')->startBold()->addText('OPNIMUS')->endBold()->addText('.')
            ->get();
        
        $this->requestData['touText'] = is_callable($callRequest) ? $callRequest($reqData) : $reqData;
    }

    public function setBtnApprovalRequest(callable $callButton = null, callable $callRequest = null)
    {
        $reqData = new RequestData();
        $reqData->chatId = $this->chatId;
        $reqData->parseMode = 'markdown';
        $reqData->text = 'Sebelum mendaftar OPNIMUS apakah anda setuju dengan Ketentuan Penggunaan diatas?';

        $inlineKeyboardData = [
            'agree' => ['text' => '👍 Setuju', 'callback_data' => null],
            'disagree' => ['text' => '❌ Tidak', 'callback_data' => null]
        ];

        if(is_callable($callButton)) {
            $inlineKeyboardData = $callButton($callButton);
        }

        $reqData->replyMarkup = new InlineKeyboard([
            $inlineKeyboardData['agree'],
            $inlineKeyboardData['disagree']
        ]);

        $this->requestData['btnApproval'] = is_callable($callRequest) ? $callRequest($reqData) : $reqData;
    }

    public function send(): ServerResponse
    {
        $response = Request::sendAnimation($this->requestData['animation']->build());
        if(!$response->isOk()) {
            return $response;
        }

        $response = Request::sendMessage($this->requestData['touText']->build());
        if(!$response->isOk()) {
            return $response;
        }

        return Request::sendMessage($this->requestData['btnApproval']->build());
    }
}