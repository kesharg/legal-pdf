<?php

namespace App\Services\File;

use App\Models\Order;
use App\Services\Google\OrderMessageService;
use App\Utils\SessionLab;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Redis;

class PdfService
{

    public function getHeaderTagText($text1, $text2)
    {
        return '<header  class="pdf-page-header">
            <div class="pdf-repeat float-left">
                <h3 class="title-1">' . $text1 . '</h3>
            </div>
            <div class="pdf-repeat float-left">
                <h3 class="title-2">' . $text2 . '</h3>
            </div>
            <div class="pdf-repeat float-left">
                <h3 class="title-3">legalpdf.co</h3>
            </div>
            <div class="clear-both"></div>
        </header>';
    }

    public function createPDF(
        $data,
        $emailTo,
        $language,
        $inc_array,
        $token = null,
        object | null $order = null,
        $parseOneMessage = null
    ) {
        //info("parseOneMessage in PDF Service: " . $parseOneMessage);
        //info("parseOneMessage in PDF Service: " . $parseOneMessage);
        info("From CREATE PDF from: " . $parseOneMessage["from"]);
        info('Language in PDF Service:' . $language);

        ob_start();
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', -1);
        set_time_limit(-1);
        ini_set("pcre.backtrack_limit", 500000000000);

        $inc_array = is_array($inc_array) && !empty($inc_array) ? $inc_array : [];


        $date            = now()->format('d_m_Y');
        $datePrefix      = now()->format('d_m_Y_h_i_s_v');
        $dateAt          = now()->format('d/m/Y');
        $timeAt          = now()->format('H:i:s');
        $authUser        = $order->from_email ?? LaravelGmail::user();
        $username        =  substr($authUser, 0, strpos($authUser, "@"));
        //  $file_path       = randomStringNumberGenerator(6).'_LegalPDF.pdf';
        $file_path       = $username . '_' . $datePrefix . '_LegalPDF.pdf';
        $emailToUsername = substr($emailTo, 0, strpos($emailTo, "@"));
        $logo            = public_path('web/assets/img/resize-image/logo-192x192.png');

        $access_token = is_null($token) ? LaravelGmail::getToken()["access_token"] : $token;

        // Define the maximum string length to process at a time
        $max_length = 2000;

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode'    => 'utf-8',
                'format'  => 'A4-P',
                'margin_header' => 3,
                'tempDir' => storage_path("app/public")
            ]);
        } catch (\Mpdf\MpdfException $e) {
            info("Mpdf Exception : " . json_encode(errorArray($e)));
        }
        $text1 = "";
        $text2 = "";

        if ($language == 'he') {
            info("Inside Hebru Block in create PDF");
            $text1 = "מאובטח, מהיר ומעוצב";
            $text2 = 'מחלץ התכתבויות דוא"ל ל- PDF';
        } else {
            $text1 = "FAST & SECURED";
            $text2 = "Emails and Chats Extractor";
            info("Inside English Block in create PDF");
        }

        // Define the header HTML
        $headerHtml = '
        <header class="pdf-page-header" style="border-bottom:1.2px solid #7a7a7a">
            <div style="width:8%;float:left;" >
            <img width="40" height="40" src="' . $logo . '" loading="lazy" alt="logo">
            </div>
            <div style="width:67%;float:left;">
            <p><span style="color: #c3b019de;font-weight: bold;font-size: 1.6rem;">LegalPDF.co</span><br><span style="color:#7a7a7a;font-weight:bold">' . $text2 . '</span></p>

            </div>
            <div style="width:21%;float:right;text-align:right!important;">
                 <p><span style="text-align:right!important;color:#7a7a7a">' . $dateAt . ' at ' . $timeAt . '</span><br><span style="color: #c3b019de;font-size: 1.2rem;font-weight: bold;">' . $text1 . '</span></p>
            </div>
        </header>';
        $headerHtmlHeb = '
        <header class="pdf-page-header" style="border-bottom:1.2px solid #7a7a7a; direction: rtl;">
            <div style="width:8%;float:right;">
                <img width="40" height="40" src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '" loading="lazy" alt="logo">
            </div>
            <div style="width:67%;float:left;">
                <p>
                    <span style="color: #c3b019de;font-weight: bold;font-size: 1.6rem;">LegalPDF.co</span>
                    <br><span style="color:#7a7a7a;font-weight:bold">' . htmlspecialchars($text2, ENT_QUOTES, 'UTF-8') . '</span>
                </p>
            </div>
            <div style="width:21%;float:left;text-align:left;">
                 <p><span style="text-align:left;color:#7a7a7a">' . htmlspecialchars($dateAt, ENT_QUOTES, 'UTF-8') . ' ב-' . htmlspecialchars($timeAt, ENT_QUOTES, 'UTF-8') . '</span><br><span style="color: #c3b019de;font-size: 1.2rem;font-weight: bold;">' . htmlspecialchars($text1, ENT_QUOTES, 'UTF-8') . '</span></p>
            </div>
        </header>';

        // Define the footer HTML
        $footerHtml = '
            <footer class="pdf-page-footer">
                <h3 class="title">- Page {PAGENO} of {nbpg} -</h3>
            </footer>';
        $mpdf->AddPage();
        // Set the header HTML
        if ($language == 'he') {
            $mpdf->SetHTMLHeader($headerHtmlHeb, '');
        } else {
            $mpdf->SetHTMLHeader($headerHtml, '');
        }

        // Set the footer HTML
        $mpdf->SetHTMLFooter($footerHtml);

        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        $countData = count($data);

        $html = $this->getHeaderHtml(
            $logo,
            $username,
            $authUser,
            $emailTo,
            $dateAt,
            $timeAt,
            $language,
            $countData,
            $parseOneMessage
        );

        // dd($html);
        $mpdf->WriteHTML($html);

        $num = 0;

        $order = $order ?? Order::query()->findOrFail(session()->get('order_id'));

        // if (isset($data['messages'])) {
        if (isset($data)) {
            //foreach ($data['messages'] as $message) {
            info("Message Loop just started without JOB : Order" . json_encode($order));
            foreach ($data as $message) {
                $messageId = $message['id'];

                $singleMessage = (new OrderMessageService())->fetchSingleMessage($messageId, $access_token);
                $messageDetails = $singleMessage;

                $num++;

                $parseMessage = $this->parseMessage($messageDetails, $num);

                $eData = $parseMessage;

                $viewData = compact(
                    'eData',
                    'logo',
                    'username',
                    'emailToUsername',
                    'authUser',
                    'emailTo',
                    'dateAt',
                    'timeAt',
                    'language',
                    'inc_array',
                    'num'
                );

                $mpdf->WriteHTML(view('web.pdf.pdf_view', $viewData)->render());
                //$html .= view('web.pdf.pdf_view', $viewData)->render();
            }

            info("Message Loop just End");
            info("total pages : " . $num);
            $total_page_update_status = $order->update(['total_pages' => $num]);
            info("Total Pages update status:" . $total_page_update_status);
            $progress = floor(($num / $countData) * 100);

            // Update progress in Redis
            $orderKey = $order->id;
            $redisKey = "job_progress_{$orderKey}";
            info("Redis Key inside PDF gerneration without Job:" . $redisKey);
            info("progress inside PDF gerneration without Job:" . $progress);
            Redis::set($redisKey, json_encode(['status' => 'processing', 'progress' => $progress, 'fetching_messages' => $num."/".$countData]));
        }

        $mpdf->WriteHtml($this->getFooterHtml());

        $mpdf->Output(storage_path('app/public/' . $file_path), 'F');

        (new SessionLab())->setDownloadFile($file_path);

        info("Saved File Path : " . $file_path);


        $order->update([
            "pdf_file" => $file_path, "total_pages" => $num
        ]);

        return $file_path;
    }
    public function createPDFWithJob(
        $data,
        $emailTo,
        $language,
        $inc_array,
        $token = null,
        object | null $order = null,
        $parseOneMessage = null
    ) {
        ob_start();
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', -1);
        set_time_limit(-1);
        //ini_set("pcre.backtrack_limit", 500000000000);

        $inc_array = is_array($inc_array) && !empty($inc_array) ? $inc_array : [];

        $date            = now()->format('d_m_Y');
        $datePrefix      = now()->format('d_m_Y_h_i_s_v');
        $dateAt          = now()->format('d/m/Y');
        $timeAt          = now()->format('H:i:s');
        $authUser        = $order->from_email ?? LaravelGmail::user();
        $username        = substr($authUser, 0, strpos($authUser, "@"));
        $file_path       = $username . '_' . $datePrefix . '_LegalPDF.pdf';
        $emailToUsername = substr($emailTo, 0, strpos($emailTo, "@"));
        $logo            = public_path('web/assets/img/resize-image/logo-192x192.png');
        $access_token    = $token;

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode'    => 'utf-8',
                'format'  => 'A4-P',
                'margin_header' => 3,
                'tempDir' => storage_path("app/public")
            ]);
        } catch (\Mpdf\MpdfException $e) {
            info("Mpdf Exception : " . json_encode(errorArray($e)));
        }

        $text1 = $language == 'he' ? "מאובטח, מהיר ומעוצב" : "FAST & SECURED";
        $text2 = $language == 'he' ? 'מחלץ התכתבויות דוא"ל ל- PDF' : "Emails and Chats Extractor";

        $headerHtml = '
        <header class="pdf-page-header" style="border-bottom:1.2px solid #7a7a7a">
            <div style="width:8%;float:left;">
                <img width="40" height="40" src="' . $logo . '" loading="lazy" alt="logo">
            </div>
            <div style="width:67%;float:left;">
                <p>
                    <span style="color: #c3b019de;font-weight: bold;font-size: 1.6rem;">LegalPDF.co</span>
                    <br><span style="color:#7a7a7a;font-weight:bold">' . $text2 . '</span>
                </p>
            </div>
            <div style="width:21%;float:right;text-align:right!important;">
                <p><span style="text-align:right!important;color:#7a7a7a">' . $dateAt . ' at ' . $timeAt . '</span><br><span style="color: #c3b019de;font-size: 1.2rem;font-weight: bold;">' . $text1 . '</span></p>
            </div>
        </header>';

        $headerHtmlHeb = '
        <header class="pdf-page-header" style="border-bottom:1.2px solid #7a7a7a; direction: rtl;">
            <div style="width:8%;float:right;">
                <img width="40" height="40" src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '" loading="lazy" alt="logo">
            </div>
            <div style="width:67%;float:right;">
                <p>
                    <span style="color: #c3b019de;font-weight: bold;font-size: 1.6rem;">LegalPDF.co</span>
                    <br><span style="color:#7a7a7a;font-weight:bold">' . htmlspecialchars($text2, ENT_QUOTES, 'UTF-8') . '</span>
                </p>
            </div>
            <div style="width:21%;float:left;text-align:left;">
                <p><span style="text-align:left;color:#7a7a7a">' . htmlspecialchars($dateAt, ENT_QUOTES, 'UTF-8') . ' ב-' . htmlspecialchars($timeAt, ENT_QUOTES, 'UTF-8') . '</span><br><span style="color: #c3b019de;font-size: 1.2rem;font-weight: bold;">' . htmlspecialchars($text1, ENT_QUOTES, 'UTF-8') . '</span></p>
            </div>
        </header>';

        $footerHtml = '
        <footer class="pdf-page-footer">
            <h3 class="title">- Page {PAGENO} of {nbpg} -</h3>
        </footer>';

        $mpdf->AddPage();
        $mpdf->SetHTMLHeader($language == 'he' ? $headerHtmlHeb : $headerHtml, '');
        $mpdf->SetHTMLFooter($footerHtml);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        $countData = count($data);
        $html = $this->getHeaderHtml(
            $logo,
            $username,
            $authUser,
            $emailTo,
            $dateAt,
            $timeAt,
            $language,
            $countData,
            $parseOneMessage
        );

        $mpdf->WriteHTML($html);

        $num = 0;
        $order = $order ?? Order::query()->findOrFail(session()->get('order_id'));

        $chunkSize = 1; // Number of messages to process at a time
        $chunks = array_chunk($data, $chunkSize);
        // info('Chunks inside PDF Service');
        // info($chunks);
        // die;
        foreach ($chunks as $chunk) {
            foreach ($chunk as $message) {
                $messageId = $message['id'];
                $singleMessage = (new OrderMessageService())->fetchSingleMessage($messageId, $access_token);
                $messageDetails = $singleMessage;
                $num++;
                $parseMessage = $this->parseMessage($messageDetails, $num);
                $eData = $parseMessage;

                $viewData = compact(
                    'eData',
                    'logo',
                    'username',
                    'emailToUsername',
                    'authUser',
                    'emailTo',
                    'dateAt',
                    'timeAt',
                    'language',
                    'inc_array',
                    'num'
                );

                $mpdf->WriteHTML(view('web.pdf.pdf_view', $viewData)->render());

                // if ($num < $countData) {
                //     $mpdf->AddPage();
                // }

                $progress = floor(($num / $countData) * 100);
                $orderKey = $order->id;
                $redisKey = "job_progress_{$orderKey}";
                Redis::set($redisKey, json_encode(['status' => 'processing', 'progress' => $progress, 'fetching_messages' => $num."/".$countData]));

                if ($num == 1) {
                    $order->status = 'Generating';
                    $status = $order->save();
                    info('generating status inside redis:' . $status);
                }
            }

            unset($chunk);
            gc_collect_cycles();
        }

        $mpdf->WriteHtml($this->getFooterHtml());
        $mpdf->Output(storage_path('app/public/' . $file_path), 'F');

        // Clear memory
        unset($mpdf);

        (new SessionLab())->setDownloadFile($file_path);

        info("Saved File Path : " . $file_path);

        $order->update([
            "pdf_file" => $file_path,
            "total_pages" => $num
        ]);

        return $file_path;
    }


    public function createPDFWithJob_main(
        $data,
        $emailTo,
        $language,
        $inc_array,
        $token = null,
        object | null $order = null,
        $parseOneMessage = null
    ) {
        //info("parseOneMessage in PDF Service: " . $parseOneMessage);
        info("From CREATE PDF from: " . $parseOneMessage["from"]);
        info('Language in PDF Service:' . $language);

        ob_start();
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', -1);
        set_time_limit(-1);
        ini_set("pcre.backtrack_limit", 500000000000);

        $inc_array = is_array($inc_array) && !empty($inc_array) ? $inc_array : [];


        $date            = now()->format('d_m_Y');
        $datePrefix      = now()->format('d_m_Y_h_i_s_v');
        $dateAt          = now()->format('d/m/Y');
        $timeAt          = now()->format('H:i:s');
        $authUser        = $order->from_email ?? LaravelGmail::user();
        $username        =  substr($authUser, 0, strpos($authUser, "@"));
        //  $file_path       = randomStringNumberGenerator(6).'_LegalPDF.pdf';
        $file_path       = $username . '_' . $datePrefix . '_LegalPDF.pdf';
        $emailToUsername = substr($emailTo, 0, strpos($emailTo, "@"));
        $logo            = public_path('web/assets/img/resize-image/logo-192x192.png');

        $access_token = $token;

        // Define the maximum string length to process at a time
        $max_length = 2000;

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode'    => 'utf-8',
                'format'  => 'A4-P',
                'margin_header' => 3,
                'tempDir' => storage_path("app/public")
            ]);
        } catch (\Mpdf\MpdfException $e) {
            info("Mpdf Exception : " . json_encode(errorArray($e)));
        }
        $text1 = "";
        $text2 = "";
        if ($language == 'he') {
            info("Inside Hebru Block in create PDF");
            $text1 = "מאובטח, מהיר ומעוצב";
            $text2 = 'מחלץ התכתבויות דוא"ל ל- PDF';
        } else {
            $text1 = "FAST & SECURED";
            $text2 = "Emails and Chats Extractor";
            info("Inside English Block in create PDF");
        }

        // Define the header HTML
        $headerHtml = '
        <header class="pdf-page-header" style="border-bottom:1.2px solid #7a7a7a">
            <div style="width:8%;float:left;" >
                <img width="40" height="40" src="' . $logo . '" loading="lazy" alt="logo">
            </div>
            <div style="width:67%;float:left;">
                <p>
                    <span style="color: #c3b019de;font-weight: bold;font-size: 1.6rem;">LegalPDF.co</span>
                    <br><span style="color:#7a7a7a;font-weight:bold">' . $text2 . '</span>
                </p>
            </div>
            <div style="width:21%;float:right;text-align:right!important;">
                 <p><span style="text-align:right!important;color:#7a7a7a">' . $dateAt . ' at ' . $timeAt . '</span><br><span style="color: #c3b019de;font-size: 1.2rem;font-weight: bold;">' . $text1 . '</span></p>
            </div>
        </header>';

        $headerHtmlHeb = '
    <header class="pdf-page-header" style="border-bottom:1.2px solid #7a7a7a; direction: rtl;">
        <div style="width:8%;float:right;">
            <img width="40" height="40" src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '" loading="lazy" alt="logo">
        </div>
        <div style="width:67%;float:right;">
            <p>
                <span style="color: #c3b019de;font-weight: bold;font-size: 1.6rem;">LegalPDF.co</span>
                <br><span style="color:#7a7a7a;font-weight:bold">' . htmlspecialchars($text2, ENT_QUOTES, 'UTF-8') . '</span>
            </p>
        </div>
        <div style="width:21%;float:left;text-align:left;">
             <p><span style="text-align:left;color:#7a7a7a">' . htmlspecialchars($dateAt, ENT_QUOTES, 'UTF-8') . ' ב-' . htmlspecialchars($timeAt, ENT_QUOTES, 'UTF-8') . '</span><br><span style="color: #c3b019de;font-size: 1.2rem;font-weight: bold;">' . htmlspecialchars($text1, ENT_QUOTES, 'UTF-8') . '</span></p>
        </div>
    </header>';


        // Define the footer HTML
        $footerHtml = '
            <footer class="pdf-page-footer">
                <h3 class="title">- Page {PAGENO} of {nbpg} -</h3>
            </footer>';
        $mpdf->AddPage();
        // Set the header HTML
        if ($language == 'he') {
            $mpdf->SetHTMLHeader($headerHtmlHeb, '');
        } else {
            $mpdf->SetHTMLHeader($headerHtml, '');
        }


        // Set the footer HTML
        $mpdf->SetHTMLFooter($footerHtml);

        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        $countData = count($data);

        $html = $this->getHeaderHtml(
            $logo,
            $username,
            $authUser,
            $emailTo,
            $dateAt,
            $timeAt,
            $language,
            $countData,
            $parseOneMessage
        );

        // dd($html);
        $mpdf->WriteHTML($html);

        $num = 0;

        $order = $order ?? Order::query()->findOrFail(session()->get('order_id'));


        // if (isset($data['messages'])) {
        if (isset($data)) {
            //foreach ($data['messages'] as $message) {
            info("Message Loop just started for PdfGenerateJob  : Order" . json_encode($order));
            foreach ($data as $message) {
                $messageId = $message['id'];


                $singleMessage = (new OrderMessageService())->fetchSingleMessage($messageId, $access_token);
                $messageDetails = $singleMessage;

                info("No of Mesage:" . $num);

                $num++;

                $parseMessage = $this->parseMessage($messageDetails, $num);

                $eData = $parseMessage;

                $viewData = compact(
                    'eData',
                    'logo',
                    'username',
                    'emailToUsername',
                    'authUser',
                    'emailTo',
                    'dateAt',
                    'timeAt',
                    'language',
                    'inc_array',
                    'num'
                );

                $mpdf->WriteHTML(view('web.pdf.pdf_view', $viewData)->render());
                //$html .= view('web.pdf.pdf_view', $viewData)->render();
                // Calculate progress
                $progress = floor(($num / $countData) * 100);

                // Update progress in Redis
                $orderKey = $order->id;
                $redisKey = "job_progress_{$orderKey}";
                info("Redis Key:" . $redisKey);
                info("progress:" . $progress);
                Redis::set($redisKey, json_encode(['status' => 'processing', 'progress' => $progress, 'fetching_messages' => $num."/".$countData]));
                if ($num == 1) {
                    $order->status = 'Generating';
                    $status = $order->save();
                    info('generating status inside redis:' . $status);
                }
            }

            info("Message Loop just End");
            info("total pages : " . $num);
            $total_page_update_status = $order->update(['total_pages' => $num]);
            // info("Total Pages update status:" . $total_page_update_status);
        }

        $mpdf->WriteHtml($this->getFooterHtml());

        $mpdf->Output(storage_path('app/public/' . $file_path), 'F');

        (new SessionLab())->setDownloadFile($file_path);

        info("Saved File Path : " . $file_path);


        $order->update([
            "pdf_file" => $file_path, "total_pages" => $num
        ]);

        return $file_path;
    }


    function parseMessage($messageDetails, $key)
    {
        $parsedMessage = [];

        // if($key == 0){
        //     info("Details : ".json_encode($messageDetails));
        // }

        // Get headers
        $headers = $messageDetails['payload']['headers'];

        // $myInfo= [
        //     'id'       => $messageDetails['id'],
        //     'threadId' => $messageDetails['threadId'],
        //     'snippet'  => $messageDetails['snippet'],
        //     'subject'  => $this->getHeader($headers, 'Subject'),
        //     'from'     => $this->getHeader($headers, 'From'),
        //     'to'       => $this->getHeader($headers, 'To'),
        //     'date'     => $this->getHeader($headers, 'Date'),
        //     'body'     => $this->getBody($messageDetails['payload']),
        // ];

        //info("Message NO : {$key} = ".json_encode($myInfo));

        foreach ($headers as $header) {
            if ($header['name'] == 'Subject') {
                $parsedMessage['subject'] = $header['value'];
            } elseif ($header['name'] == 'From') {
                $parsedMessage['from'] = $header['value'];
            } elseif ($header['name'] == 'To') {
                $parsedMessage['to'] = $header['value'];
            } elseif ($header['name'] == 'Cc') {
                $parsedMessage['cc'] = $header['value'];
            } elseif ($header['name'] == 'Bcc') {
                $parsedMessage['bcc'] = $header['value'];
            } elseif ($header['name'] == 'Date') {
                $date          = Carbon::parse($header['value']);
                $formattedDate = $date->format('d/m/Y H:i');

                $parsedMessage['date'] = $formattedDate; // $header['value'];
            }
        }

        // Get body
        $body = getBody($messageDetails['payload']);
        $parsedMessage['body'] = $body;

        //info("Message Inside : " . json_encode($parsedMessage));

        return $parsedMessage;
    }

    private function getHeader($headers, $name)
    {
        foreach ($headers as $header) {
            if ($header['name'] === $name) {
                return $header['value'];
            }
        }
        return null;
    }

    private function getBody($payload)
    {
        if (isset($payload['parts'])) {
            foreach ($payload['parts'] as $part) {
                if ($part['mimeType'] === 'text/plain' && isset($part['body']['data'])) {
                    return base64_decode(str_replace(['-', '_'], ['+', '/'], $part['body']['data']));
                }
            }
        } elseif (isset($payload['body']['data'])) {
            return base64_decode(str_replace(['-', '_'], ['+', '/'], $payload['body']['data']));
        }
        return null;
    }

    protected function getHeaderHtml($logo, $username, $authUser, $emailTo, $dateAt, $timeAt, $language, $countData, $oneMessageParse = null)
    {
        info("language in getHeaderHtml:" . $language);
        $fromUserName = null;
        $toUserName = null;

        $fromEmail = null;
        $toEmail   = null;

        $fromFullString = null;
        $toFullString   = null;

        $email  = extractEmail($oneMessageParse["from"]);

        if ($email == laravelGmailUser()) {
            $fromEmail = laravelGmailUser();
            $toEmail   = extractEmail($oneMessageParse["to"]);
        } else {
            $fromEmail = $email;
            $toEmail   = laravelGmailUser();
            $toFullString = $oneMessageParse["to"];
        }

        $fromUserName = extractName($oneMessageParse["from"]);
        $toUserName   = extractName($oneMessageParse["to"]);

        $htmlHeb = "<div style='direction: rtl;'>
                        <div style='width:25%;' class='left-align float-right'>
                             <div>
                            <img width='95' height='95' src='" . $logo . "' loading='lazy' alt='logo'>
                            </div>
                             <p class='logo-slogan' style='color: #c3b019de;font-size: 1.3rem;font-weight: bold;'> מאובטח, מהיר ומעוצב</p>
                        </div>
                        <div class='right-align float-right' style='width:75%;text-align: right;'>

                         <span class='title' style='color: #c3b019de; font-size:1.8rem;text-align:right!important'>
                         מחלץ התכתבויות דוא'ל ל- PDF
                         </span>
                                <p class='clear-both'>
                                    <span class='title' style='color: #c3b019de; font-size:1.8rem;text-align:right!important'>
                                    זהו מסמך רשמי מוכן לעיון ושימוש בבית המשפט.
                                    המסמך מכיל את כל המידע שהוזמן על ידי הלקוח הרשום מטה.
                                    </span>
                                </p>
                                <p  style='font-size: 1.6rem;color:#7a7a7a;margin-top:2.7rem;text-align:left'>
                                    www.LegalPDF.co | +44 2 089 850 107
                                </p>

                        </div>
                        <div class='clear-both'></div>
                    </div>";

        $htmlEn = "<div >
                        <div class='' style='width:25%;float:left'>
                            <div>
                                <img width='95' height='95' src='" . $logo . "' alt='logo' loading='lazy'/>
                            </div>
                             <p class='logo-slogan' style='color: #c3b019de;font-size: 1.3rem;font-weight: bold;'>
                                FAST & SECURED
                             </p>
                        </div>
                        <div style='width:75%;float:left'>
                            <p class='clear-both'>
                                <span class='title' style='color: #c3b019de; font-size:1.8rem;text-align:left!important'>
                                    Email's Messages into an Organised Document
                                </span>
                                <br>
                                <span  style='font-size: 1.6rem;color:#7a7a7a;text-align:lef!timportant'>
                                        This is an official document produced by LegalPDF. The Document authentically reflects
                                        email correspondence between the two parties mentioned below.
                                </span>
                            </p>
                            <p  style='font-size: 1.6rem;color:#7a7a7a;margin-top:2.7rem;text-align:left'>
                                   www.LegalPDF.co | +44 2 089 850 107
                            </p>

                        </div>
                        <div class='clear-both'></div>
                    </div>";
        $html2Heb = '
                    <div style="text-align:center;padding-top:10px;">
                        <span style="color: #7a7a7a; font-size: 1.2rem; font-weight: bold;">פרטי ההזמנה</span>
                    </div>
                    <div class="order-details-card">
                        <div class="card-items">
                            <div class="item">
                                <div style="width:30%; float:right; margin-top:20px;">
                                    <span style="color: #7A7A7A; font-size: 1.2rem; font-weight: bold;">הוזמן על ידי</span>
                                </div>
                                <div style="width:70%; float:left; text-align:left;">
                                    <span style="text-align:left; color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;">' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . ' | ' . htmlspecialchars($authUser, ENT_QUOTES, 'UTF-8') . '</span>
                                </div>
                            </div>
                            <div class="item">
                                <div style="width:30%; float:right; margin-top:20px;">
                                    <span style="color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;" class="label">תאריך</span>
                                </div>
                                <div style="width:70%; float:left; text-align:left;">
                                    <span style="text-align:left; color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;" class="valueP">' . htmlspecialchars($dateAt, ENT_QUOTES, 'UTF-8') . ' ב-' . htmlspecialchars($timeAt, ENT_QUOTES, 'UTF-8') . '</span>
                                </div>
                            </div>
                            <div class="item">
                                <div style="width:30%; float:right; margin-top:20px;">
                                    <span style="color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;">פלטפורמה</span>
                                </div>
                                <div style="width:70%; float:left; text-align:left;">
                                    <span style="text-align:left; color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;" class="valueP">Gmail</span>
                                </div>
                            </div>
                            <div class="item">
                                <div style="width:30%; float:right; margin-top:20px;">
                                    <span style="color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;">מספר מיילים:</span>
                                </div>
                                <div style="width:70%; float:left; text-align:left;">
                                    <span style="text-align:left; color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;" class="valueP">' . htmlspecialchars($countData, ENT_QUOTES, 'UTF-8') . '</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="additional-card">
                        <h3 class="card-title">המסמך הבא מכיל התכתבויות שבין הצדדים הבאים:</h3>
                        <div class="card-box">
                            <div class="card-p float-right">
                                <p class="text-center"><span class="label">שם: </span>&nbsp;<span class="content">' . htmlspecialchars($fromUserName, ENT_QUOTES, 'UTF-8') . '</span></p>
                                <p class="text-center"><span class="label">מייל: </span>&nbsp;<span class="content">' . htmlspecialchars($fromEmail, ENT_QUOTES, 'UTF-8') . '</span></p>
                            </div>
                            <div class="card-c float-right">
                                <h1 class="text-center"><span class="content">ו</span></h1>
                            </div>
                            <div class="card-p float-right">
                                <p class="text-center"><span class="label">שם: </span>&nbsp;<span class="content">' . htmlspecialchars($toUserName, ENT_QUOTES, 'UTF-8') . '</span></p>
                                <p class="text-center"><span class="label">מייל: </span>&nbsp;<span class="content">' . htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8') . '</span></p>
                            </div>
                            <div class="clear-both"></div>
                        </div>
                    </div>
                    <p class="notes">התכתובות הבאות מסודרות מהחדש ביותר בראש המסמך, ועד הישן ביותר בתחתית המסמך</p>';


        $html2Heb_backup = "
                     <div style='text-align:center;padding-top:10px;'>
                        <span  style='color: #7a7a7a; font-size: 1.2rem; font-weight: bold;'>פרטי הזמנה</span>
                     </div>
                     <div class='order-details-card'>

                        <div class='card-items'>
                            <div class='item'>
                                <div style='width:30%; float:right; margin-top:20px;'>
                                <span style='color: #7A7A7A; font-size: 1.2rem; font-weight: bold;'>הוזמן על ידי</span>
                            </div>
                                <p class='valueP'>" . $username . " | " . $authUser . "</p>
                            </div>
                            <div class='item'>
                                <p>תאריך </p>
                                <p class='valueP'>" . $dateAt . " at " . $timeAt . "</p>
                            </div>
                            <div class='item'>
                                <p>פלטפורמה </p>
                                <p class='valueP'>Gmail </p>
                            </div>
                            <div class='item'>
                                <p>כמות תכתובות </p>
                                <p class='valueP'>" . $countData . "</p>
                            </div>
                        </div>
                    </div>
                    <div class='additional-card'>
                        <h3 class='card-title'>המסמך הבא מכיל התכתבויות שבין הצדדים הבאים:</h3>
                        <div class='card-box'>
                              <div class='card-p float-left'>
                                <p class='text-center'><span class='label'>שֵׁם: </span>&nbsp;<span class='content'>" .  $fromUserName . "</span></p>
                                <p class='text-center'><span class='label'>אימייל: </span>&nbsp;<span class='content'>" . $fromEmail . "</span></p>
                            </div>
                            <div class='card-c float-left'>
                                <h1 class='text-center'><span class='content'>עם</span></h1>
                            </div>
                            <div class='card-p float-left'>
                                <p class='text-center'><span class='label'>שֵׁם: </span>&nbsp;<span class='content'>" . $toUserName . "</span></p>
                                <p class='text-center'><span class='label'>אימייל: </span>&nbsp;<span class='content'>" . $toEmail . "</span></p>
                            </div>
                            <div class='clear-both'></div>
                        </div>
                    </div>
                    <p class='notes'>התכתובות הבאות מסודרות מהחדש ביותר בראש המסמך, ועד הישן ביותר בתחתית המסמך</p>";




        $nameParts = explode(" ", $fromUserName);

        $html2En = '
        <div style="text-align:center;padding-top:10px;">
            <span  style="color: #7a7a7a; font-size: 1.2rem; font-weight: bold;">Order details</span>
        </div>
    <div class="order-details-card">

        <div class="card-items">
            <div class="item">
                <div style="width:30%; float:left; margin-top:20px;">
                    <span style="color: #7A7A7A; font-size: 1.2rem; font-weight: bold;">Ordered by</span>
                </div>
                <div style="width:70%; float:right; text-align:right;">
                    <span style="text-align:right; color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;">' . htmlspecialchars($nameParts[0], ENT_QUOTES, 'UTF-8') . ' | ' . htmlspecialchars($authUser, ENT_QUOTES, 'UTF-8') . '</span>
                </div>
            </div>
            <div class="item">
                <div style="width:30%; float:left; margin-top:20px;">
                    <span style="color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;" class="label">Date</span>
                </div>
                <div style="width:70%; float:right; text-align:right;">
                    <span style="text-align:right; color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;" class="valueP">' . htmlspecialchars($dateAt, ENT_QUOTES, 'UTF-8') . ' at ' . htmlspecialchars($timeAt, ENT_QUOTES, 'UTF-8') . '</span>
                </div>
            </div>
            <div class="item">
                <div style="width:30%; float:left; margin-top:20px;">
                    <span style="color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;">Platform</span>
                </div>
                <div style="width:70%; float:right; text-align:right;">
                    <span style="text-align:right; color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;" class="valueP">Gmail</span>
                </div>
            </div>
            <div class="item">
                <div style="width:30%; float:left; margin-top:20px;">
                    <span style="color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;">Number of emails:</span>
                </div>
                <div style="width:70%; float:right; text-align:right;">
                    <span style="text-align:right; color: #7A7A7A!important; font-size: 1.2rem; font-weight: bold;" class="valueP">' . htmlspecialchars($countData, ENT_QUOTES, 'UTF-8') . '</span>
                </div>
            </div>
        </div>
    </div>
    <div class="additional-card">
        <h3 class="card-title">The following document contains emails between</h3>
        <div class="card-box">
            <div class="card-p float-left">
                <p class="text-center"><span class="label">Name: </span>&nbsp;<span class="content">' . htmlspecialchars($fromUserName, ENT_QUOTES, 'UTF-8') . '</span></p>
                <p class="text-center"><span class="label">Email: </span>&nbsp;<span class="content">' . htmlspecialchars($fromEmail, ENT_QUOTES, 'UTF-8') . '</span></p>
            </div>
            <div class="card-c float-left">
                <h1 class="text-center"><span class="content">And</span></h1>
            </div>
            <div class="card-p float-left">
                <p class="text-center"><span class="label">Name: </span>&nbsp;<span class="content">' . htmlspecialchars($toUserName, ENT_QUOTES, 'UTF-8') . '</span></p>
                <p class="text-center"><span class="label">Email: </span>&nbsp;<span class="content">' . htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8') . '</span></p>
            </div>
            <div class="clear-both"></div>
        </div>
    </div>
    <p class="notes">* The following emails are in order from newest to oldest</p>';


        if ($language == 'he') {
            $display = $htmlHeb;
            $display2 = $html2Heb;
        } else {
            $display = $htmlEn;
            $display2 = $html2En;
        }

        $html = "<html lang='en'>
                    <head>
                        <meta charset='UTF-8'>
                        <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                        <meta http-equiv='X-UA-Compatible' content='ie=edge'>

                        <style>
                            * {
                                margin: 0;
                                padding: 0;
                                -webkit-box-sizing: border-box;
                                box-sizing: border-box
                            }
.h-wrapper {
  direction: rtl;
}
                            body {
                                font-size: 62.5%;
                                padding: 4rem 3rem;
                                max-width: 100%;
                                font-family: DejaVu Sans, sans-serif;
                                unicode-bidi: bidi-override !important;
                                direction: unset !important
                            }
 .content {
            font-size: 1.2rem;

            color: #7a7a7a;
            font-weight: bold;
        }
            .label {
            text-align: right;
            color: #7a7a7a;
            font-size: 1.2rem;
            font-weight: 400;
            line-height: 2.3rem
        }
                            .text-right {
                                text-align: right
                            }

                            .left-align {
                                width: 25%
                            }

                            .right-align {
                                width: 75%
                            }

                            .float-left {
                                float: left
                            }

                            .float-right {
                                float: right
                            }

                            .clear-both {
                                clear: both
                            }

                            .pdf-logo-holder {
                                width: 13.65rem;
                                height: auto;
                                overflow: hidden;
                                margin: 0 auto
                            }

                            .pdf-logo-holder>img {
                                width: 90%;
                                height: auto;
                                -o-object-fit: contain;
                                object-fit: contain
                            }

                            header.pdf-header {
                                border-bottom: .15rem solid #7a7a7a
                            }

                            header.pdf-header h3.logo-slogan {
                                font-size: 1.5rem;
                                line-height: 2.4rem;
                                color: #999903;
                                text-align: center;
                                font-weight: bold;
                                margin: 1rem 0
                            }

                            header.pdf-header h3.title {
                                font-size: 2.2rem;
                                line-height: 2.8rem;
                                color: #999903;
                                font-weight: bold;
                                margin-left: 1rem;
                                margin-bottom: 0
                            }

                            header.pdf-header p.paragraph {
                                color: #7a7a7a;
                                font-size: 1.7rem;
                                font-weight: 400;
                                line-height: 3rem;
                                margin-left: 1rem
                            }

                            section.pdf-invoice .order-details-card {
                                margin-bottom: 1.5rem
                            }

                            section.pdf-invoice .order-details-card h3.card-title {
                                font-size: 2rem;
                                line-height: 2.6rem;
                                color: #7a7a7a;
                                font-weight: bold
                            }

                            section.pdf-invoice .order-details-card .card-items {
                                padding: 0.1rem 0
                            }

                            section.pdf-invoice .order-details-card .card-items .item {
                                border-bottom: 1.5px dotted #7a7a7a;
                                padding: 0.1rem 0;
                                color: #7a7a7a;
                                font-size: 1.2rem;
                                font-weight: 400;

                            }

                            .item p{
                                display:inline-block;
                                width: 100%;
                                float:left;
                                margin:0;
                            }

                            .valueP{
                                text-align :right;
                                font-weight:bold;
                            }

                            section.pdf-invoice .additional-card {
                                margin: 1.5rem 0
                            }

                            section.pdf-invoice .additional-card h3.card-title {
                                font-size: 1.2rem;
                                line-height: 2.6rem;
                                color: #7a7a7a;
                                text-align: center;
                                font-weight: bold;
                                margin-bottom: 1.5rem
                            }

                            section.pdf-invoice .additional-card .card-box {
                                max-width: 50%;
                                border: .15rem solid #7a7a7a;
                                padding: 1rem;
                                margin: 0 auto
                            }

                            section.pdf-invoice .additional-card .card-box .card-p {
                                width: 45%
                            }

                            section.pdf-invoice .additional-card .card-box .card-c {
                                width: 10%
                            }

                            section.pdf-invoice .additional-card .card-box h3.title-1 {
                                font-size: 1.7rem;
                                line-height: 2.4rem;
                                color: #7a7a7a;
                                font-weight: bold
                            }

                            .text-center {
                                text-align: center
                            }

                            .text-right {
                                text-align: right
                            }

                            section.pdf-invoice p.notes {
                                text-align: center;
                                color: #7a7a7a;
                                font-size: 1.4rem;
                                font-weight: 400;
                                line-height: 2.3rem
                            }

                            section.pdf-message-collection .message-counter-head {
                                border-top: .1rem solid #7a7a7a;

                            }

                            section.pdf-message-collection .message-counter-head p.text-1 {
                                color: #7a7a7a;
                                font-size: 1.6rem;
                                font-weight: 200;

                            }

                            section.pdf-message-collection .message-counter-head p.text-2 {
                                text-align: center;
                                color: #7a7a7a;
                                font-size: 1.4rem;
                                font-weight: 200;

                            }

                            section.pdf-message-collection .message-body h3.subjects {
                                font-size: 1.8rem;
                                line-height: 2.4rem;
                                color: #7a7a7a
                            }

                            section.pdf-message-collection .message-body h3.sub-subjects {
                                font-size: 1.5rem;
                                line-height: 2rem;
                                color: #7a7a7a
                            }

                            section.pdf-message-collection .message-body .content {
                                color: #7a7a7a;
                                font-size: 1.7rem;
                                font-weight: 400;
                                line-height: 2.3rem
                            }

                            .pdf-page-footer {
                                width: 100%
                            }

                            .pdf-page-footer .title {
                                text-align: center
                            }

                            footer.pdf-page-footer h3.title {
                                font-size: 1.4rem;
                                line-height: 2.3rem;
                                color: #7a7a7a;
                                text-align: center;
                                font-weight: bold
                            }

                            .pdf-page-header {
                                width: 100%;
                                padding: 0;
                                margin: 1rem auto
                            }

                            .pdf-page-header .pdf-repeat {
                                width: 33.33%
                            }

                            header.pdf-page-header h3.title-1 {
                                font-size: 1.5rem;
                                line-height: 2.4rem;
                                color: #999903;
                                font-weight: bold
                            }

                            header.pdf-page-header h3.title-2 {
                                font-size: 1.5rem;
                                line-height: 2.4rem;
                                color: #7a7a7a;
                                text-align: center;
                                font-weight: bold
                            }

                            header.pdf-page-header h3.title-3 {
                                font-size: 1.5rem;
                                line-height: 2.4rem;
                                color: #999903;
                                text-align: right;
                                font-weight: bold
                            }

                            .message-counter-head {
                                width: 100%
                            }

                            .numberDiv{
                                width: 10%;
                                padding-top:5px;
                                padding-bottom:5px;
                                float:left;
                            // background-color:red;
                            }

                            .subjectDiv{
                                display:inline-block;
                                width: 70%;
                                float:left;
                                text-align:center;
                                  padding-top:5px;
                                padding-bottom:5px;
                                // background-color:green;

                            }

                            .dateDiv{
                                width: 20%;
                                text-align:right;
                                  padding-top:5px;
                                padding-bottom:5px;
                                // background-color:brown;
                            }

                            .message-counter-head .ram {
                                width: 50%
                            }
                        </style>
                        <style>
                            body {
                                unicode-bidi: bidi-override !important;
                                direction: unset !important;
                            }
                            .highlight {
                                background-color: yellow;
                            }
                        </style>
                    </head>";
        $html .= "<body dir='" . ($language == 'he' ? 'rtl' : '') . "'>
                    <header class='pdf-header'>";


        $html .= $display;

        $html .= "  </header>
                    <section class='pdf-invoice'>" . $display2 . "</section>
                    <section class='pdf-message-collection'>";

        return $html;
    }

    protected function getFooterHtml()
    {
        $html = "</section>
                </body>
            </html>";

        return $html;
    }

    public function createPdfUsingSnappy($data, $emailTo, $language, $inc_array, $token = null, object | null $order = null, $parseOneMessage = null)
    {
        info("From CREATE PDF from: " . $parseOneMessage["from"]);
        info('Language in PDF Service:' . $language);

        ob_start();
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', -1);
        set_time_limit(-1);
        ini_set("pcre.backtrack_limit", 500000000000);

        $inc_array = is_array($inc_array) && !empty($inc_array) ? $inc_array : [];


        $datePrefix      = now()->format('d_m_Y_h_i_s_v');
        $dateAt          = now()->format('d/m/Y');
        $timeAt          = now()->format('H:i:s');
        $authUser        = $order->from_email ?? LaravelGmail::user();
        $username        =  substr($authUser, 0, strpos($authUser, "@"));
        $file_path       = $username . '_' . $datePrefix . '_LegalPDF.pdf';
        $emailToUsername = substr($emailTo, 0, strpos($emailTo, "@"));
        $logo            = url('web/assets/img/resize-image/logo-192x192.png');

        $access_token = is_null($token) ? LaravelGmail::getToken()["access_token"] : $token;


        $text1 = "";
        $text2 = "";

        if ($language == 'he') {
            info("Inside Hebru Block in create PDF");
            $text1 = "מאובטח, מהיר ומעוצב";
            $text2 = 'מחלץ התכתבויות דוא"ל ל- PDF';
        } else {
            $text1 = "FAST & SECURED";
            $text2 = "Emails and Chats Extractor";
            info("Inside English Block in create PDF");
        }

        // Define the header HTML
        $headerHtml = '
        <header class="pdf-page-header" style="border-bottom:1.2px solid #7a7a7a">
            <div style="width:8%;float:left;" >
            <img width="40" height="40" src="' . $logo . '" loading="lazy" alt="logo">
            </div>
            <div style="width:67%;float:left;">
            <p><span style="color: #c3b019de;font-weight: bold;font-size: 1.6rem;">LegalPDF.co</span><br><span style="color:#7a7a7a;font-weight:bold">' . $text2 . '</span></p>

            </div>
            <div style="width:21%;float:right;text-align:right!important;">
                 <p><span style="text-align:right!important;color:#7a7a7a">' . $dateAt . ' at ' . $timeAt . '</span><br><span style="color: #c3b019de;font-size: 1.2rem;font-weight: bold;">' . $text1 . '</span></p>
            </div>
        </header>';
        $headerHtmlHeb = '
        <header class="pdf-page-header" style="border-bottom:1.2px solid #7a7a7a; direction: rtl;">
            <div style="width:8%;float:right;">
                <img width="40" height="40" src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '" loading="lazy" alt="logo">
            </div>
            <div style="width:67%;float:left;">
                <p>
                    <span style="color: #c3b019de;font-weight: bold;font-size: 1.6rem;">LegalPDF.co</span>
                    <br><span style="color:#7a7a7a;font-weight:bold">' . htmlspecialchars($text2, ENT_QUOTES, 'UTF-8') . '</span>
                </p>
            </div>
            <div style="width:21%;float:left;text-align:left;">
                 <p><span style="text-align:left;color:#7a7a7a">' . htmlspecialchars($dateAt, ENT_QUOTES, 'UTF-8') . ' ב-' . htmlspecialchars($timeAt, ENT_QUOTES, 'UTF-8') . '</span><br><span style="color: #c3b019de;font-size: 1.2rem;font-weight: bold;">' . htmlspecialchars($text1, ENT_QUOTES, 'UTF-8') . '</span></p>
            </div>
        </header>';

        // Define the footer HTML
        $footerHtml = '
            <footer class="pdf-page-footer">
                <h3 class="title">- Page [page] of [topage] -</h3>
            </footer>';

        // Set the header HTML
        if ($language == 'he') {
            $header = $headerHtmlHeb;
        } else {
            $header = $headerHtml;
        }

        // Set the footer HTML
        $footer = $footerHtml;


        $countData = count($data);

        $html = $this->getHeaderHtml(
            $logo,
            $username,
            $authUser,
            $emailTo,
            $dateAt,
            $timeAt,
            $language,
            $countData,
            $parseOneMessage
        );

        $num = 0;

        $order = $order ?? Order::query()->findOrFail(session()->get('order_id'));

        // if (isset($data['messages'])) {
        if (isset($data)) {
            foreach ($data as $message) {
                $messageId = $message['id'];

                $singleMessage = (new OrderMessageService())->fetchSingleMessage($messageId, $access_token);
                $messageDetails = $singleMessage;

                $num++;

                $parseMessage = $this->parseMessage($messageDetails, $num);

                $eData = $parseMessage;

                $viewData = compact(
                    'eData',
                    'logo',
                    'username',
                    'emailToUsername',
                    'authUser',
                    'emailTo',
                    'dateAt',
                    'timeAt',
                    'language',
                    'inc_array',
                    'num'
                );

                $html .= view('web.pdf.pdf_view', $viewData)->render();

                $progress = 25 + floor(($num / $countData) * 70);
                $orderKey = $order->id;
                $redisKey = "job_progress_{$orderKey}";
                Redis::set($redisKey, json_encode(['status' => 'processing', 'progress' => $progress, 'fetching_messages' => $num."/".$countData]));
            }
        }

        try {
            $path = 'public/' . $file_path;

            // Render the header view to HTML
            $headerHtml = view('new_pdf_header_design',compact('language','dateAt','timeAt'))->render();

            $headerFileName = "header{$order->id}.html";

            // Save the rendered header HTML to a temporary file
            $headerPath = storage_path($headerFileName);

            File::put($headerPath, $headerHtml);

//            $headerUrl = url('/get-pdf-header',[
//                'language'=>$language,
//                'dateAt'=>$dateAt,
//                'timeAt'=>$timeAt
//            ]);

            $pdf = SnappyPdf::loadHTML($html)
                ->setTemporaryFolder(storage_path())
                ->setPaper('a4')
                ->setOption('encoding', 'UTF-8')
                ->setOption('header-html', $headerPath)
                ->setOption('margin-top', '20mm')
                ->setOption('header-spacing', 5)
                ->setOption('no-stop-slow-scripts', true)
                ->setOption('javascript-delay', 3000) // 3 seconds delay to ensure the header is loaded
                ->setOption('disable-smart-shrinking', true);
//                ->setOptions([
//                    'header-html' => view('new_pdf_header_design',compact('logo','text1','text2','dateAt','timeAt','language'))->render(),
//                    'footer-html' => view('new_pdf_footer_design'),
//                ]);

            Storage::put($path, $pdf->output());

            // delete the temporary header file
            File::delete($headerPath);

            $orderKey = $order->id;
            $redisKey = "job_progress_{$orderKey}";
            Redis::set($redisKey, json_encode(['status' => 'processing', 'progress' => 100, 'pdf' => "Done"]));

        } catch (\Exception $e) {
            info("PDF generation error: " . $e->getMessage());
            // Optionally, handle the error (e.g., send a notification or retry)
        }

        (new SessionLab())->setDownloadFile($file_path);

        $order->update([
            "pdf_file" => $file_path, "total_pages" => $num
        ]);

        return $file_path;
    }
}
