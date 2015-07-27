## Laravel 5.1 app that manages bounces/complaints from Amazon SES/SNS/SQS and Interspire API

[![Deployment status from DeployBot](https://sws.deploybot.com/badge/02267417959608/39353.svg)](http://deploybot.com)

This is a work in progress. The aim is to have a Laravel 5.1 app running in front of Interspire.
We uses Amazon SES to process Interspire emails. We have set up Amazon SNS notifications for complaints/bounces. These are pushed to Amazon SQS queues. 

The app queries the queues to see if there are any complaints/bounces. If so they are handled with Interspire's API. Bounces will be tagged as `Bounced` and complaints will be `Unbsuscribed` and addes to the `Suppression list` in Interspire. See this package for more info : https://github.com/SwissWeb/laravel-interspire

## Installation
###To install laravel
Just clone this project
```
$ git clone https://github.com/SwissWeb/interspire-aws-bounce.git
```

Run composer
```php
$ composer update
```

Copy .env.example to .env and edit it :
```php
$ cp .env.example .env
```

###To setup the AWS stuff here are hints :
For bounces
* Create an Amazon SQS queue named ses-bounces-queue.
* Create an Amazon SNS topic named ses-bounces-topic.
* Configure the Amazon SNS topic to publish to the SQS queue.
* Configure Amazon SES to publish bounce notifications using ses-bounces-topic to ses-bounces-queue.

For complaints
* Create an Amazon SQS queue named ses-complaints-queue.
* Create an Amazon SNS topic named ses-complaints-topic.
* Configure the Amazon SNS topic to publish to the SQS queue.
* Configure Amazon SES to publish complaint notifications using ses-complaints-topic to ses-complaints-queue.

###Usage
#####To process bounces :
Go to 
```http
http://example.com/bounces/process
```
or run
```php
$ php artisan bounces:process
```

#####To process complaints :
Go to :
```
http://example.com/complaints/process
```
or run :
```php
$ php artisan complaints:process
```

If you run the artisan command, you will get debug outputs. Ex:
```
Bounces are being processed
-> Amazon SQS pulling message(s)
-> Message(s) received
  - Start handling message(s) received
  - recipient1@example.com not subscribed to any list
  - recipient2@example.com not subscribed to any list
-> Amazon SQS pulling message(s)
Complaints processed. Bybye!
```

Or an error like :
```php
  [Symfony\Component\HttpKernel\Exception\HttpException]
  COMPLAINTS_SQS_URL is not set in .env file
```
