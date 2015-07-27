<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

use Aglipanci\Interspire\Interspire;
use App\Services\Sqs;
use Log;

class BouncesController extends Controller
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
    private $bouncesSqsUrl;

    /**
     * @param Sqs $sqs
     * @param Interspire $interspire
     */
    public function __construct(Sqs $sqs, Interspire $interspire)
    {
        $this->bouncesSqsUrl = env('BOUNCES_SQS_URL', null);

        if (is_null($this->bouncesSqsUrl))
            abort(403, 'BOUNCES_SQS_URL is not set in .env file');

        $this->interspire = $interspire;
        $this->sqs = $sqs;
    }

    /**
     * Process bounce queue messages
     */
    public function process()
    {
        echo '-> Amazon SQS pulling message(s)' . PHP_EOL;
        $messages = $this->sqs->receiveMessages($this->bouncesSqsUrl);

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
            $bounce = json_decode($message['Body']);

            switch ($bounce->bounce->bounceType) {
                // A transient bounce indicates that the recipient's ISP is not accepting messages for that
                // particular recipient at that time and you can retry delivery in the future.
                case "Transient" :
                    $this->manuallyReviewBounce($bounce);
                    break;

                // Remove all recipients that generated a permanent bounce or an unknown bounce.
                default:
                    foreach ($bounce->bounce->bouncedRecipients as $recipient) {
                        $email = $recipient->emailAddress;
                        $listids = $this->interspire->getAllListsForEmailAddress($email);

                        // if the email is not in any list, we skip
                        if (is_null($listids)) {
                            echo '  - ' . $email . ' not subscribed to any list' . PHP_EOL;
                            continue;
                        }

                        // CAREFUL !!! we want to bounce this email in ALL lists, you might want to change this
                        foreach ($listids as $listid) {
                            $this->bounceRecipient($email, $listid);
                        }
                    }
                    break;
            }

            // done with message so we delete it from queue
            $this->sqs->deleteMessage($this->bouncesSqsUrl, $message['ReceiptHandle']);
        }

        // kinda dirty loop ???
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
        echo '  - a bounce has to be reviewed manually, check logs!' . PHP_EOL;
    }

    /**
     * Mark recipient as bounced in mailing lists
     *
     * @TODO Log DSN (http://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-examples.html#notification-examples-bounce)
     * @param string $email
     * @param int $listid
     */
    private function bounceRecipient($email, $listid = 1)
    {
        $result = $this->interspire->bounceSubscriber($email, $listid);
        Log::info('BOUNCE // ' . $email . ' : ' . $result);
        echo '  - mark ' . $email . ' as bounce says : ' . $result . PHP_EOL;
    }
}
