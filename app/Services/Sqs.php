<?php
/**
 * Created by PhpStorm.
 * User: Wolf
 * Date: 7/27/2015
 * Time: 10:36 AM
 */

namespace App\Services;

use Aws\Sdk;

class Sqs
{
    /**
     * @var Sdk
     */
    private $sdk;

    /**
     * @var SqsClient
     */
    private $sqs;


    public function __construct(Sdk $sdk)
    {
        $this->sdk = $sdk;

        if(is_null(env('AWS_ACCESS_KEY_ID', null)))
            abort(403, 'AWS_ACCESS_KEY_ID is not set in .env file');

        if(is_null(env('AWS_SECRET_ACCESS_KEY', null)))
            abort(403, 'AWS_SECRET_ACCESS_KEY is not set in .env file');

        if(is_null(env('AWS_REGION', null)))
            abort(403, 'AWS_REGION is not set in .env file');

        $this->sqs = $this->sdk->createClient('sqs');
    }

    /**
     * get SQS messages
     *
     * @param $sqsUrl
     * @return array
     */
    public function receiveMessages($sqsUrl)
    {
        $data = $this->sqs->receiveMessage([
            'QueueUrl' => $sqsUrl,
            'MaxNumberOfMessages' => 1,
        ]);
        return $data->search('Messages');
    }


    /**
     * Delete a SQS message
     *
     * @param $sqsUrl
     * @param $ReceiptHandle
     */
    public function deleteMessage($sqsUrl, $ReceiptHandle)
    {
        $this->sqs->deleteMessage([
            'QueueUrl' => $sqsUrl,
            'ReceiptHandle' => $ReceiptHandle,
        ]);
    }


}