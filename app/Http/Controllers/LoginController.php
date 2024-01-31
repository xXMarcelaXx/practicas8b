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
                $message = 'Se logeo un usuario visitante con id: '. $user->id;
                Log::info($message);
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
        try {

            if (!$request->hasValidSignature()) {
                $message = 'Alguien intenta acceder al modulo mandarSMS sin ruta frimada valida';
                Log::warning($message);
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
            return abort(401);
            
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

    public function validarCodigo(Request $request)
    {
        try {
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
                $message = 'Inicio sesion un usuario administrador con id: '.$user->id;
                Log::info($message);
                $request->session()->regenerate();
                return redirect()->route('index', ['id' => $user->id]);
            }
            return response()->json("Ocurrio algo", 400);
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

    public function index($id)
    {
        $user = User::find($id);
        return view('index')->with('user', $user);
    }
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('iniciarSesion');
    }

}
