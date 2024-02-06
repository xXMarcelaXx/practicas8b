<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

  <title>Login</title>
</head>
<body>
  <div class="wrapper fadeInDown">
    <div id="formContent">
      <!-- Tabs Titles -->
      <!-- Icon -->
      <div class="fadeIn first">
      </div><br><br>
      <!-- Login Form -->
      <form method="POST" action="{{route('guardar')}}">
        @csrf
        <p>Login</p>
        <input type="text"  id="email" class="fadeIn third" name="email" placeholder="Correo">
        <small class="form-text text-danger">
        @if($errors->has('email'))
        {{$errors->first('email')}}
        @endif
        </small>
        <input type="password" id="password" class="fadeIn third" name="password" placeholder="Contraseña">
        <small class="form-text text-danger">
        @if($errors->has('password'))
        {{$errors->first('password')}}
        @endif
        </small>
        <input type="submit" class="fadeIn fourth" value="registrar">

      </form>
    </div>
  </div>
</body>

</html>