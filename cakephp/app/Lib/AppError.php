<?php
class AppError {
    /**
    第一个参数获取的是状态码
    第二个参数获取的是错误原因
    第三个参数获取的是错误类地址
    第四个参数获取的是错误出现行数
    */
    public static function handleError($code,$description, $file,
        $line , $context ) {

        echo '文件:'.$file."<br />".'出现错误:'.$description."<br />".'在第:'.$line.'行';

        exit();
    }
}

?>
