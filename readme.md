## Laravel 5.1 app that manages bounces/complaints from Amazon SES/SNS/SQS and Interspire API

[![Deployment status from DeployBot](https://sws.deploybot.com/badge/02267417959608/39353.svg)](http://deploybot.com)

This is a work in progress. The aim is to have a Laravel 5.1 app running in front of Interspire.
Our Interspire uses Amazon SES to send emails. We set up Amazon SNS notifications in a SQS queues. 
The app then queries the queues to see if there are any complaints/bounces. If so they are handled.

It uses the API to communicate with Interspire. See this package :
https://github.com/SwissWeb/laravel-interspire

## Installation
###To install laravel
Just clone this project
```
$ git clone https://github.com/SwissWeb/interspire-aws-bounce.git
```

Run composer
```
$ composer update
```

Copy .env.example to .env and edit it :
```
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
Go to http://example.com/bounces/process

or run
```
$ php artisan bounces:process
```

#####To process complaints :
Go to http://example.com/complaints/process

or run
```
$ php artisan complaints:process
```
