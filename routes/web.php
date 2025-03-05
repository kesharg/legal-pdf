<?php

use App\Http\Controllers\Web\OrderRefundController;
use App\Http\Controllers\Web\RegenerateFailedPdfController;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\CustomAuthController;
use App\Http\Controllers\Admin\Partner\PartnerController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\PagesController;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use App\Http\Controllers\Google\LoginController;
use App\Http\Controllers\Web\DownloadController;
use App\Http\Controllers\Microsoft\AuthController;
use App\Http\Controllers\Microsoft\EmailController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Google\OrderMessageController;
use App\Http\Controllers\PaymentGateway\StripePaymentController;
use App\Http\Controllers\BasicController;
use App\Http\Controllers\PdfDesignController;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use App\Http\Controllers\Web\JobProgressController;
use App\Http\Controllers\JsonToPdfController;
use App\Utils\SessionLab;


Route::get('/redis-test', function () {
    try {
        Redis::set('name', 'Laravel');
        return Redis::get('name');
    } catch (Exception $e) {
        return 'Could not connect to Redis: ' . $e->getMessage();
    }
});

Route::get('/setSession/{lang}', function ($lang) {
    try {
        setValueSession($lang);
        echo json_encode(["success" => true, "value" => $lang]);
        exit;
    } catch (Exception $e) {
        echo json_encode(["success" => false, "value" => "error"]);
        exit;
    }
});


Route::get('/setLangSession/{lang}/{direction}', function ($lang, $direction) {
    try {
        session()->put("lang", $lang);
        session()->put("lang-direction", $direction);
        session()->save();
        return json_encode(["success" => true, "value" => $lang, "direction" => $direction]);
    } catch (Exception $e) {
        return json_encode(["success" => false, "value" => "error"]);
    }
});

Route::get('/job-progress/{orderId}', function ($orderId) {
    $order = Order::query()->findOrFail($orderId);

    if ($order->status == "Generating" || $order->status == "Done") {
        $progress = Redis::get("job_progress_{$orderId}");
        if ($progress) {
            return response()->json(json_decode($progress, true));
        }
    }

    return response()->json([], 200);
});

Route::get("/reboot", function (Request $request) {
    \Artisan::call("config:clear");
    \Artisan::call("cache:clear");
    \Artisan::call("view:clear");
    \Artisan::call("route:clear");

    if ($request->has("jobRestart")) {
        \Artisan::call("queue:restart");
    }

    if ($request->has("keyGenerate")) {
        \Artisan::call("key:generate");
    }

    return redirect("/");
});

Route::get('/oauth/gmail/login/{email?}', function ($email = null) {

    session()->remove('gmail_session');
    session()->save();

    if ($email){
        session()->put('gmail_requested_login_email', $email);
        session()->save();
        LaravelGmail::setLoginHint($email);
    }

    return LaravelGmail::redirect();


})->name('oauthGoogle')->middleware('XssSanitizer');

Route::get('/oauth/gmail/callback', function () {

    try {

        session()->remove('gmail_session');
        session()->save();

        LaravelGmail::deleteAccessToken();

        $makeToken = LaravelGmail::makeToken();  // Generate the new Gmail token

        if (session()->has('gmail_requested_login_email') && session('gmail_requested_login_email') != $makeToken['email']) {
            session()->put('email_not_match', true);
            session()->remove('gmail_requested_login_email');
            session()->remove('gmail_session');
            session()->save();
            return redirect('/');
        }

        $data = getSessionDataFromRedis();
        $cookieLifetime = 60 * 24 * 30;  // 30 days

        if ($data) {
            // Check if 'main_token' exists and update accordingly
            if (isset($data['main_token']) && !empty($data['main_token'])) {
                if(isset($data['microsoft_token'])){
                    $data['pdf_token'] = $makeToken;
                }
                elseif($data['main_token']['email'] == $makeToken['email']) {
                    // If the email matches, update the main_token
                    $data['main_token'] = $makeToken;
                } else {
                    $data['pdf_token'] = $makeToken;  // Store as pdf_token if main_token email differs
                }

            } else {
                // If no main_token exists, set the newly generated token as the main_token
                $data['main_token'] = $makeToken;
            }


        }else{
            $data['main_token'] = $makeToken;
        }

        setSessionDataInRedis($data);


        $user = LaravelGmail::user();
        // Store the email in the session
        session()->put([
            "yourEmail" => $user
        ]);

       if(isset($data['main_token']) && isset($data['main_token']['email'])){
        session()->put('gmail_session',$data['main_token']);
        session()->save();
       }
    } catch (\Throwable $e) {
        dd($e);
    }

    return redirect()->to('/');
})->middleware('XssSanitizer');


//Route::get('/oauth/gmail/callback',[HomeController::class,'gmailUserRegister'])->middleware('XssSanitizer');

Route::get('/oauth/gmail/logout', function () {
    try {
        // Get the user's auth_key from the cookie
        $authKey = request()->cookie('auth_key');

        // If an auth_key exists, remove the corresponding session data from Redis
        if ($authKey) {
            $redisKey = "gmail_sessions:{$authKey}";

            // Delete the session data from Redis
            Redis::del($redisKey);
        }

        // Clear the auth_key cookie by setting it to expire immediately
        cookie()->queue(cookie('auth_key', '', -1));  // Set the cookie to expire immediately

        // Perform Gmail logout
        LaravelGmail::logout();  // It will throw an exception if something goes wrong

        session()->invalidate();

        // Redirect the user to the home page after successful logout
        return redirect()->to('/');

    } catch (\Exception $e) {
        // Log the exception for debugging
        Log::error('Gmail logout failed: ' . $e->getMessage());

        // Redirect back with an error message if logout fails
        return redirect()->to('/');
    }
})->middleware('XssSanitizer');

Route::get('/test', [BasicController::class, 'test'])->middleware('XssSanitizer');
Route::get('/sms', [BasicController::class, 'sms'])->middleware('XssSanitizer');
Route::get('/download/pdf/{id}', [BasicController::class, 'downloadPdfFile'])->name("downloadPdfFile")->middleware('XssSanitizer');

Route::get('/email/verify', function () {
    return view('dashboard.auth.verify');
})->middleware('auth')->name('verification.notice');
// Email verification handler routes
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('/email/resend', [VerificationController::class, 'resend'])->middleware(['auth', 'throttle:6,1'])->name('verification.resend');

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
    Route::get('/otp/verify', [VerificationController::class, 'show'])->name('otp.notice');
    Route::post('/send-otp', [VerificationController::class, 'sendOtp'])->name('send.otp');
    Route::post('/verify-otp', [VerificationController::class, 'verifyOtp'])->name('verify.otp');
    Route::get('/order', [\App\Http\Controllers\Admin\DashboardController::class, 'order'])->name('order');
});

Route::group([
    'as' => 'web.'
], function () {
    Route::controller(LoginController::class)->group(function () {
        Route::post('/generate/gmail/extract', 'generate')->name('generate')->middleware('XssSanitizer');
        Route::post('/generate/gmail/extract/again', 'generateAgain')->name('generate.again')->middleware('XssSanitizer');
    });


    Route::controller(DownloadController::class)->group(function () {
        Route::post('dispatch-job', 'dispatchJobOrder')->name('dispatchJobOrder')->middleware('XssSanitizer');
        Route::get('/download/{file}', 'download')->name('download')->middleware('XssSanitizer');
        Route::delete('/destroy/{file?}', 'destroy')->name('destroy')->middleware('XssSanitizer');
    });

    Route::controller(PagesController::class)->group(function () {
        Route::get('pages/{slug}', 'show')->name('page')->middleware('XssSanitizer');
    });

    //TODO::Discussion Required, Code Repeated here
    // Route::get('/', function () {
    //     return view('web.pages.homepage');
    // })->middleware('XssSanitizer');

    Route::group([
        'as' => 'microsoft.'
    ], function () {
        Route::controller(AuthController::class)->group(function () {
            Route::get('/microsoft/signin', 'signin')->name('signin')->middleware('XssSanitizer');
            Route::get('/microsoft/callback', 'callback')->name('callback')->middleware('XssSanitizer');
            Route::get('/microsoft/signout', 'signout')->name('signout');
        });

        Route::controller(EmailController::class)->group(function () {
            Route::post('/microsoft/outlook', 'generate')->name('generate')->middleware('XssSanitizer');
        });
    });
});

Route::post("/update-order-notify-via", [OrderMessageController::class, "updateOrderNotifyVia"])->name("updateOrderNotifyVia");

/*stripe route*/
Route::get('payment', [StripePaymentController::class, 'showPaymentForm'])->name('payment.form');
Route::post('create-checkout-session', [StripePaymentController::class, 'createCheckoutSession'])->name('stripe.checkout.session');
Route::get('payment-success', [StripePaymentController::class, 'paymentSuccess'])->name('payment.success');
Route::get('payment-cancel', [StripePaymentController::class, 'paymentCancel'])->name('payment.cancel');

// Route::get('/', function () {
//     $session= session()->get('lang');
//     App::setlocale($session);
//     return view('web.pages.homepage');
// })->middleware('XssSanitizer');

Route::match(['get', 'post'], '/', [HomeController::class, 'index'],)->middleware('XssSanitizer');
// Route::match(['get', 'post'], 'login', [\App\Http\Controllers\Admin\Auth\CustomAuthController::class, 'login'])->name('login')->middleware('guest');
Route::get('/orders', [HomeController::class, 'index'])->name('home');
Route::get('/get-orders', [HomeController::class, 'getOrders'])->name('getOrders');
Route::get('signout', [\App\Http\Controllers\Admin\DashboardController::class, 'signOut'])->name('admin.signout');
Route::get('/generate-again/{id}', [HomeController::class, 'generateAgain'])->name('generate.again');
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard');
//Route::get('/register', [App\Http\Controllers\HomeController::class, 'register'])->name('register');

// Define the routes for PdfDesignController
Route::get('/pdf-design', [PdfDesignController::class, 'pdfDesign'])->name('pdf.design');
Route::get('/download-pdf/{id}', [PdfDesignController::class, 'downloadPdf'])->name('download.pdf');

Route::get('/download-done-pdf/{id}/{file_name}', [PdfDesignController::class, 'downloadDonePDF'])->name('download.done.pdf');

// json to pdf:
Route::get('/json-to-pdf', function () {
    return view('upload');
});

Route::post('/upload-json', [JsonToPdfController::class, 'uploadJson'])->name('upload_json');

Route::get('/download-pdf', function (Request $request) {
    $path = $request->input('path');
    return response()->download(storage_path("app/{$path}"));
})->name('download_pdf');

Route::get('/logs', function () {
    $date = now()->format('Y-m-d');

    $logFile = storage_path("logs/laravel-{$date}.log");

    if (!File::exists($logFile)) {
        return response()->json(['message' => 'Log file not found'], 404);
    }

    $logs = File::get($logFile);

    return response('<pre>' . e($logs) . '</pre>', 200)
        ->header('Content-Type', 'text/plain');
});

Route::get('/fail-order/{id}', function ($id) {
    $order = Order::query()->findOrFail($id);

    if ($order->status == "Generating") {
        $order->update([
            "status" => "Failed"
        ]);
    }

    return response()->json(['status' => 'success'], 200);
});

Route::get('/regenerate-failed-pdf/{id}', function ($id) {
    // Fetch the order with failed status and available JSON file
    $order = Order::where('id', $id)
        ->where('status', 'Failed')
        ->whereNotNull('msg_json_file')
        ->first();

    // If the order exists and the JSON file is present
    if ($order) {
        $filePath = storage_path('app/public/' . $order->msg_json_file);

        if (File::exists($filePath)) {
            // Decode the order's request field
            $request = json_decode($order->request, true);

            // Extract keywords and dispatch the PDF generation job
            $keywords = stringSplit($request['inc_keywords'] ?? '');

            \App\Jobs\WkHtmlToPdf\WkHtmlToPdfJob::dispatch($order, $keywords);

            return response()->json(['message' => 'Order placed successfully'], 200);
        }
    }

    // If the order is not valid for regeneration
    return response()->json(['message' => 'Order is not able to regenerate.'], 200);
});

Route::get('/register', [CustomAuthController::class, 'show'])->name('register');
Route::post('/register', [CustomAuthController::class, 'register']);

Route::get('/regenerate-order/{id}', function ($id) {
    // Fetch the order with failed status and available JSON file
    $order = Order::where('id', $id)
        ->whereNotNull('msg_json_file')
        ->first();

    // If the order exists and the JSON file is present
    if ($order) {
        $filePath = storage_path('app/public/' . $order->msg_json_file);

        if (File::exists($filePath)) {
            // Decode the order's request field
            $request = json_decode($order->request, true);

            // Extract keywords and dispatch the PDF generation job
            $keywords = stringSplit($request['inc_keywords'] ?? '');

            \App\Jobs\WkHtmlToPdf\WkHtmlToPdfJob::dispatch($order, $keywords);

            return response()->json(['message' => 'Order placed successfully'], 200);
        }
    }

    // If the order is not valid for regeneration
    return response()->json(['message' => 'Order is not able to regenerate.'], 200);
});

Route::post('/refund-request', [OrderRefundController::class, 'store'])->name('refund.request');
Route::post('/regenerate-failed-pdf', [RegenerateFailedPdfController::class, 'store'])->name('regenerate.failed.pdf');

Route::post('/set-user-timezone', function (Request $request) {
    //$request->timezone
    $request->session()->put('user_timezone', geoip(request()->ip())->timezone);
    return response()->json(['success' => true, "user_timezone" => $request->timezone]);
})->name('set_user_timezone');

Route::get('/validate/coupon', [BasicController::class, 'validate_coupon'])->name("validate_coupon");
Route::get('/notify/order/email', [BasicController::class, 'notify_order_email'])->name("notify_order_email");

Route::get('/get-redis-messages/{id}', function ($id) {
    $messages = Redis::get("order_messages_{$id}");
    return $messages;
});

Route::get('/get-order-status/{order_id}/', function ($order_id) {
    $data = Order::where("id", $order_id)
        ->select(["processing_status",  "fetch_start_at", "status", "total_messages"])
        ->first();

    if ($data != null) {
        $data->fetch_start_at = \Carbon\Carbon::parse($data->fetch_start_at);


        // Ensure the fields are returned as formatted date-time strings
        $data->fetch_start_at = $data->fetch_start_at->toISOString(); // Format as 'Y-m-d H:i:s'

        // Response with the formatted data
        $response = [
            "status" => true,
            "code" => 200,
            "msg" => "Get Data Successfully!",
            "data" => $data
        ];
    } else {
        $response = [
            "status" => false,
            "code" => 401,
            "msg" => "Order Not found!"
        ];
    }

    return response()->json($response);
});
