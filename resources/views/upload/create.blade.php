<!<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <form method="post" action="upload" enctype="multipart/form-data">
        附件类型
        <select name="file_type">
            <option>请选择</option>
            @foreach($file_type as $k=>$vo)
            <option value="{{$k}}" >{{$vo}}</option>
            @endforeach
        </select>
        <input type="file" name="file" multiple/>

        <input type="submit" value="提交"/>
    </form>
</body>
</html>