<?php
/**
 * Mysql 导出到 Excel
 * @auther Sparkle
 */


//获取传入参数
$dbname = urlGet('dbname');
$host = urlGet('host');
$port = urlGet('port');
$user = urlGet('user');
$pwd = urlGet('pwd');
$inpsql = urlGet('inpsql');

if (!empty($dbname) && !empty($host) && !empty($port) && !empty($user) && !empty($pwd)) {
    //创建连接
    $mysqli = new mysqli($host, $user, $pwd, $dbname, $port);
    if (!$mysqli) {
        echo "database error";
        return;
    } else {
        //设置编码
        $mysqli->set_charset("utf8");

        //自定义查询sql
        if (empty($inpsql)) {
            //把表名都查出来
            $sql = "SELECT table_name, table_comment FROM INFORMATION_SCHEMA.TABLES WHERE table_schema ='" . $dbname . "'";
            $result = $mysqli->query($sql);
            if ($result === false) {
                //执行失败
                echo $mysqli->error;
                echo $mysqli->errno;
            } else {
                $data = "";
                $tableNames = $result->fetch_all(MYSQLI_BOTH);
                for ($i = 0; $i < count($tableNames); $i++) {
                    $sql = "SELECT
                              COLUMN_NAME              列名,
                              COLUMN_TYPE              数据类型,
                              DATA_TYPE                字段类型,
                              CHARACTER_MAXIMUM_LENGTH 长度,
                              COLUMN_KEY               主键,
                              IS_NULLABLE              是否可空,
                              COLUMN_DEFAULT           默认值,
                              COLUMN_COMMENT           备注
                            FROM
                              INFORMATION_SCHEMA.COLUMNS
                            WHERE
                              table_schema = 'hlw' AND table_name = '".$tableNames[$i]['table_name']."'";

                    $data .= "表名: \t".$tableNames[$i]['table_name']."\t 备注: \t".$tableNames[$i]['table_comment']."\t\n";
                    $data .= mysqlSelect($mysqli, $sql)."\t\n";
                }

                initExcel();
                writeExcel($data);
            }

        } else {
            $sql = $inpsql;

            $data = "";
            $data .= mysqlSelect($mysqli, $sql);
            initExcel();
            writeExcel($data);
        }

    }
    $mysqli->close();

} else {
    echo('
    <!DOCTYPE html>
    <html lang=\"zh\">
    <head>
        <meta charset=\"UTF-8\">
        <title>导出Mysql</title>
    </head>
    <body>
        <form action="" method="post">
            数据库名: <input name="dbname" value=""><br>
            数据库地址: <input name="host" value=""><br>
            数据库端口: <input name="port" value="3306"><br>
            数据库用户名: <input name="user" value=""><br>
            数据库密码: <input name="pwd" value=""><br>
            自定义查询sql (默认导出所有表结构): <br>
            <textarea name="inpsql" cols="40" rows="5"></textarea><br>
            <input type="submit" value="下载 sql.xls">
        </form>
    </body>
    </html>
    ');
}

function mysqlSelect($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if ($result === false) {
        //执行失败
        echo $mysqli->error;
        echo $mysqli->errno;
    } else {
        //行数
//            echo $result->num_rows . "行";
        //列数 字段数
//            echo $result->field_count . "列";
        //移动记录指针
        //$result->data_seek(1);//0 为重置指针到起始
        //获取数据
        $data = "";

        //循环表头
        foreach ($result->fetch_fields() as $val) {
            $data .= $val->name . "\t";
        }
        $data .= "\n";

        //循环数据
        while ($row = $result->fetch_row()) {
            for ($i = 0; $i < count($row); $i++) {
                $data .= $row[$i] . "\t";
            }
            $data .= "\n";
        }

        return $data;
    }
}

function urlGet($key)
{
    return empty($key) ? '' : empty($_POST[$key]) ? '' : $_POST[$key];
}

function initExcel()
{
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=sql.xls");
}

function writeExcel($data)
{
    $data = iconv('UTF-8', "GB2312//IGNORE", $data);
    exit($data);
}

?>
