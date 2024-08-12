<?php

class TikTokImporter
{
    private $botToken;
    private $channelId;
    private $tiktokApiUrl;
    private $caption;
    private $includeNickname;

    public function __construct(string $botToken, string $channelId, string $caption, string $includeNickname)
    {
        $this->botToken = $botToken;
        $this->channelId = $channelId;
        $this->tiktokApiUrl = 'https://api.eswhik.com/tiktok/?url=';
        $this->caption = $caption;
        $this->includeNickname = $includeNickname;
    }

    public function importFromUrls(array $tiktokUrls): array
    {
        $results = [];

        foreach ($tiktokUrls as $tiktokUrl) {
            $videoData = $this->getVideoData($tiktokUrl);

            if ($videoData) {
                $responseMessage = $this->sendVideoToTelegram($videoData['videoUrl'], $videoData['nickname']);
                $results[] = [
                    'url' => $tiktokUrl,
                    'message' => $responseMessage,
                    'nickname' => $videoData['nickname']
                ];
            } else {
                $results[] = [
                    'url' => $tiktokUrl,
                    'message' => 'No se pudo obtener el video de TikTok. Por favor, verifica la URL.'
                ];
            }
        }

        return $results;
    }

    private function getVideoData(string $tiktokUrl): ?array
    {
        $apiResponse = @file_get_contents($this->tiktokApiUrl . urlencode($tiktokUrl));
        if ($apiResponse === false) {
            return null; // Manejar errores de API aquí
        }

        $apiData = json_decode($apiResponse, true);

        if (
            isset($apiData['data']['play']) &&
            isset($apiData['data']['author']['nickname'])
        ) {
            return [
                'videoUrl' => $apiData['data']['play'],
                'nickname' => $apiData['data']['author']['nickname']
            ];
        }

        return null;
    }

    private function sendVideoToTelegram(string $videoUrl, string $nickname): string
    {
        $telegramApiUrl = 'https://api.telegram.org/bot' . $this->botToken . '/sendVideo';

        $captionText = $this->includeNickname === 'yes'
            ? $this->caption . ' @' . $nickname
            : $this->caption;

        $postFields = [
            'chat_id' => $this->channelId,
            'video'   => $videoUrl,
            'caption' => $captionText
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $telegramApiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if ($response === false) {
            return 'Error al enviar el video a Telegram: ' . curl_error($ch);
        }

        curl_close($ch);

        return 'Video importado';
    }
}

$botToken = $_POST['bot_token'] ?? '';
$channelId = $_POST['channel_id'] ?? '';
$caption = $_POST['caption'] ?? '';
$includeNickname = $_POST['include_nickname'] ?? '';
$tiktokUrls = isset($_POST['tiktok_urls']) ? json_decode($_POST['tiktok_urls'], true) : [];

$tiktokImporter = new TikTokImporter($botToken, $channelId, $caption, $includeNickname);
$results = $tiktokImporter->importFromUrls($tiktokUrls);

header('Content-Type: application/json');
echo json_encode($results);
