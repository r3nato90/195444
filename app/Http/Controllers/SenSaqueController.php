<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SenSaque;
// Removida a necessidade de Hash para visualização direta

class SenSaqueController extends Controller
{
    public function store(Request $request)
    {
        // Validar os dados recebidos (Mantido 6 dígitos)
        $validatedData = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'pin' => 'required|string|size:6',
        ]);

        // ALTERAÇÃO: Agora salvamos o PIN exatamente como o usuário digitou
        $senSaque = new SenSaque();
        $senSaque->user_id = $validatedData['user_id'];
        $senSaque->valid_saque = $validatedData['pin']; // Sem criptografia agora
        $senSaque->save();

        return response()->json(['message' => 'Senha de saque criada com sucesso!'], 201);
    }

    public function verifyPin(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'pin' => 'required|string|size:6',
        ]);

        $senSaque = SenSaque::where('user_id', $validatedData['user_id'])->first();

        if (!$senSaque) {
            return response()->json(['message' => 'PIN não encontrado.'], 404);
        }

        // ALTERAÇÃO: Comparação direta de texto (Necessário para senhas não-criptografadas)
        // Se a senha no banco for igual à digitada, ele libera o saque
        if ($validatedData['pin'] === $senSaque->valid_saque) {
            return response()->json(['message' => 'PIN verificado com sucesso!'], 200);
        } else {
            return response()->json(['message' => 'PIN inválido.'], 400);
        }
    }

    public function checkUserHasPin(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $senSaque = SenSaque::where('user_id', $validatedData['user_id'])->first();

        if ($senSaque) {
            return response()->json(['hasPins' => true], 200);
        } else {
            return response()->json(['hasPin' => false], 200);
        }
    }
}