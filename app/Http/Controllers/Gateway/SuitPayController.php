<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\AffiliateWithdraw;
use App\Models\SuitPayPayment;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Traits\Gateways\SuitpayTrait;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use App\Models\Transaction;

class SuitPayController extends Controller
{
    use SuitpayTrait;


    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function callbackMethodPayment(Request $request)
    {
        $data = $request->all();
        \DB::table('debug')->insert(['text' => json_encode($request->all())]);

        return response()->json([], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
public function callbackMethod(Request $request)
{
    $data = $request->all();
    \Log::info('[vizzerpay] Callback recebido', ['data' => $data]);

    try {
        if (empty($data['reference_code']) || empty($data['status'])) {
            \Log::error('[vizzerpay] Dados incompletos no callback', ['data' => $data]);
            return response()->json(['message' => 'reference_code e status são obrigatórios'], 400);
        }

        \Log::info('[vizzerpay] Dados de transação recebidos', [
            'reference_code' => $data['reference_code'],
            'status' => $data['status'],
        ]);

        if ($data['status'] === 'paid') {
            \Log::info('[vizzerpay] Pagamento confirmado, tentando finalizar...', [
                'external_id' => $data['reference_code'],
            ]);

            if (self::finalizePayment($data['reference_code'])) {
                \Log::info('[vizzerpay] Pagamento finalizado com sucesso', [
                    'external_id' => $data['reference_code'],
                ]);
                return response()->json(['message' => 'Pagamento confirmado'], 200);
            } else {
                \Log::warning('[vizzerpay] Falha ao finalizar pagamento', [
                    'external_id' => $data['reference_code'],
                ]);
                return response()->json(['message' => 'Falha ao finalizar pagamento'], 500);
            }
        } else {
            \Log::warning('[vizzerpay] Status diferente de "paid"', [
                'status' => $data['status'],
            ]);
            return response()->json(['message' => 'Status inválido'], 400);
        }
    } catch (\Exception $e) {
        \Log::error('[vizzerpay] Erro inesperado no callback', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json(['message' => 'Erro interno'], 500);
    }
}



    /**
     * @param Request $request
     * @return null
     */
    public function getQRCodePix(Request $request)
    {
        return self::suitPayRequestQrcode($request);
    }

    public function consultStatusTransactionPix(Request $request)
    {
        return self::suitPayConsultStatusTransaction($request);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function confirmWithdrawalUser($id)
    {
        $withdrawal = Withdrawal::find($id);
        if (!empty($withdrawal)) {
            $suitpayment = SuitPayPayment::create([
                'withdrawal_id' => $withdrawal->id,
                'user_id' => $withdrawal->user_id,
                'pix_key' => $withdrawal->pix_key,
                'pix_type' => $withdrawal->pix_type,
                'amount' => $withdrawal->amount,
                'observation' => 'suitpay',
            ]);

            if ($suitpayment) {
                $parm = [
                    'pix_key' => $withdrawal->pix_key,
                    'pix_type' => $withdrawal->pix_type,
                    'amount' => $withdrawal->amount,
                    'suitpayment_id' => $suitpayment->id
                ];

                $resp = self::suitPayPixCashOut($parm);

                if ($resp) {
                    $withdrawal->update(['status' => 1]);
                    Notification::make()
                        ->title('Saque solicitado')
                        ->body('Saque solicitado com sucesso')
                        ->success()
                        ->send();

                    return back();
                } else {
                    Notification::make()
                        ->title('Erro no saque')
                        ->body('Erro ao solicitar o saque')
                        ->danger()
                        ->send();

                    return back();
                }
            }
        }
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function confirmWithdrawalAffiliate($id)
    {
        $withdrawal = AffiliateWithdraw::find($id);

        if (!empty($withdrawal)) {
            $suitpayment = SuitPayPayment::create([
                'withdrawal_id' => $withdrawal->id,
                'user_id' => $withdrawal->user_id,
                'pix_key' => $withdrawal->pix_key,
                'pix_type' => $withdrawal->pix_type,
                'amount' => $withdrawal->amount,
                'observation' => 'suitpay',
            ]);

            if ($suitpayment) {
                $parm = [
                    'pix_key' => $withdrawal->pix_key,
                    'pix_type' => $withdrawal->pix_type,
                    'amount' => $withdrawal->amount,
                    'suitpayment_id' => $suitpayment->id
                ];

                $resp = self::suitPayPixCashOut($parm);

                if ($resp) {
                    $withdrawal->update(['status' => 1]);
                    Notification::make()
                        ->title('Saque solicitado')
                        ->body('Saque solicitado com sucesso')
                        ->success()
                        ->send();

                    return back();
                } else {
                    Notification::make()
                        ->title('Erro no saque')
                        ->body('Erro ao solicitar o saque')
                        ->danger()
                        ->send();

                    return back();
                }
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function withdrawalFromModal($id, $action)
    {
        if ($action == 'user') {
            return $this->confirmWithdrawalUser($id);
        }

        if ($action == 'affiliate') {
            return $this->confirmWithdrawalAffiliate($id);
        }


    }

    /**
     * Cancel Withdrawal
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancelWithdrawalFromModal($id, $action)
    {
        if ($action == 'user') {
            return $this->cancelWithdrawalUser($id);
        }

        if ($action == 'affiliate') {
            return $this->cancelWithdrawalAffiliate($id);
        }
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    private function cancelWithdrawalAffiliate($id)
    {
        $withdrawal = AffiliateWithdraw::find($id);
        if (!empty($withdrawal)) {
            $wallet = Wallet::where('user_id', $withdrawal->user_id)
                ->where('currency', $withdrawal->currency)
                ->first();

            if (!empty($wallet)) {
                $wallet->increment('refer_rewards', $withdrawal->amount);

                $withdrawal->update(['status' => 2]);
                Notification::make()
                    ->title('Saque cancelado')
                    ->body('Saque cancelado com sucesso')
                    ->success()
                    ->send();

                return back();
            }
            return back();
        }
        return back();
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    private function cancelWithdrawalUser($id)
    {
        $withdrawal = Withdrawal::find($id);
        if (!empty($withdrawal)) {
            $wallet = Wallet::where('user_id', $withdrawal->user_id)
                ->where('currency', $withdrawal->currency)
                ->first();

            if (!empty($wallet)) {
                $wallet->increment('balance_withdrawal', $withdrawal->amount);

                $withdrawal->update(['status' => 2]);
                Notification::make()
                    ->title('Saque cancelado')
                    ->body('Saque cancelado com sucesso')
                    ->success()
                    ->send();

                return back();
            }
            return back();
        }
        return back();
    }
    public function checkTransactionStatusByToken(Request $request)
    {
        // Validação dos parâmetros recebidos
        $request->validate([
            'token' => 'required|string',
        ]);

        // Obtém o token do request
        $token = $request->input('token');

        // Obtém o usuário autenticado
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Usuário não autenticado.',
            ], 401);
        }

        // Busca a transação na tabela usando o token e o user_id
        $transaction = Transaction::where('user_id', $user->id)
            ->where('token', $token)
            ->first();

        // Verifica se a transação foi encontrada
        if ($transaction) {
            $status = $transaction->status;
            $statusMessage = $status == 1 ? 'Confirmado' : 'Aguardando pagamento';

            return response()->json([
                'status' => $status,
                'status_message' => $statusMessage,
            ]);
        } else {
            return response()->json([
                'error' => 'Transação não encontrada.',
            ], 404);
        }
    }
}
