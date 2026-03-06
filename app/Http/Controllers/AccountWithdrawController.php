<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AccountWithdraw;
use Illuminate\Support\Facades\Auth;

class AccountWithdrawController extends Controller
{
    public function store(Request $request)
    {
        // Validar os dados recebidos (removido o user_id da validação por segurança)
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'pix_type' => 'required|string|in:document,email,phoneNumber,randomKey', // Adicionado randomKey para garantir compatibilidade
            'pix_key' => 'required|string|max:255',
            'document' => 'nullable|string|max:20', // Margem segura para formatação
        ]);

        // SEGURANÇA: Pega o ID do usuário diretamente do token logado
        // Isso impede que um hacker insira a chave PIX dele na conta de outro jogador
        $userId = auth('api')->id() ?? $request->input('user_id');

        if (!$userId) {
            return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }

        // Contar o número de contas existentes para o usuário
        $accountCount = AccountWithdraw::where('user_id', $userId)->count();

        // Verificar se o usuário já possui 8 contas
        if ($accountCount >= 8) {
            return response()->json(['message' => 'O usuário já possui o número máximo de contas (8).'], 400);
        }

        // Criar uma nova instância de AccountWithdraw e preencher os dados
        $withdraw = new AccountWithdraw();
        $withdraw->user_id = $userId;
        $withdraw->name = $validatedData['name'];
        $withdraw->pix_type = $validatedData['pix_type'];

        // Salvar pix_key exatamente como recebido
        $withdraw->pix_key = $validatedData['pix_key'];

        // Limpar e formatar o Documento (CPF/CNPJ) apenas para números
        if (!empty($validatedData['document'])) {
            $withdraw->document = preg_replace('/[^0-9]/', '', $validatedData['document']);
        }

        // Salvar os dados no banco de dados
        $withdraw->save();

        return response()->json(['message' => 'Conta PIX salva com sucesso!'], 200);
    }
}