<?php
namespace App\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use GuzzleHttp\Client;

class DiscordWebhookHandler extends AbstractProcessingHandler
{
    protected $webhookUrl;

    public function __construct($webhookUrl, $level = Logger::ERROR, bool $bubble = true) {
        parent::__construct($level, $bubble);
        $this->webhookUrl = env('DISCORD_WEBHOOK_URL');
    }

    protected function write(LogRecord $record): void {
        if (empty($this->webhookUrl)) {
            return;
        }

        $client = new Client();
        $payload = [
            'content' => $record['message'],
            // Customize the message format as needed
        ];

        try {
            $client->post($this->webhookUrl, ['json' => $payload]);
        } catch (\Exception $e) {
            // Handle any exceptions thrown while trying to send the notification
            // It's important to avoid throwing exceptions from here, to prevent recursive error logging
        }
    }
}

?>
