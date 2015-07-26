<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

use Aglipanci\Interspire\Interspire;
use Aws\Sdk;
use Log;

class ComplaintsController extends Controller
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
    private $complaintsSqsUrl;


    public function __construct(Sdk $sdk, Interspire $interspire)
    {
        $this->sdk = $sdk;
        $this->interspire = $interspire;

        if (is_null(env('AWS_ACCESS_KEY_ID', null)))
            abort(403, 'AWS_ACCESS_KEY_ID is not set in .env file');

        if (is_null(env('AWS_SECRET_ACCESS_KEY', null)))
            abort(403, 'AWS_SECRET_ACCESS_KEY is not set in .env file');

        if (is_null(env('AWS_REGION', null)))
            abort(403, 'AWS_REGION is not set in .env file');

        $this->sqsClient = $this->sdk->createClient('sqs');
        $this->complaintsSqsUrl = env('COMPLAINTS_SQS_URL', null);

        if (is_null($this->complaintsSqsUrl))
            abort(403, 'COMPLAINTS_SQS_URL is not set in .env file');

    }

    /**
     * Process bounce queue messages
     */
    public function process()
    {
        $messages = $this->receiveMessages();

        if (!is_null($messages))
            $this->handleMessages($messages);

        exit;
    }

    /**
     * get SQS messages
     *
     * @return array
     */
    private function receiveMessages()
    {
        $data = $this->sqsClient->receiveMessage([
            'QueueUrl' => $this->complaintsSqsUrl,
            'MaxNumberOfMessages' => 1,
        ]);

        return $data->search('Messages');
    }


    /**
     * @TODO handle loop better
     * @param $messages
     */
    private function handleMessages($messages)
    {
        foreach ($messages as $message) {
            $complaints = json_decode($message['Body']);

            foreach ($complaints->complaint->complainedRecipients as $complaint) {
                $this->cleanRecipient($complaint->emailAddress);
            }

            $this->deleteMessage($message['ReceiptHandle']);
        }

        // kinda dirty loop
        if (!is_null($this->receiveMessages()))
            $this->process();
    }

    /**
     * Delete a SQS message
     *
     * @param $ReceiptHandle
     */
    private function deleteMessage($ReceiptHandle)
    {
        $this->sqsClient->deleteMessage([
            'QueueUrl' => $this->complaintsSqsUrl,
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
        $ban = $this->interspire->addBannedSubscriber($recipient);
        Log::info('COMPLAINT // ' . $recipient . ' : ' . $ban);

        $unsub = $this->interspire->unsubscribeSubscriber($recipient);
        Log::info('UNSUB // ' . $recipient . ' : ' . $unsub);
    }
}
