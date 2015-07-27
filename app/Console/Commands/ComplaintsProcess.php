<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ComplaintsController;

class ComplaintsProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'complaints:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Amazon SQS complaints queue.';

    /**
     * @var ComplaintsController
     */
    protected $complaintsController;

    /**
     * Create a new command instance.
     *
     * @param ComplaintsController $complaintsController
     */
    public function __construct(ComplaintsController $complaintsController)
    {
        parent::__construct();
        $this->complaintsController = $complaintsController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment('Complaints are being processed');
        $response = $this->complaintsController->process();
        $this->info($response);
    }
}
