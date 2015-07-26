<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

use Aglipanci\Interspire\Interspire;
use Aws\Sdk;
use Log;

class BouncesController extends Controller
{

    /**
     * @var Interspire
     */
    private $interspire;

    /**
     * @var Sdk
     */
    private $sdk;

    /**
     * @var SqsClient
     */
    private $sqsClient;

    /**
     * @var string|null
     */
    private $bouncesSqsUrl;



    public function __construct(Sdk $sdk, Interspire $interspire)
    {
        $this->sdk = $sdk;
        $this->interspire = $interspire;

        if(is_null(env('AWS_ACCESS_KEY_ID', null)))
            abort(403, 'AWS_ACCESS_KEY_ID is not set in .env file');

        if(is_null(env('AWS_SECRET_ACCESS_KEY', null)))
            abort(403, 'AWS_SECRET_ACCESS_KEY is not set in .env file');

        if(is_null(env('AWS_REGION', null)))
            abort(403, 'AWS_REGION is not set in .env file');

        $this->sqsClient = $this->sdk->createClient('sqs');
        $this->bouncesSqsUrl = env('BOUNCES_SQS_URL', null);

        if(is_null($this->bouncesSqsUrl))
            abort(403, 'BOUNCES_SQS_URL is not set in .env file');

    }

    /**
     * Process bounce queue messages
     */
    public function process()
    {
        $messages = $this->receiveMessages();

        if(!is_null($messages))
            $this->handleMessages($messages);

        exit;
    }

    /**
     * @TODO handle loop better
     * @param $messages
     */
    private function handleMessages($messages)
    {
        foreach($messages as $message) {
            $bounce = json_decode($message['Body']);

            switch ($bounce->bounce->bounceType)
            {
                // A transient bounce indicates that the recipient's ISP is not accepting messages for that
                // particular recipient at that time and you can retry delivery in the future.
                case "Transient" :
                    $this->manuallyReviewBounce($bounce);
                    break;

                // Remove all recipients that generated a permanent bounce or an unknown bounce.
                default:
                    foreach($bounce->bounce->bouncedRecipients as $recipient)
                    {
                        $this->cleanRecipient($recipient->emailAddress);
                    }
                    break;
            }

            $this->deleteMessage($message['ReceiptHandle']);
        }

        // kinda dirty
        if(!is_null($this->receiveMessages()))
            $this->process();
    }

    /**
     * Log to review manually the bounce
     *
     * @TODO send email notifications or something
     * @param $bounce
     */
    private function manuallyReviewBounce($bounce)
    {
        Log::warning(json_encode($bounce));
    }

    /**
     * get SQS messages
     *
     * @return array
     */
    private function receiveMessages()
    {
        $data = $this->sqsClient->receiveMessage([
            'QueueUrl' => $this->bouncesSqsUrl,
            'MaxNumberOfMessages' => 1,
        ]);
        return $data->search('Messages');
    }

    /**
     * Delete a SQS message
     *
     * @param $ReceiptHandle
     */
    private function deleteMessage($ReceiptHandle)
    {
        $this->sqsClient->deleteMessage([
            'QueueUrl' => $this->bouncesSqsUrl,
            'ReceiptHandle' => $ReceiptHandle,
        ]);
    }

    /**
     * Remove recipient from mailing lists
     *
     * @param $recipient
     */
    private function cleanRecipient($recipient)
    {
        $result = $this->interspire->bounceSubscriber($recipient);
        Log::info('BOUNCE // '.$recipient.' : '.$result);
        echo 'BOUNCE // '.$recipient.' : '.$result;
    }

//    public function getAllListsForEmailAddress($recipient = 'jl.allemann@gmail.com')
//    {
//        $result = $this->interspire->getAllListsForEmailAddress($recipient);
//        dd($result);
//    }
}
