<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>BIENVENIDO {{$name}}</h1>
    <h2>Verifica tu correo en la siguiente liga</h2>
    <a href="http://127.0.0.1:8000/verificarCorreo/{{$id}}">verificar</a>

</body>
</html>