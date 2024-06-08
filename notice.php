<?php
    // Update the path below to your autoload.php,
    // see https://getcomposer.org/doc/01-basic-usage.md
    require_once '/path/to/vendor/autoload.php';
    use Twilio\Rest\Client;

    $sid    = "ACde1b726924d5df8fde1fb8b2c2cda27d";
    $token  = "cd34a3fa748520dd2b2d88d6ea3a9dd4";
    $twilio = new Client($sid, $token);

    $message = $twilio->messages
      ->create("+8201048158176", // to
        array(
          "from" => "+17064683044",
          "body" => "산사태발령"
        )
      );

print($message->sid);
?>


