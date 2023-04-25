<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="/img/favicon.ico" />

    <script type="text/javascript" src="{{ asset('js/jquery.min.js') }}"></script>

    <title>Job Deal</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <meta name="description" content=""/>
    <link rel="canonical" href="http://jobdeal.justraspberry.com/" />
    <meta property="og:locale" content="en_EN" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="" />
    <meta property="og:description" content="" />
    <meta property="og:url" content="http://jobdeal.justraspberry.com/" />
    <meta property="og:image" content="" />
    <meta property="og:image:secure_url" content="" />
    <meta property="og:site_name" content="Job Deal" />
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:description" content="" />
    <meta name="twitter:title" content="" />



</head>
<body>


<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Password reset</div>

                <div id="results">
                    <div class="card-body">
                        <form method="POST" action="">
                            @csrf

                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="form-group row">
                                <label for="newPassword" class="col-md-4 col-form-label text-md-right">New password</label>

                                <div class="col-md-6">
                                    <input id="newPassword" type="password" class="form-control" name="new_password" required>

                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="reNewPassword" class="col-md-4 col-form-label text-md-right">Repeat new password</label>

                                <div class="col-md-6">
                                    <input id="reNewPassword" type="password" class="form-control" name="re_new_password" required>
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-6 offset-md-4">
                                    <button type="submit" class="btn btn-primary" onclick="resetPassword(); return false;">
                                        Reset password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




<script>
    function resetPassword() {

        var CSRF_TOKEN = jQuery('meta[name="csrf-token"]').attr('content');

        var newPassword = jQuery('#newPassword').val();
        var reNewPassword = jQuery('#reNewPassword').val();

        if (newPassword === reNewPassword) {
            jQuery.ajax({
                type: 'POST',
                url: '/api/user/resetpassword',
                data:  {_token: CSRF_TOKEN, "password": newPassword, "token": '{{$token}}'},
                cache: 'false',
                dataType: 'html',
                beforeSend: function(){
                    jQuery('#results').html('working...');
                },

                success: function(response) {
                    jQuery('#results').html(response);
                },

                error: function(){
                    jQuery('#results').html('error');
                }
            });
        } else {
            alert('pass1 and pass2 are not same?');
        }



    }
</script>

</body>
</html>
