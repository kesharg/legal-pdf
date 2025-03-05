<?php

namespace App\Jobs\Outlook;

use App\Mail\Partner\OrderFailMail;
use App\Mail\OrderNotifyMail;
use App\Models\Order;
use App\Models\User;
use App\Services\File\SnappyPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

class GenerateOutlookPdfFromJsonFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        setMemoryLimitation();
        $order = $this->order;

        $order->update([
            "pdf_gen_start_at" => now(),
            "processing_status" => 3 // Fetching Start
        ]);

        $jsonFile = storage_path('app/public/' . $order->msg_json_file);

        // if file not exists then fail the order
        if (!File::exists($jsonFile)) {
            $this->fail(new \Exception('Json File not found.'));
        }

        // Get the file content
        $jsonData = File::get($jsonFile);

        // Decode the JSON file into an associative array
        $messages = json_decode($jsonData, true);

        if (!$messages) {
            $this->fail(new \Exception('Messages not found in json file.'));
        }

        $keydata = json_decode($order->request);
        if (!empty($keydata->inc_keywords)) {
            $keywordsArray = explode(',', $keydata->inc_keywords);
        } else {
            $keywordsArray = array();
        }

        $pdfFile = SnappyPdfService::generate($order, $messages, $keywordsArray, $order->language);

        if ($pdfFile != null) {
            // delete the json file
            //unlink($jsonFile);

            //send mail when pdf is generated
            $to = explode(',', config('developerMail.pdf-testing-email-ids'));
            $server = config('app.env');
            $pdfFilePath = storage_path('app/public/' . $pdfFile);

            // Convert MB to bytes (25 MB = 25 * 1024 * 1024)
            $maxFileSize = 25 * 1024 * 1024;

            if ($server != 'local' && !empty($to) && file_exists($pdfFilePath)) {
                $fileSize = filesize($pdfFilePath);

                Mail::raw("This PDF is generated between email: {$order->from_email} and {$order->recipient_email}", function ($message) use ($pdfFile, $to, $server, $fileSize, $pdfFilePath, $maxFileSize) {
                    $message->to($to)
                        ->subject(ucfirst($server) . ' Server PDF Generated');

                    // Attach only if file size is within the limit
                    if ($fileSize <= $maxFileSize) {
                        $message->attach($pdfFilePath, [
                            'as' => $pdfFile,
                            'mime' => 'application/pdf',
                        ]);
                    } else {
                        $message->setBody("Attachment size exceeds 25 MB and could not be included.");
                    }
                });
            }



            // file found
            $order->update([
                "pdf_gen_end_at" => now(),
                "processing_status" => 4,
                "pdf_file" => $pdfFile,
                "status" => "Done"
            ]);

            // Update progress in Redis
            Redis::set("job_progress_{$order->id}", json_encode(['status' => 'processing', 'progress' => 100, 'pdf' => "Done"]));
            $this->notifyOrderEmail();
        } else {
            $this->PdfGenerationFailed($order);
        }
    }

    private function notifyOrderEmail()
    {
        $order = $this->order;

        if ($order->notify == 1) {

            if ($order->pdfgenerated_email) {
                $email = $order->pdfgenerated_email;
            } else {
                $email = $order->from_email;
            }

            Mail::to($email)->queue(new OrderNotifyMail($order));

            info("Order Complete and sending email to notify.");
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        // Log the exception using Laravel's default report method
        report($exception);

        // Update the related order's status to 'Failed'
        // Assuming you have access to the order instance in the job
        $order = $this->order;
        $this->PdfGenerationFailed($order);

    }

    protected function PdfGenerationFailed(Order $order)
    {
        if ($order) {
            // Check if job is running for first time then only mail will be sent
            if ($this->attempts() == 1) {
                //Find partner and send mail to partner
                $partner = User::where('id', $order->partner_id)->first();
                if ($partner && $partner->email != '' && $partner->email != null) {

                    Mail::to($partner->email)->queue(new OrderFailMail($order));
                }
            }

            $order->update([
                "pdf_gen_end_at" => now(),
                "status" => "Failed"
            ]);

            // Update progress in Redis
            Redis::set("job_progress_{$order->id}", json_encode(['status' => 'failed', 'progress' => 0]));
        }
    }
}
