<?php
class AppExceptionHandler {
    public static function handle($error) {
        echo 'Oh noes! ' . $error->getMessage();
        // ...
    }
    // ...
}
 ?>
