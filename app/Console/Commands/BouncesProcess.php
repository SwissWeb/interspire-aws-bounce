<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\BouncesController;

class BouncesProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bounces:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Amazon SQS bounces queue.';

    /**
     * @var BouncesController
     */
    protected $bouncesController;

    /**
     * Create a new command instance.
     *
     * @param BouncesController $bouncesController
     */
    public function __construct(BouncesController $bouncesController)
    {
        parent::__construct();
        $this->bouncesController = $bouncesController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment('Bounces are being processed');
        $response = $this->bouncesController->process();
        $this->info($response);
    }
}
