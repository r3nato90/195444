<?php

namespace App\Traits\Gateways;

use App\Models\AffiliateHistory;
use App\Models\Deposit;
use App\Models\GamesKey;
use App\Models\Gateway;
use App\Models\Setting;
use App\Models\SuitPayPayment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewDepositNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Core as Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait SuitpayTrait
{
    /**
     * @var $uri
     * @var $clienteId
     * @var $clienteSecret
     */
    protected static string $uri;
    protected static string $clienteId;
    protected static string $clienteSecret;

  
    private static function generateCredentials()
    {
        $setting = Gateway::first();
        if(!empty($setting)) {
            self::$uri = $setting->getAttributes()['suitpay_uri'];
            self::$clienteId = $setting->getAttributes()['suitpay_cliente_id'];
            self::$clienteSecret = $setting->getAttributes()['suitpay_cliente_secret'];
        }
    }

    /**
     * Request QRCODE
     * Metodo para solicitar uma QRCODE PIX
     * @dev @dracman999
     * @return array
     */
public static function requestQrcode($request)
{
    try {
        // Log: Início da solicitação de QR Code
        \Log::info('[vizzerpay] Iniciando solicitação de QR Code...', ['request' => $request->all()]);

        // Obtendo configurações
        $setting = \Helper::getSetting();

        // Validando os dados recebidos
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:' . $setting->min_deposit, 'max:' . $setting->max_deposit],
            'cpf'    => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            \Log::warning('[vizzerpay] Validação falhou', ['errors' => $validator->errors()]);
            return response()->json($validator->errors(), 400);
        }

        // Gerar as credenciais
        self::generateCredentials();

        // Gerar o ID único para a transação
        $idUnico = uniqid();
        \Log::info('[vizzerpay] ID único gerado', ['idUnico' => $idUnico]);

        // Dados a serem enviados para gerar o QR Code
        $postData = [
            'client_id' => self::$clienteId,
            'client_secret' => self::$clienteSecret,
            'nome' => auth('api')->user()->name,
            'documento' => \Helper::soNumero($request->input("cpf")),
            'valor' => (float) $request->input("amount"),
            'descricao' => 'Depósito via PIX',
            'urlnoty' => url('/suitpay/callback'),
            'telefone' => \Helper::soNumero(auth('api')->user()->phone),
            'email' => auth('api')->user()->email,
        ];

        // URL de requisição para a API
        $url = self::$uri . 'pix/qrcode';
        \Log::info('[vizzerpay] Enviando requisição para gerar QR Code', [
            'url' => $url,
            'postData' => $postData
        ]);

        // Enviar requisição para a API
        $response = Http::asForm()->post($url, $postData);
        \Log::info('[vizzerpay] Resposta da API recebida', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        // Verificar se a resposta foi bem-sucedida
        if ($response->successful()) {
            $responseData = $response->json();
            \Log::info('[vizzerpay] Resposta da API processada', ['responseData' => $responseData]);

            // Verificar se o qrcode e reference_code foram retornados
            if (!isset($responseData['qrcode']) || !isset($responseData['reference_code'])) {
                \Log::error('[SuitPay] Chaves obrigatórias não encontradas na resposta');
                return response()->json(['error' => 'Resposta inválida da API'], 500);
            }

            $externalId = $responseData['reference_code'];
            $qrcode = $responseData['qrcode'];
            \Log::info('[vizzerpay] External ID (reference_code) obtido', ['external_id' => $externalId]);

            // Realizar a transação e o depósito dentro de uma transação DB
            DB::transaction(function () use ($externalId, $request, $idUnico) {
                \Log::info('[vizzerpay] Iniciando transação e depósito', [
                    'external_id' => $externalId,
                    'amount' => $request->input("amount"),
                ]);

                // Salvar a transação com o external_id
                self::generateTransaction($externalId, $request->input("amount"), $externalId);

                // Salvar o depósito com o external_id
                self::generateDeposit($externalId, $request->input("amount"), $externalId);
            });

            // Adicionar uma chave de sessão para o frontend disparar a função JS
            session(['qr_response' => [
                'status' => 'QRCode gerado com sucesso',
                'transaction_id' => $externalId
            ]]);

            // Enviar resposta com o QR Code e o externalId
            \Log::info('[vizzerpay] Requisição processada com sucesso', [
                'transactionId' => $externalId,
                'qrcode' => $qrcode
            ]);
            return response()->json([
                'status' => true,
                'transactionId' => $externalId,
                'qrcode' => $qrcode,
                'externalId' => $externalId
            ]);
        }

        // Log: Falha na geração do QR Code
        \Log::error('[vizzerpay] Falha na geração do QR Code', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);
        return response()->json(['error' => "Ocorreu uma falha ao entrar em contato com o banco."], 500);

    } catch (\Exception $e) {
        // Log: Erro inesperado
        \Log::error('[vizzerpay] Erro ao solicitar QR Code', ['message' => $e->getMessage()]);
        return response()->json(['error' => 'Erro interno'], 500);
    }
}


    /**
     * Consult Status Transaction
     * Consultar o status da transação
     * @dev @dracman999
     *
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */

public static function consultStatusTransaction()
{
    Log::info('Iniciando consulta de status do último pagamento');

    self::generateCredentials();

    try {
        // 1. Buscar a última transação da tabela
        Log::debug('Buscando última transação no banco de dados');
        $transaction = Transaction::latest()->first();

        if (!$transaction) {
            Log::error('Nenhuma transação encontrada na base');
            return response()->json(['error' => 'Nenhuma transação registrada'], 404);
        }

        // 2. Obter o último external_id
        $externalId = $transaction->external_id;
        Log::debug('Último external_id recuperado', [
            'external_id' => $externalId,
            'criado_em' => $transaction->created_at
        ]);

        // 3. Montar URL exata conforme especificação
        $statusUrl = 'https://vizzerpay.com/libs/consult/transaction_status.php?id=' . $externalId;
        Log::info('URL montada com último external_id', ['url' => $statusUrl]);

        // 4. Fazer requisição GET
        Log::debug('Enviando requisição para VizzerPay', [
            'url' => $statusUrl,
            'headers' => [
                'ci' => self::$clienteId,
                'cs' => '***' // Ofuscação do client secret
            ]
        ]);

        $response = Http::withHeaders([
            'ci' => self::$clienteId,
            'cs' => self::$clienteSecret
        ])->get($statusUrl);

        // 5. Processar resposta
        Log::debug('Resposta recebida', [
            'status_code' => $response->status(),
            'tamanho_resposta' => strlen($response->body())
        ]);

        if (!$response->successful()) {
            Log::error('Falha na comunicação com VizzerPay', [
                'external_id' => $externalId,
                'status' => $response->status()
            ]);
            return response()->json(['status' => 'pendente'], 202);
        }

        // 6. Obter o status da resposta
        $status = $response->json();
        Log::info('Status da resposta', [
            'external_id' => $externalId,
            'status_json' => $status
        ]);

        // Verificar se o campo 'data' existe e se 'status' dentro de 'data' é 'PAID'
        if (isset($status['data']) && isset($status['data']['status'])) {
            $transactionStatus = $status['data']['status'];
            Log::info('Status da transação recebido: ' . $transactionStatus);

            // Verifica se o status é PAID
            if ($transactionStatus === 'PAID') {
                Log::notice('Pagamento confirmado', ['external_id' => $externalId]);

                // Chama a função para finalizar o pagamento
                if (self::finalizePayment($externalId)) {
                    Log::info('Pagamento finalizado com sucesso');
                    return response()->json(['status' => 'PAID']);
                }

                // Caso falhe na finalização
                Log::alert('Falha na finalização do pagamento');
                return response()->json(['error' => 'Erro na finalização'], 500);
            } else {
                // Caso o status não seja PAID, loga o status atual
                Log::info('Status da transação não finalizado: ' . $transactionStatus);
                return response()->json(['status' => $transactionStatus]);
            }
        } else {
            // Se o formato da resposta for inválido
            Log::error('Resposta mal formatada, status não encontrado');
            return response()->json(['error' => 'Resposta inválida do gateway'], 500);
        }

    } catch (\Exception $e) {
        // Tratamento de exceção
        Log::critical('Erro crítico no processo', [
            'erro' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => 'Erro interno'], 500);
    }
}

    /*
     * @param $idTransaction
     * @dev @dracman999
     * @return bool
     */
public static function finalizePayment($externalId) : bool
{
    // Log inicial para verificar se o externalId foi recebido corretamente
    \Log::info("Iniciando finalização do pagamento com external_id: $externalId");

    // Altera a busca para usar o external_id
    $transaction = Transaction::where('external_id', $externalId)->where('status', 0)->first();
    if (!$transaction) {
        \Log::error("Transação não encontrada para o external_id: $externalId");
        return false;
    }
    \Log::info("Transação encontrada para o external_id: $externalId, id da transação: " . $transaction->id);

    $setting = \Helper::getSetting();
    \Log::info("Configurações carregadas para o pagamento.");

    if (!empty($transaction)) {
        $user = User::find($transaction->user_id);
        \Log::info("Usuário encontrado, id: " . $user->id);

        $wallet = Wallet::where('user_id', $transaction->user_id)->first();
        if (!empty($wallet)) {
            \Log::info("Carteira encontrada para o usuário.");

            $setting = Setting::first();

            // Verifica se é o primeiro depósito
            $checkTransactions = Transaction::where('user_id', $transaction->user_id)
                ->where('status', 1)
                ->count();
            \Log::info("Verificando transações anteriores do usuário. Transações pagas encontradas: $checkTransactions");

            if ($checkTransactions == 0 || empty($checkTransactions)) {
                // Paga o bônus inicial
                $bonus = Helper::porcentagem_xn($setting->initial_bonus, $transaction->price);
                \Log::info("Pagando bônus inicial: $bonus");
                $wallet->increment('balance_bonus', $bonus);
                $wallet->update(['balance_bonus_rollover' => $bonus * $setting->rollover]);
            }

            // Rollover do depósito
            $wallet->update(['balance_deposit_rollover' => $transaction->price * intval($setting->rollover_deposit)]);
            \Log::info("Aplicando rollover ao depósito.");

            // Acumula bônus VIP
            Helper::payBonusVip($wallet, $transaction->price);
            \Log::info("Pagando bônus VIP.");

            if ($wallet->increment('balance', $transaction->price)) {
                \Log::info("Saldo do usuário atualizado.");

                if ($transaction->update(['status' => 1])) {
                    \Log::info("Status da transação atualizado para 'pago'.");

                    // Procura o depósito correspondente
                    $deposit = Deposit::where('external_id', $externalId)->where('status', 0)->first();
                    if (!empty($deposit)) {
                        \Log::info("Depósito encontrado, id: " . $deposit->id);

                        // Processa o CPA
                        $affHistoryCPA = AffiliateHistory::where('user_id', $user->id)
                            ->where('commission_type', 'cpa')
                            ->where('status', 0)
                            ->first();
                        if (!empty($affHistoryCPA)) {
                            \Log::info("Verificando histórico de CPA.");

                            // Verifica se o CPA já pode ser pago
                            $sponsorCpa = User::find($user->inviter);
                            if (!empty($sponsorCpa)) {
                                \Log::info("Sponsor encontrado para CPA, id: " . $sponsorCpa->id);
                                if ($affHistoryCPA->deposited_amount >= $sponsorCpa->affiliate_baseline || $deposit->amount >= $sponsorCpa->affiliate_baseline) {
                                    $walletCpa = Wallet::where('user_id', $affHistoryCPA->inviter)->first();
                                    if (!empty($walletCpa)) {
                                        // Paga o CPA
                                        $walletCpa->increment('refer_rewards', $sponsorCpa->affiliate_cpa);
                                        $affHistoryCPA->update(['status' => 1, 'commission_paid' => $sponsorCpa->affiliate_cpa]);
                                        \Log::info("CPA pago ao sponsor: " . $sponsorCpa->id);
                                    }
                                } else {
                                    $affHistoryCPA->update(['deposited_amount' => $transaction->price]);
                                    \Log::info("Devido a regras de CPA, atualização do valor depositado: " . $transaction->price);
                                }
                            }
                        }

                        if ($deposit->update(['status' => 1])) {
                            \Log::info("Depósito marcado como pago.");

                            $admins = User::where('role_id', 0)->get();
                            foreach ($admins as $admin) {
                                $admin->notify(new NewDepositNotification($user->name, $transaction->price));
                                \Log::info("Notificação de novo depósito enviada ao admin, id: " . $admin->id);
                            }
                        }
                    }
                }
            } else {
                \Log::error("Erro ao atualizar o saldo do usuário.");
            }
        } else {
            \Log::error("Carteira não encontrada para o usuário.");
        }
    } else {
        \Log::error("Transação não encontrada ou com status inválido.");
    }

    return true;
}

/**
 * @param $idTransaction
 * @param $amount
 * @param $externalId
 * @dev @dracman999
 * @return void
 */
private static function generateDeposit($idTransaction, $amount, $externalId)
{
    $userId = auth('api')->user()->id;
    $wallet = Wallet::where('user_id', $userId)->first();

    Deposit::create([
        'payment_id'=> $idTransaction,
        'user_id'   => $userId,
        'amount'    => $amount,
        'type'      => 'pix',
        'currency'  => $wallet->currency,
        'symbol'    => $wallet->symbol,
        'status'    => 0,
        'external_id' => $externalId, // Adicionando o external_id
    ]);
}

/**
 * @param $idTransaction
 * @param $amount
 * @param $externalId
 * @dev @dracman999
 * @return void
 */
private static function generateTransaction($idTransaction, $amount, $externalId)
{
    $setting = \Helper::getSetting();

    Transaction::create([
        'payment_id' => $idTransaction,
        'user_id' => auth('api')->user()->id,
        'payment_method' => 'pix',
        'price' => $amount,
        'currency' => $setting->currency_code,
        'status' => 0,
        'external_id' => $externalId, // Adicionando o external_id
    ]);
}

    /**
     * @param $request
     * @dev @dracman999
     * @return \Illuminate\Http\JsonResponse|void
     */
    public static function pixCashOut(array $array): bool
    {
        self::generateCredentials();

        $response = Http::withHeaders([
            'ci' => self::$clienteId,
            'cs' => self::$clienteSecret
        ])->post(self::$uri.'pix/payment', [
            "key" => $array['pix_key'],
            "typeKey" => $array['pix_type'],
            "value" => $array['amount'],
            'callbackUrl' => url('/calback'),
        ]);

        if($response->successful()) {
            $responseData = $response->json();

            if($responseData['response'] == 'OK') {
                $suitPayPayment = SuitPayPayment::lockForUpdate()->find($array['suitpayment_id']);
                if(!empty($suitPayPayment)) {
                    if($suitPayPayment->update(['status' => 1, 'payment_id' => $responseData['idTransaction']])) {
                        return true;
                    }
                    return false;
                }
                return false;
            }
            return false;
        }
        return false;
    }
}