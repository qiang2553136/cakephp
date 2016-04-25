<?php
App::uses('ExceptionRenderer', 'Error');

class AppExceptionRenderer extends ExceptionRenderer {
    public function missingWidget($error) {
        echo 'Oops that widget is missing!';
    }
}


 ?>
