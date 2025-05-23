<?php
namespace App\Services\PDF;

use App\Traits\File\FileUploadTrait;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;

class PDFService
{
    use FileUploadTrait;
    public function generatePdf()
    {
        $payloads = session("last_order_info");
        $email_address = $payloads['email_from'];
        $query = '(from:' . $email_address . ' OR to:' . $email_address . ')';

        $inc_query = "";
        $exc_query = "";
        $inc_array = [];

        $language = $payloads["language"];


        if($payloads['inc_keywords'])
        {
            $inc_array = stringSplit($payloads['inc_keywords']);
            $inc_query = createRawIncludeQuery($inc_array);
        }

        if($payloads['exc_keywords'])
        {
            $exc_array = stringSplit($payloads['exc_keywords']);
            $exc_query = createRawIncludeQuery($exc_array);
        }

        if($payloads['start_date']) { $aft_query = 'after:'.$payloads['start_date']; }
        if($payloads['end_date']) { $bef_query = 'before:'.$payloads['end_date']; }

        $messages = LaravelGmail::message()->raw($query)->raw($inc_query)->raw($exc_query)->raw($payloads['start_date'])->raw($payloads['end_date'])->preload()->all();

        // TODO::Create Directory
        $path = fileService()::DIR_PDF."/".$payloads["your_email"]; // uploads/pdf/maynuddinhsn@gmail.com

        $this->dynamicDirCreate(public_path($path));

        //Messages

        // TODO::PDF

        $file = "";
        if(count($messages) > 0) {
            // CREATE PDF
            $file = $this->createPDF($messages, $email_address, $language, $inc_array);
        }

        return $file;

//        dd($file);
//        dd($messages);
//        session()->flush();


//        return response()->download(asset("storage/app/".$file));
    }


    protected function createPDF($data, $emailTo, $language, $inc_array) {
        $date = now()->format('d_m_Y');
        $dateAt = now()->format('d/m/Y');
        $timeAt = now()->format('H:i:s');
        $authUser = laravelGmailUser();
        $username = substr($authUser, 0, strpos($authUser, "@"));
        $num_str = sprintf("%06d", mt_rand(1, 999999));
        $fileName = $username.'_'.$date.'_'.$num_str.'_LegalPDF';
        $file_path = $fileName . '.pdf';
        $emailToUsername = substr($emailTo, 0, strpos($emailTo, "@"));
        $logo = public_path('web/assets/img/resize-image/logo-192x192.png');

        // Define the maximum string length to process at a time
        $max_length = 2000;
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4-P']);

        if($language == 'en') {
            $text1 = "FAST & SECURED";
            $text2 = "Emails and Chats Extractor";
        } else {
            $text1 = "מאובטח, מהיר ומעוצב";
            $text2 = 'מחלץ התכתבויות דוא"ל ל- PDF';
        }

        // Define the header HTML
        $headerHtml = '
            <header class="pdf-page-header">
                <div class="pdf-repeat float-left">
                    <h3 class="title-1">'. $text1 .'</h3>
                </div>
                <div class="pdf-repeat float-left">
                    <h3 class="title-2">'. $text2 .'</h3>
                </div>
                <div class="pdf-repeat float-left">
                    <h3 class="title-3">legalpdf.co</h3>
                </div>
                <div class="clear-both"></div>
            </header>';

        // Define the footer HTML
        $footerHtml = '
            <footer class="pdf-page-footer">
                <h3 class="title">- Page {PAGENO} of {nbpg} -</h3>
            </footer>';

        // Set the header HTML
        $mpdf->SetHTMLHeader($headerHtml);

        // Set the footer HTML
        $mpdf->SetHTMLFooter($footerHtml);

        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        ini_set('max_execution_time', '120');
        set_time_limit(120);
        ini_set("pcre.backtrack_limit", "5000000");

        $countData = count($data);

        $html = $this->getHeaderHtml($logo, $username, $authUser, $emailTo, $dateAt, $timeAt, $language ,$countData);

        // dd($html);
        $mpdf->WriteHTML($html);

        $num = 1;

        foreach($data as $eData)
        {
            $html = view('web.pdf.pdf_view', compact('eData', 'logo', 'username', 'emailToUsername', 'authUser', 'emailTo', 'dateAt', 'timeAt', 'language', 'inc_array', 'num'))->render();
            // Chunk the HTML and write it to the PDF
            // $htmlChunks = str_split($html, $max_length);
            // dd($htmlChunks);
            // foreach ($htmlChunks as $chunk) {
            //     $mpdf->WriteHTML($chunk);
            // }
            $mpdf->WriteHTML($html);
            $num++;
        }

        $html .= $this->getFooterHtml();
        $mpdf->WriteHTML($html);

        // $mpdf->WriteHtml($html);
        $mpdf->Output(storage_path('app/public/' . $file_path), 'F');

        return $file_path;
    }


    protected function getHeaderHtml($logo, $username, $authUser, $emailTo, $dateAt, $timeAt, $language ,$countData) {

        $htmlHeb = "<div class='h-wrapper'>
                        <div class='left-align float-right'>
                            <div class='pdf-logo-holder'>
                            <img src='". $logo ."' alt='logo'>
                            </div>
                            <h3 class='logo-slogan'>מאובטח ומהיר</h3>
                        </div>
                        <div class='right-align float-right' style='text-align: right;'>
                            <h3 class='title text-right'>מחלץ התכתבויות דוא'ל ל- PDF</h3>
                            <div class='p-wrapper'>
                                <p class='paragraph text-right'>
                                    זהו מסמך רשמי מוכן לעיון ושימוש בבית המשפט.
                                    המסמך מכיל את כל המידע שהוזמן על ידי הלקוח הרשום מטה.
                                </p>
                                <p class='paragraph text-right'>
                                    למידע נוסף, אנא בקרו באתר co.legalpdf.
                                </p>
                            </div>
                        </div>
                        <div class='clear-both'></div>
                    </div>";
        $htmlEn = "<div class='h-wrapper'>
                        <div class='left-align float-left'>
                            <div class='pdf-logo-holder'>
                                <img src='". $logo ."' alt='logo'>
                            </div>
                            <h3 class='logo-slogan'>FAST & SECURED</h3>
                        </div>
                        <div class='right-align float-left'>
                            <h3 class='title'>Emails and Chats Extractor</h3>
                            <div class='p-wrapper'>
                                <p class='paragraph'>
                                    This is a formal document ready to be used, created by LegalPDF.
                                    The document contains all the requested information by the client.
                                </p>
                                <p class='paragraph'>
                                    For more information, please visit LegalPDF.com otherwise contact us
                                    on info@legalpdf.co or +44 208 5053 311
                                </p>
                            </div>
                        </div>
                        <div class='clear-both'></div>
                    </div>";



        $html2Heb = "<div class='order-details-card'>
                        <h3 class='card-title'>פרטי הזמנה</h3>
                        <div class='card-items'>
                            <div class='item'>
                                <span> ידי על ה</span>
                                <span>". $username ." | ". $authUser ."</span>
                            </div>
                            <div class='item'>
                                <span>תאריך </span>
                                <span>". $dateAt ." at ". $timeAt ."</span>
                            </div>
                            <div class='item'>
                                <span>פלטפורמה </span>
                                <span>Gmail </span>
                            </div>
                            <div class='item'>
                                <span>כמות תכתובות </span>
                                <span>". $countData ."</span>
                            </div>
                        </div>
                    </div>
                    <div class='additional-card'>
                        <h3 class='card-title'>המסמך הבא מכיל התכתבויות שבין הצדדים הבאים:</h3>
                        <div class='card-box'>
                            <div class='card-p float-left'>
                                <h3 class='title-1'>" . $authUser . " </h3>
                            </div>
                            <div class='card-c float-left'>
                                <h3 class='title-1 text-center'>עם</h3>
                            </div>
                            <div class='card-p float-left'>
                                <h3 class='title-1 text-right'>" . $emailTo . " </h3>
                            </div>
                            <div class='clear-both'></div>
                        </div>
                    </div>
                    <p class='notes'>התכתובות הבאות מסודרות מהחדש ביותר בראש המסמך, ועד הישן ביותר בתחתית המסמך</p>";

        $html2En = "<div class='order-details-card'>
                        <h3 class='card-title'>Order details</h3>
                        <div class='card-items'>
                            <div class='item'>
                                <span>Ordered by </span>
                                <span>" . $username . " | " . $authUser . " </span>
                            </div>
                            <div class='item'>
                                <span>Date: </span>
                                <span>" . $dateAt . " at " . $timeAt . " </span>
                            </div>
                            <div class='item'>
                                <span>Platform: </span>
                                <span>Gmail </span>
                            </div>
                            <div class='item'>
                                <span>Number of emails: </span>
                                <span>". $countData ."</span>
                            </div>
                        </div>
                    </div>
                    <div class='additional-card'>
                        <h3 class='card-title'>The following document contains emails between</h3>
                        <div class='card-box'>
                            <div class='card-p float-left'>
                                <h3 class='title-1'>". $authUser ."</h3>
                            </div>
                            <div class='card-c float-left'>
                                <h3 class='title-1 text-center'>and</h3>
                            </div>
                            <div class='card-p float-left'>
                                <h3 class='title-1 text-right'>". $emailTo ."</h3>
                            </div>
                            <div class='clear-both'></div>
                        </div>
                    </div>
                    <p class='notes'>* The following emails are in order from newest to oldest</p>";

        if($language == 'heb') {
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
                        <title>LegalPDF - PDF</title>
                        <style>
                            * {
                                margin: 0;
                                padding: 0;
                                -webkit-box-sizing: border-box;
                                box-sizing: border-box
                            }

                            body {
                                font-size: 62.5%;
                                padding: 4rem 3rem;
                                max-width: 100%;
                                font-family: DejaVu Sans, sans-serif;
                                unicode-bidi: bidi-override !important;
                                direction: unset !important
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
                                width: 15rem;
                                height: auto;
                                overflow: hidden;
                                margin: 0 auto
                            }

                            .pdf-logo-holder>img {
                                width: 100%;
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
                                padding: 1rem 0
                            }

                            section.pdf-invoice .order-details-card .card-items .item {
                                border-bottom: .15rem dashed #7a7a7a;
                                padding: 1rem 0;
                                display: flex;
                                color: #7a7a7a;
                                font-size: 1.6rem;
                                font-weight: 400;
                                line-height: 2.3rem
                            }

                            section.pdf-invoice .order-details-card .card-items .item span {
                                font-weight: bold
                            }

                            section.pdf-invoice .additional-card {
                                margin: 1.5rem 0
                            }

                            section.pdf-invoice .additional-card h3.card-title {
                                font-size: 1.8rem;
                                line-height: 2.6rem;
                                color: #7a7a7a;
                                text-align: center;
                                font-weight: bold;
                                margin-bottom: 1.5rem
                            }

                            section.pdf-invoice .additional-card .card-box {
                                max-width: 90%;
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
                                font-size: 1.7rem;
                                font-weight: 400;
                                line-height: 2.3rem
                            }

                            section.pdf-message-collection .message-counter-head {
                                border-top: .15rem solid #7a7a7a;
                                padding-top: 1rem;
                                margin-top: 2.5rem
                            }

                            section.pdf-message-collection .message-counter-head p.text-1 {
                                color: #7a7a7a;
                                font-size: 1.7rem;
                                font-weight: 400;
                                line-height: 2.3rem
                            }

                            section.pdf-message-collection .message-counter-head p.text-2 {
                                text-align: right;
                                color: #7a7a7a;
                                font-size: 1.7rem;
                                font-weight: 400;
                                line-height: 2.3rem
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
                                font-size: 1.6rem;
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
        $html .= "<body dir='" . ($language =='heb') ? 'rtl':'' . "'>
                    <header class='pdf-header'>";

        $html .= $display;

        $html .= "  </header>
                    <section class='pdf-invoice'>"
            . $display2 .
            "</section>
                    <section class='pdf-message-collection'>";

        return $html;
    }


    protected function getFooterHtml() {
        $html = "</section>
                </body>
            </html>";

        return $html;
    }


    protected function stringSplit($string) {
        // Split the string into an array using commas, semicolons, and spaces as delimiters
        $array = preg_split('/[\s,;]+/', $string);
        return $array;
    }

    protected function createRawIncludeQuery($array) {
        $string = "";

        if(!empty($array)) {
            $string = implode(" OR ", $array);
            return $string; // hello OR from OR dev
        }
        return $string;
    }

    protected function createRawExcludeQuery($array) {
        $string = "";

        if(!empty($array)) {
            $string = '-' . implode("-", $array);
            return $string; // -hello -from -dev
        }
        return $string;
    }

}
