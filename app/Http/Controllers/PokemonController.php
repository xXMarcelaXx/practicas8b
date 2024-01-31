<?php

namespace App\Http\Controllers;

use App\Models\Pokemon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class PokemonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $url='https://rickandmortyapi.com/api/character';
        $personajes = Http::get($url)['results'];
        $favoritos = Pokemon::all();
        $buscar = 0;
        return view('personajes')->with('personajes',$personajes)->with('favoritos',$favoritos)->with('buscar',$buscar);
    }

    public function guardarFavorito(Request $request)
    {
        log::info($request);
        $pokemon = new Pokemon();
        $pokemon->name = $request->get('name');
        $pokemon->image = $request->get('image');
        $pokemon->specie = $request->get('specie');
        $pokemon->status = true;
        $pokemon->save();
        return redirect ('/personajes');

    }

    public function eliminarFavorito($id)
    {
        $pokemon = Pokemon::find($id);
        $pokemon->status = 0;
        $pokemon->save();

        return redirect ('/personajes');
    }

    public function buscar()
    {
        $url='https://rickandmortyapi.com/api/character';
        $personajes = Http::get($url)['results'];
        $favoritos = Pokemon::all();
        $buscar = 0;
        return view('personajes')->with('personajes',$personajes)->with('favoritos',$favoritos)->with('buscar',$buscar);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Pokemon $pokemon)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pokemon $pokemon)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pokemon $pokemon)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pokemon $pokemon)
    {
        //
    }
}
