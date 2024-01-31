<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificarCorreo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Redirect;

use PDOException;
use Exception;


class LoginController extends Controller
{
    public function registro()
    {
        return view('registro');
    }

    public function registrarUsuario(Request $request)
    {
        try{

 
        // Verificar el reCAPTCHA
        $recaptchaResponse =  $request->input('g-recaptcha-response');
        $recaptchaSecret = '6Leeyl4pAAAAAD_1KhmEwnITeh4-jD-lFMuv-ts0';

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $recaptchaSecret,
            'response' => $recaptchaResponse,
        ]);

        $recaptchaData = $response->json();

        if (!$recaptchaData['success']) {
            // El reCAPTCHA no se validó correctamente
            $message = 'Error en validacion de recaptcha formulario de registro';
            Log::warning($message);
            return response()->json([
                "Status" => 403,
                "msg" => "Acceso no autorizado",
            ], 403);
        }

        //Validaciones
        $validacion = Validator::make($request->all(), [
            'name' => ['required', 'string', 'regex:/^[A-Za-z\s]{4,50}$/'],
            'email' => 'required|email|unique:users',
            'password' => ['required', 'string', 'min:10', 'max:16', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{10,16}$/',],
            'telefono' => 'required|integer'
        ]);

        if ($validacion->fails()) {
            return redirect('registro')
                ->withErrors($validacion)
                ->withInput();
        }

        //Agregar usuario admin o visitante
        $tablaVacia = User::count() === 0;

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->telefono = $request->telefono;
        $user->status = true;
        if ($tablaVacia) {
            $user->assignRole('admin');
        } else {
            $user->assignRole('visitante');
        }
        $user->password = Hash::make($request->password);

        if ($user->save()) {
            $message = 'Se registro un nuevo usuario';
            Log::info($message);
            return view('login');
        }
        } catch (QueryException $e) {
            // Manejo de la excepción de consulta SQL
            Log::error('Error de consulta SQL: ' . $e->getMessage());
            return Redirect::back()->withErrors(['errors' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']);
        } catch (PDOException $e) {
            // Manejo de la excepción de PDO
            Log::error('Error de PDO: ' . $e->getMessage());
            return Redirect::back()->withErrors(['errors' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']);
        } catch (Exception $e) {
            // Manejo de cualquier otra excepción no prevista
            Log::error('Excepción no controlada: ' . $e->getMessage());
            return Redirect::back()->withErrors(['errors' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']);
        }   
    }

    public function iniciarSesion()
    {
        return view('login');
    }

    public function index($id)
    {
        $user = User::find($id);
        return view('index')->with('user', $user);
    }

    public function login(Request $request)
    {

        try {
            // Verificar el reCAPTCHA
            $recaptchaResponse =  $request->input('g-recaptcha-response');
            $recaptchaSecret = '6Leeyl4pAAAAAD_1KhmEwnITeh4-jD-lFMuv-ts0';

            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $recaptchaSecret,
                'response' => $recaptchaResponse,
            ]);

            $recaptchaData = $response->json();

            if (!$recaptchaData['success']) {
                // El reCAPTCHA no se validó correctamente
                $message = 'Error en validacion de recaptcha formulario de registro';
                Log::warning($message);
                return response()->json([
                    "Status" => 403,
                    "msg" => "Acceso no autorizado",
                ], 403);
            }

            $validacion = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validacion->fails()) {
                return redirect('iniciarSesion')
                    ->withErrors($validacion);
            }


            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = User::where("email", "=", $request->email)->first();
                if ($user->hasRole('admin')) {

                    $url = URL::temporarySignedRoute('mandarSMS', now()->addMinutes(10), [
                        'id' => $user->id
                    ]);

                    return view('mandarSMS')->with('url', $url);
                }
                $request->session()->regenerate();
                return redirect()->route('index', ['id' => $user->id]);
            } else {
                return redirect('iniciarSesion')->withErrors(['errors' => 'Las credenciales proporcionadas son incorrectas.']);
            }
        } catch (QueryException $e) {
            // Manejo de la excepción de consulta SQL
            Log::error('Error de consulta SQL: ' . $e->getMessage());
            return Redirect::back()->withErrors(['errors' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']);
        } catch (PDOException $e) {
            // Manejo de la excepción de PDO
            Log::error('Error de PDO: ' . $e->getMessage());
            return Redirect::back()->withErrors(['errors' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']);
        } catch (Exception $e) {
            // Manejo de cualquier otra excepción no prevista
            Log::error('Excepción no controlada: ' . $e->getMessage());
            return Redirect::back()->withErrors(['errors' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']);
        }
    }

    public function mandarSMS($id, Request $request)
    {
        if (!$request->hasValidSignature()) {
            abort(401);
        }
        $user = User::find($id);

        $response = Http::withBasicAuth('5140e05f', 'pa0wgQ6Z8lEi4jBk')->post(
            "https://api.nexmo.com/v2/verify",
            [
                'brand' => 'Verifica Usuario',
                'workflow' => [[
                    'channel' => "sms",
                    'to' => '528714389101',
                ]],
                'locale' => 'es-mx',
                "channel_timeout" => 300,
                "code_length" => 4
            ]
        );
        if ($response->successful()) {

            return view('validarSMS')->with('request_id', $response->object('request')->request_id)->with('id', $user->id);
        }
        return response()->json($response, 400);
    }

    public function validarCodigo(Request $request)
    {
        $validacion = Validator::make($request->all(), [
            'code' => 'required',
        ]);
        if ($validacion->fails()) {
            return redirect('validarSMS')
                ->withErrors($validacion)
                ->withInput();
        }
        $user = User::find($request->id);
        $request_id = $request->request_id;
        $response = Http::withBasicAuth('5140e05f', 'pa0wgQ6Z8lEi4jBk')->post(
            "https://api.nexmo.com/v2/verify/$request_id",
            [
                "code" => $request->code
            ]
        );
        if ($response->ok()) {
            $user->createToken("auth_token")->plainTextToken;
            // return view('index')->with('user', $user);
            return redirect()->route('index', ['id' => $user->id]);
        }
        return response()->json("Ocurrio algo", 400);
    }
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(["Sesion ha cerrado"],);
        //    Auth::user()->tokens()->delete();
        //    return response()->json([
        //        "status"=>200,
        //        "msg"=>"Se a eliminao tu token"
        //    ],200);
    }
    /* public function prueba(){
        $user = User::find(1);
        $id = $user->id;
        log::info($id);
        $url = URL::temporarySignedRoute('mandarSMS', now()->addMinutes(10),['id'=>$id]);

        return view('mandarSMS')->with('url', $url);

    }*/
    public function lo()
    {
        return view('log2');
    }

    public function prueba(Request $request)
    {


        $validacion = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        $validacion->validate();

        $user = User::where("email", "=", $request->email)->first();

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {

            if ($user->hasRole('admin')) {

                $url = URL::temporarySignedRoute('mandarSMS', now()->addMinutes(10), [
                    'id' => $user->id
                ]);

                return view('mandarSMS')->with('url', $url);
            }
            $request->session()->regenerate();
            return redirect()->route('index', ['id' => $user->id]);
        } else {
            return redirect('iniciarSesion')->withErrors(['error' => 'Las credenciales proporcionadas son incorrectas.']);
        }


        try {
        } catch (QueryException $e) {
            // Manejo de la excepción de consulta SQL
            Log::error('Error de consulta SQL: ' . $e->getMessage());
            return Redirect::back()->withErrors(['errors' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']);
        } catch (PDOException $e) {
            // Manejo de la excepción de PDO
            Log::error('Error de PDO: ' . $e->getMessage());
            return Redirect::back()->withErrors(['errors' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']);
        } catch (Exception $e) {
            // Manejo de cualquier otra excepción no prevista
            Log::error('Excepción no controlada: ' . $e->getMessage());
            return Redirect::back()->withErrors(['errors' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']);
        }
    }


    /*  public function prueba(){
        $user= new User();
        $user->name= 'Marce';
        $user->email='marcelacasesc@gmail.com';
        $user->telefono=8712419989;
        $user->assignRole('admin');
        $contraseña='1234';
        $user->password=Hash::make($contraseña);
        $user->save();
        $email = new VerificarCorreo($user);
        Mail::to($user->email)->send($email);
    }
    
     // Verificar el reCAPTCHA
        $recaptchaResponse =  $request->input('g-recaptcha-response');
        $recaptchaSecret = '6Leeyl4pAAAAAD_1KhmEwnITeh4-jD-lFMuv-ts0';

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $recaptchaSecret,
            'response' => $recaptchaResponse,
        ]);

        $recaptchaData = $response->json();

        if (!$recaptchaData['success']) {
            // El reCAPTCHA no se validó correctamente
            $message = 'Error en validacion de recaptcha formulario de registro';
            Log::warning($message);
            return response()->json([
                "Status" => 403,
                "msg" => "Acceso no autorizado",
            ], 403);
        }

        $validacion = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validacion->fails()) {
            return redirect('iniciarSesion')
                ->withErrors($validacion)
                ->withInput();
        }

        $user = User::where("email", "=", $request->email)->first();

        if (isset($user->id)) {
            if (Hash::check($request->password, $user->password)) {
                //Si es admin generar ruta para mandar sms
                if ($user->hasRole('admin')) {

                    $url = URL::temporarySignedRoute('mandarSMS', now()->addMinutes(10), [
                        'id' => $user->id
                    ]);

                    return view('mandarSMS')->with('url', $url);
                }

                $user->createToken("auth_token")->plainTextToken;
                return redirect()->route('index', ['id' => $user->id]);
                //return view('index')->with('user', $user);
            } else {
                // Si las credenciales no son válidas, redirige de nuevo al formulario de inicio de sesión
                // y pasa un mensaje de error genérico.
                return redirect('iniciarSesion')->withErrors(['error' => 'Las credenciales proporcionadas son incorrectas.']);
            }
        } else {
            // Si las credenciales no son válidas, redirige de nuevo al formulario de inicio de sesión
            // y pasa un mensaje de error genérico.
            return redirect('iniciarSesion')->withErrors(['error' => 'Las credenciales proporcionadas son incorrectas.']);
        }

    
    */

    /*  public function prueba()
    { 
        {
            // Verificar el reCAPTCHA
            $recaptchaResponse = '11111';
            $recaptchaSecret = '6Leeyl4pAAAAAD_1KhmEwnITeh4-jD-lFMuv-ts0';

            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $recaptchaSecret,
                'response' => $recaptchaResponse,
            ]);

            $recaptchaData = $response->json();

            if (!$recaptchaData['success']) {
                // El reCAPTCHA no se validó correctamente
                $message = 'Error en validacion de recaptcha formulario de registro';
                Log::warning($message);
                return response()->json([
                    "Status" => 201,
                    "msg" => "Acceso no autorizado",
                    "error" => null,
                ], 400);
            }

            // Si el reCAPTCHA se validó correctamente, continúa con el registro del usuario
            // Tu lógica para registrar al usuario aquí...

            // Por ejemplo:
            // User::create($request->all());

            return response()->json([
                "Status" => 201,
                "msg" => "Acceso no autorizado",
                "error" => null,
            ], 400);
        }
     }*/
}
