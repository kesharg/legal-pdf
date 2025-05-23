$(document).ready(function(){
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });



    var OUTLOOK_OVERLAY        = '#outlook-overlay-layout';
    var OUTLOOK_LOADING        = '#outlook-loading-dilog';
    var OUTLOOK_SUCCESS        = '#outlook-success-dilog';
    var OUTLOOK_FAILD          = '#outlook-faild-dilog';
    var OUTLOOK_DOWNLOAD       = '#outlook-download-link';
    var OUTLOOK_DOWNLOADLOGOUT = '#outlook-download-link-with-logout';
    var OUTLOOK_FORM           = '#outlook-filter-form';
    var OUTLOOK_FAILDMSG       = '#outlook-faild-dilog-msg';
    var OUTLOOK_DATA_COUNT     = '#outlook-data-count-value';
    var OUTLOOK_BTNGENERATE    = '#outlook-btn-generate';

    // Reset data count to 0 every time page reload
    $(OUTLOOK_DATA_COUNT).text(0);

    // Chacking if session has stored previous ajaxRequest
    if (hasOutlookSessionStored()) {
        continueOutlookAjaxRequest();
    }

    $('#retry-outlook-pdf-btn').click(function() {
        removeOutlookAjaxState();
        $(OUTLOOK_OVERLAY).removeClass('show-layer');
    });

    $('#generate-outlook-pdf-btn').click(function(){
        var coupon_no = document.getElementById("coupon_no_ms").value;
        document.getElementById("coupon_no_submit").value = coupon_no;

        var your_email_coupon = document.getElementById("your_email_coupon_ms").value;
        document.getElementById("your_email_coupon_submit").value = your_email_coupon;
    });

    // $(document).ready(function () {
    //
    //
    //     // Hide the popup when the close button is clicked
    //     $(".close").click(function () {
    //         $('#privacyPopup').removeClass('show-modal');
    //     });
    //
    //     // Hide the popup when the "I Accept" button is clicked
    //     $("#acceptPrivacy").click(function () {
    //         $('#privacyPopup').removeClass('show-modal');
    //         // Proceed with payment logic here
    //         alert("Proceeding with payment...");
    //     });
    //
    //     // Hide the popup when clicking outside the popup content
    //     $(window).click(function (event) {
    //         if ($(event.target).is("#privacyPopup")) {
    //             $('#privacyPopup').removeClass('show-modal');
    //         }
    //     });
    // });

    function hasOutlookSessionStored() {
        var request = JSON.parse(sessionStorage.getItem("ajaxOutlookRequest"));
        if(request){
            return true;
        } else {
            return false;
        }
    }

    // Function to retrieve saved AJAX request state and continue request
    function continueOutlookAjaxRequest() {
        // Retrieve saved AJAX request state and continue request with saved access token// Get the stored request data
        try {
            var request = JSON.parse(sessionStorage.getItem("ajaxOutlookRequest"));
        } catch (error) {
            console.log(error);
        }

        // Btn-loading
        $(OUTLOOK_BTNGENERATE).text('Processing...');
        $(OUTLOOK_BTNGENERATE).prop('disabled', true);

        //hide both button
        $("#generate-outlook-pdf-btn").hide();
        $("#retry-outlook-pdf-btn").hide();

        // Send the AJAX request using the stored data
        $.ajax({
            url         : request.url,
            method      : request.method,
            data        : request.data,
            dataType    : request.dataType,
            contentType : request.contentType,
            cache       : request.cache,
            processData : request.processData,
            async       : request.async,
            beforeSend  : function() {
                $(OUTLOOK_OVERLAY).addClass("show-layer");
                $(OUTLOOK_LOADING).addClass("show-layer");
            },
            success:function(response)
            {
                if (response.data_count === 0){
                    //show the retry btn
                    $("#retry-outlook-pdf-btn").show();
                }else {
                    // show the generate pdf btn
                    $("#generate-outlook-pdf-btn").show();
                }

                updateMsgCount(response.data_count)
                try {
                    // Empty count
                $(OUTLOOK_DATA_COUNT).text(0);
                // Set the value of n (the number to count up to)
                var n = response.data_count;
                var an = response.data_count + 1;
                // Set the duration of the animation (in milliseconds)
                var duration = 500; // 1000 / 2 = 500, .5sec

                // Start the animation using jQuery's animate() function
                $(OUTLOOK_DATA_COUNT).animate({ num: an }, {
                    duration: duration,
                    step: function() {
                        $(this).text(Math.floor(this.num));
                        if (this.num >= an) {
                            $(this).finish();
                        }
                    },
                    complete: function() {
                        this.num = 0;
                    }
                });

                setTimeout(function() {
                    $(OUTLOOK_DATA_COUNT).stop();
                    $(OUTLOOK_LOADING).removeClass("show-layer");
                    $(OUTLOOK_SUCCESS).addClass("show-layer");

                    if(response.download_status){
                        $(OUTLOOK_DOWNLOAD).attr('download-file', response.download_file);
                        $(OUTLOOK_DOWNLOADLOGOUT).attr('download-file', response.download_file);
                        updateMsgCount(response.data_count)
                    } else {
                        $(OUTLOOK_LOADING).removeClass("show-layer");
                        $(OUTLOOK_SUCCESS).removeClass("show-layer");
                        $(OUTLOOK_FAILD).addClass("show-layer");
                        $(OUTLOOK_FAILDMSG).empty().append(response.complete_message);
                        setTimeout(function() {
                            $(OUTLOOK_OVERLAY).removeClass("show-layer");
                            $(OUTLOOK_FAILD).removeClass("show-layer");
                        }, 3000);
                    }

                    $(OUTLOOK_FORM).trigger("reset");
                    $(OUTLOOK_BTNGENERATE).text('Generate');
                    $(OUTLOOK_BTNGENERATE).prop('disabled', false);
                    removeOutlookAjaxState();
                }, 550);
                } catch (error) {
                    console.log(error);
                }

            },
            error: function(response, status, errorThrown) {
                console.log(status);
                console.log(errorThrown);
                removeOutlookAjaxState();
                $(OUTLOOK_LOADING).removeClass("show-layer");
                var error = response.responseJSON;
                // console.log(response.responseJSON);
                if (typeof error === 'undefined') {
                    // Handle the undefined response
                    console.log('Response is undefined');
                    $(OUTLOOK_FAILD).addClass("show-layer");
                    $(OUTLOOK_FAILDMSG).empty().append("Unauthenticated!: User Deined Request.");
                    setTimeout(function() {
                        $(OUTLOOK_OVERLAY).removeClass("show-layer");
                        $(OUTLOOK_FAILD).removeClass("show-layer");
                    }, 3000);
                } else {
                    if(error.errors) {
                        if(Object.keys(error.errors).length !== 0) {
                            $(OUTLOOK_OVERLAY).removeClass("show-layer");
                            $.each(error.errors, function (key, value) {
                                var input = '#filter-form input[name=' + key + ']';
                                $(input + ' + small').text(value);
                                $(input).addClass('is-invalid');
                            });
                        }
                    } else if(error.exception) {
                        $(OUTLOOK_FAILD).addClass("show-layer");
                        $(OUTLOOK_FAILDMSG).empty().append(error.error_msg);
                        setTimeout(function() {
                            $(OUTLOOK_OVERLAY).removeClass("show-layer");
                            $(OUTLOOK_FAILD).removeClass("show-layer");
                        }, 3000);
                    } else if(error.catch) {
                        $(OUTLOOK_FAILD).addClass("show-layer");
                        $(OUTLOOK_FAILDMSG).empty().append(error.catch);
                        setTimeout(function() {
                            $(OUTLOOK_OVERLAY).removeClass("show-layer");
                            $(OUTLOOK_FAILD).removeClass("show-layer");
                        }, 3000);
                    } else if(error.error_msg) {
                        $(OUTLOOK_FAILD).addClass("show-layer");
                        $(OUTLOOK_FAILDMSG).empty().append(error.error_msg);
                        setTimeout(function() {
                            $(OUTLOOK_OVERLAY).removeClass("show-layer");
                            $(OUTLOOK_FAILD).removeClass("show-layer");
                        }, 3000);
                    } else {
                        $(OUTLOOK_FAILD).removeClass("show-layer");
                        setTimeout(function() {
                            $(OUTLOOK_OVERLAY).removeClass("show-layer");
                        }, 3000);
                    }
                }


                $(OUTLOOK_BTNGENERATE).text('Generate');
                $(OUTLOOK_BTNGENERATE).prop('disabled', false);
            }
        });
    }

    function removeOutlookAjaxState() {
        // Remove the "ajaxOutlookRequest" key from the session storage
        sessionStorage.removeItem("ajaxOutlookRequest");
    }

    $(OUTLOOK_FORM).on('submit', function(event){
        event.preventDefault();
        var url = $(this).attr('data-action');
        var user = $(this).attr('data-login-for');
        var pdfEmail = $(this).find('input[name="your_email"]').val();
        // Check if user is authenticated with Google OAuth
        if (isOutlookAuthenticated() === true  && user === pdfEmail) {
            // User is authenticated, send AJAX request
            sendOutlookAjaxRequest();
        } else {
            // User is not authenticated, redirect to Google OAuth authentication page
            saveOutlookAjaxState();
            // return false;
            redirectToMicrosoftOAuth();
        }

        // Function to check if user is authenticated with Google OAuth
        function isOutlookAuthenticated() {
            var value = $(OUTLOOK_FORM).attr('data-login-state');
            if(value === "1") { return true; } else { return false; }
        }

        // Function to save AJAX request state
        function saveOutlookAjaxState() {
            var formData = $(OUTLOOK_FORM).serialize();
            // Create an object to store the request data
            var request = {
                "url": url,
                "data": formData,
                "method": 'POST',
                "dataType": 'JSON',
                "contentType": 'application/x-www-form-urlencoded; charset=UTF-8',
                "cache": false,
                "processData": false,
                "async" : false,
            };

            // Store the request data in session storage
            sessionStorage.setItem("ajaxOutlookRequest", JSON.stringify(request));
        }

        // Function to redirect to Google OAuth authentication page
        function redirectToMicrosoftOAuth() {
            // Redirect user to Google OAuth authentication page
            var OUTLOOK_FORM = '#outlook-filter-form';
            var authUrl = $(OUTLOOK_FORM).attr('data-auth-url');
            window.location.href = authUrl;
        }

        // Function to send AJAX request with access token
        function sendOutlookAjaxRequest() {
            var form = $(OUTLOOK_FORM)[0]; // You need to use standard javascript object here
            var formData = new FormData(form);
            // Btn-loading
            $(OUTLOOK_BTNGENERATE).text('Processing...');
            $(OUTLOOK_BTNGENERATE).prop('disabled', true);

            //hide both button
            $("#generate-outlook-pdf-btn").hide();
            $("#retry-outlook-pdf-btn").hide();
            // Send the AJAX request with the access token
            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                dataType: 'JSON',
                contentType: false,
                cache: false,
                processData: false,
                async : false,
                beforeSend: function() {
                    // Btn-loading
                    $(OUTLOOK_BTNGENERATE).text('Processing...');
                    $(OUTLOOK_BTNGENERATE).prop('disabled', true);

                    $(OUTLOOK_OVERLAY).addClass("show-layer");
                    $(OUTLOOK_LOADING).addClass("show-layer");
                },
                success:function(response)
                {
                    if (response.data_count === 0){
                        //show the retry btn
                        $("#retry-outlook-pdf-btn").show();
                    }else {
                        // show the generate pdf btn
                        $("#generate-outlook-pdf-btn").show();
                    }

                    updateMsgCount(response.data_count)
                    // Empty count
                    $(OUTLOOK_DATA_COUNT).text(0);
                    // Set the value of n (the number to count up to)
                    var n = response.data_count;
                    var an = response.data_count + 1;
                    // Set the duration of the animation (in milliseconds)
                    var duration = n * 500; // 1000 / 2 = 500, .5sec

                    // Start the animation using jQuery's animate() function
                    $(OUTLOOK_DATA_COUNT).animate({ num: an }, {
                        duration: duration,
                        step: function() {
                            $(this).text(Math.floor(this.num));
                            if (this.num >= an) {
                                $(this).finish();
                            }
                        },
                        complete: function() {
                            this.num = 0;
                        }
                    });

                    setTimeout(function() {
                        $(OUTLOOK_LOADING).removeClass("show-layer");
                        $(OUTLOOK_SUCCESS).addClass("show-layer");
                        updateMsgCount(response.data_count)
                        if(response.download_status){
                            updateMsgCount(response.data_count)
                            $(OUTLOOK_DOWNLOAD).attr('download-file', response.download_file);
                            $(OUTLOOK_DOWNLOADLOGOUT).attr('download-file', response.download_file);
                            updateMsgCount(response.data_count)
                        }
                        else {
                            $(OUTLOOK_LOADING).removeClass("show-layer");
                            $(OUTLOOK_SUCCESS).removeClass("show-layer");
                            $(OUTLOOK_FAILD).addClass("show-layer");
                            $(OUTLOOK_FAILDMSG).empty().append(response.complete_message);
                            setTimeout(function() {
                                $(OUTLOOK_OVERLAY).removeClass("show-layer");
                                $(OUTLOOK_FAILD).removeClass("show-layer");
                            }, 3000);
                        }
                        updateMsgCount(response.data_count)
                        $(OUTLOOK_FORM).trigger("reset");
                        $(OUTLOOK_BTNGENERATE).text('Generate');
                        $(OUTLOOK_BTNGENERATE).prop('disabled', false);
                    }, n * 550);
                    updateMsgCount(response.data_count)
                },
                error: function(response) {
                    $(OUTLOOK_LOADING).removeClass("show-layer");
                    var error = response.responseJSON;
                    if(error.errors) {
                        if(Object.keys(error.errors).length !== 0) {
                            $(OUTLOOK_OVERLAY).removeClass("show-layer");
                            $.each(error.errors, function (key, value) {
                                var input = '#filter-form input[name=' + key + ']';
                                $(input + ' + small').text(value);
                                $(input).addClass('is-invalid');
                            });
                        }
                    } else if(error.exception) {
                        $(OUTLOOK_FAILD).addClass("show-layer");
                        $(OUTLOOK_FAILDMSG).empty().append(error.error_msg);
                        setTimeout(function() {
                            $(OUTLOOK_OVERLAY).removeClass("show-layer");
                            $(OUTLOOK_FAILD).removeClass("show-layer");
                        }, 3000);
                    } else if(error.catch) {
                        $(OUTLOOK_FAILD).addClass("show-layer");
                        $(OUTLOOK_FAILDMSG).empty().append(error.catch);
                        setTimeout(function() {
                            $(OUTLOOK_OVERLAY).removeClass("show-layer");
                            $(OUTLOOK_FAILD).removeClass("show-layer");
                        }, 3000);
                    } else if(error.error_msg) {
                        $(OUTLOOK_FAILD).addClass("show-layer");
                        $(OUTLOOK_FAILDMSG).empty().append(error.error_msg);
                        setTimeout(function() {
                            $(OUTLOOK_OVERLAY).removeClass("show-layer");
                            $(OUTLOOK_FAILD).removeClass("show-layer");
                        }, 3000);
                    } else {
                        $(OUTLOOK_FAILD).removeClass("show-layer");
                        setTimeout(function() {
                            $(OUTLOOK_OVERLAY).removeClass("show-layer");
                        }, 3000);
                    }

                    $(OUTLOOK_BTNGENERATE).text('Generate');
                    $(OUTLOOK_BTNGENERATE).prop('disabled', false);
                }
            });
        }
    });

    $(OUTLOOK_DOWNLOADLOGOUT).on('click', function(event) {
        event.preventDefault();
        var filename = $(this).attr('download-file');
        var routeDwn = $(this).attr('data-download-url');
        var routeDlte = $(this).attr('data-destroy-url');
        var logoutUrl = $(this).attr('data-user-logout');
        var urlDownload = routeDwn + '/' + filename;
        var urlDelete = routeDlte + '/' + filename;

        $.ajax({
            url: urlDownload,
            method: 'POST',
            xhrFields: {
                responseType: 'blob'
            },
            success: function(data) {
                var a = document.createElement('a');
                var url = window.URL.createObjectURL(data);
                a.href = url;
                a.download = filename;
                document.body.append(a);
                a.click();
                a.remove();

                setTimeout(function() {
                    $(OUTLOOK_SUCCESS).removeClass("show-layer");
                    $(OUTLOOK_OVERLAY).removeClass("show-layer");

                    // Delete the file after download
                    $.ajax({
                        url: urlDelete,
                        method: 'DELETE'
                    });

                    window.location.href = logoutUrl;
                }, 2000);
            }
        });
    });

    $(OUTLOOK_DOWNLOAD).on('click', function(event){
        event.preventDefault();
        var filename = $(this).attr('download-file');
        var routeDwn = $(this).attr('data-download-url');
        var routeDlte = $(this).attr('data-destroy-url');
        var urlDownload = routeDwn + '/' + filename;
        var urlDelete = routeDlte + '/' + filename;

        console.log(urlDownload);

        $.ajax({
            url: urlDownload,
            method: 'POST',
            xhrFields: {
                responseType: 'blob'
            },
            success: function(data) {
                var a = document.createElement('a');
                var url = window.URL.createObjectURL(data);
                a.href = url;
                a.download = filename;
                document.body.append(a);
                a.click();
                a.remove();

                setTimeout(function() {
                    $(OUTLOOK_SUCCESS).removeClass("show-layer");
                    $(OUTLOOK_OVERLAY).removeClass("show-layer");

                    // Delete the file after download
                    $.ajax({
                        url: urlDelete,
                        method: 'DELETE'
                    });
                }, 1000);
            }
        });
    });
});

function updateMsgCount(totalMessage){
    $(".data_count_ms").text(totalMessage);
}
