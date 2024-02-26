<!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

        <title><?= isset($code) ? $code : '404 Not Found' ?></title>

        <!-- Google font -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:400,700" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Passion+One:900" rel="stylesheet">

        <!-- Custom stlylesheet -->
        <style media="all">/* cyrillic-ext */
            * {
            -webkit-box-sizing: border-box;
                    box-sizing: border-box;
            }

            body {
                padding: 0;
                margin: 0;
            }

            #notfound {
                position: relative;
                height: 100vh;
            }

            #notfound .notfound {
                position: absolute;
                left: 50%;
                top: 50%;
                -webkit-transform: translate(-50%, -50%);
                    -ms-transform: translate(-50%, -50%);
                        transform: translate(-50%, -50%);
            }

            .notfound {
                max-width: 710px;
                width: 100%;
                padding-left: 190px;
                line-height: 1.4;
            }

            .notfound .notfound-404 {
                position: absolute;
                left: 0;
                top: 0;
                width: 150px;
                height: 150px;
            }

            .notfound .notfound-404 h1 {
                font-family: 'Passion One', cursive;
                color: #00b5c3;
                font-size: 150px;
                letter-spacing: 15.5px;
                margin: 0px;
                font-weight: 900;
                position: absolute;
                left: 50%;
                top: 50%;
                -webkit-transform: translate(-50%, -50%);
                    -ms-transform: translate(-50%, -50%);
                        transform: translate(-50%, -50%);
            }

            .notfound h2 {
                font-family: 'Raleway', sans-serif;
                color: #292929;
                font-size: 28px;
                    font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 2.5px;
                margin-top: 0;
            }

            .notfound p {
                font-family: 'Raleway', sans-serif;
                font-size: 14px;
                font-weight: 400;
                margin-top: 0;
                margin-bottom: 15px;
                color: #333;
            }

            .notfound a {
                font-family: 'Raleway', sans-serif;
                font-size: 14px;
                text-decoration: none;
                text-transform: uppercase;
                background: #fff;
                display: inline-block;
                padding: 15px 30px;
                border-radius: 40px;
                color: #292929;
                font-weight: 700;
                -webkit-box-shadow: 0px 4px 15px -5px rgba(0, 0, 0, 0.3);
                        box-shadow: 0px 4px 15px -5px rgba(0, 0, 0, 0.3);
                -webkit-transition: 0.2s all;
                transition: 0.2s all;
            }

            .notfound a:hover {
                color: #fff;
                background-color: #00b5c3;
            }

            @media only screen and (max-width: 480px) {
                .notfound {
                    text-align: center;
                }
                .notfound .notfound-404 {
                    position: relative;
                    width: 100%;
                    margin-bottom: 15px;
                }
                .notfound {
                    padding-left: 15px;
                    padding-right: 15px;
                }
            }

            ::-webkit-scrollbar {
                -webkit-appearance: none;
                width: 7px;
            }

            ::-webkit-scrollbar-thumb {
                border-radius: 4px;
                background-color: rgba(0, 0, 0, .5);
                box-shadow: 0 0 1px rgba(255, 255, 255, .5);
            }

            #details {
                height:180px;
                overflow-x: hidden;
                overflow-y: auto;
            }

        </style>

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
            <![endif]-->

    </head>

    <body>

        <div id="notfound">
            <div class="notfound">
                <div class="notfound-404">
                    <h1>:|</h1>
                </div>
                <h2><?= isset($code) ? $code : '404 Not Found' ?></h2>
                <p><?= isset($message) ? $message : 'The page you are looking for might have been removed, had its name changed or is temporarily unavailable.' ?></p>
                <p id="details"><?= isset($details) ? $details : '';?></p>
                <a href="/">home page</a>
            </div>
        </div>

        <script>
            var objDiv = document.getElementById("details");
            objDiv.scrollTop = objDiv.scrollHeight;
        </script>

    </body>

</html>
