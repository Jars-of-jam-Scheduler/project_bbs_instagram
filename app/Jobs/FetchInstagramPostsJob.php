<?php
namespace App\Jobs;

use App\Connectors\InstagramConnector;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FetchInstagramPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance
     *
     * @param string $username The Instagram account username
     * @return void
     */
    public function __construct(public string $username)
    {
        $this->username = $username;
    }

    /**
     * Execute the job
     *
     * @return void
     */
    public function handle(): void
    {
        $connector = InstagramConnector::getInstance();
        $connector->setClientId(config('services.instagram.client_id'))
            ->setClientSecret(config('services.instagram.client_secret'));

        $accessToken = $connector->getAccessToken();
        $posts = Http::get("https://graph.instagram.com/v11.0/{$this->username}/media", [
            'access_token' => $accessToken,
			'limit' => 10,
        ])->json()['data'];

		Log::info('Posts: ', $posts);
    }
}