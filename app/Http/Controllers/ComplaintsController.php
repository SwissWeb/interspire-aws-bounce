<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

use Aglipanci\Interspire\Interspire;
use App\Services\Sqs;
use Log;

class ComplaintsController extends Controller
{

    /**
     * @var Interspire
     */
    private $interspire;

    /**
     * @var Sqs
     */
    private $sqs;

    /**
     * @var string|null
     */
    private $complaintsSqsUrl;


    /**
     * @param Sqs $sqs
     * @param Interspire $interspire
     */
    public function __construct(Sqs $sqs, Interspire $interspire)
    {
        $this->complaintsSqsUrl = env('COMPLAINTS_SQS_URL', null);

        if (is_null($this->complaintsSqsUrl))
            abort(403, 'COMPLAINTS_SQS_URL is not set in .env file');

        $this->sqs = $sqs;
        $this->interspire = $interspire;
    }

    /**
     * Process bounce queue messages
     */
    public function process()
    {
        echo '-> Amazon SQS pulling message(s)' . PHP_EOL;
        $messages = $this->sqs->receiveMessages($this->complaintsSqsUrl);

        if (!is_null($messages)) {
            echo '-> Message(s) received' . PHP_EOL;
            $this->handleMessages($messages);
        }

        return 'Complaints processed. Bybye!';
    }

    /**
     * @TODO handle loop better
     * @param $messages
     */
    private function handleMessages($messages)
    {
        echo '  - Start handling message(s) received' . PHP_EOL;

        foreach ($messages as $message) {
            $complaints = json_decode($message['Body']);

            foreach ($complaints->complaint->complainedRecipients as $recipient) {
                $email = $recipient->emailAddress;
                $this->removeRecipient($email);
                $listids = $this->interspire->getAllListsForEmailAddress($email);

                // if the email is not in any list, we skip
                if (is_null($listids)) {
                    echo '  - ' . $email . ' not subscribed to any list' . PHP_EOL;
                    continue;
                }

                // CAREFUL !!! we want to unsubscribe this email in ALL lists, you might want to change this
                foreach ($listids as $listid) {
                    $this->unsubscribeRecipient($email, $listid);
                }
            }

            $this->sqs->deleteMessage($this->complaintsSqsUrl, $message['ReceiptHandle']);
        }

        // kinda dirty loop ???
        $this->process();
    }

    /**
     * Ban recipient globally
     *
     * @param string $email
     * @param int|string $listid
     */
    private function removeRecipient($email, $listid = 'global')
    {
        $result = $this->interspire->addBannedSubscriber($email, $listid);
        Log::info('COMPLAINT // ' . $email . ' BAN : ' . $result);
        echo '  - ban ' . $email . ' says : ' . $result . PHP_EOL;
    }

    /**
     * Ubsubscribe recipient from ALL mailing lists
     *
     * @param string $email
     * @param int $listid
     */
    private function unsubscribeRecipient($email, $listid = 1)
    {
        $result = $this->interspire->unsubscribeSubscriber($email, $listid);
        Log::info('COMPLAINT // ' . $email . ' UNSUB : ' . $result);
        echo '  - unsubscribe ' . $email . ' says : ' . $result . PHP_EOL;
    }
}
