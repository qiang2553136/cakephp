<?php
class AppError {
    public static function handleError($code, $description, $file = null,
        $line = null, $context = null) {
        echo '系统异常'.$description;
        exit();
    }
}
 ?>
