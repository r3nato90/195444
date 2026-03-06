<?php
// SIMULADOR DE WEBHOOK PIXUP v2
$url = "https://101win.fun/api/pixup/webhook";
$txid_para_testar = "COLE_AQUI_O_TXID_DA_TABELA_PIX_PAYMENTS"; // <--- Ajuste aqui

$data = [
    "requestBody" => [
        "transactionType" => "PAYMENT",
        "transactionId" => "SIMULADO_".uniqid(),
        "external_id" => $txid_para_testar,
        "amount" => 1.00,
        "dateApproval" => date("Y-m-d H:i:s")
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Resultado do Teste de Webhook</h3>";
echo "Status HTTP: " . $httpCode . "<br>";
echo "Resposta do Servidor: " . $response;