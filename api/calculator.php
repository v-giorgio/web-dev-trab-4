<?php

function generateList(
    $valorFinanciado,
    $taxaReal,
    $iteracoes,
    $prestacao,
    $taxaJurosDada,
    $valorAPrazoDado,
    $numParcelas,
    $valorAVoltar,
    $mesesAVoltar,
    $hasEntrada,
    $coeficienteFinanciamento,
    $precoTotalCorrigido
) {
    return "
        <li>Parcelamento: $numParcelas meses</li>
        <li>Taxa: $taxaJurosDada% ao mês</li>
        <li>Valor financiado: $$valorFinanciado</li>
        <li>Valor a voltar: $" . (is_numeric($valorAVoltar) ? $valorAVoltar : 0) . "</li>
        <li>Meses a voltar: $" . (is_numeric($mesesAVoltar) ? $mesesAVoltar : 0) . "</li>
        <li>Entrada: $hasEntrada</li>
        <li>Coeficiente de Financiamento: $coeficienteFinanciamento</li>
        <li>Prestação: $coeficienteFinanciamento * $$valorFinanciado = $prestacao ao mês</li>
        <li>Valor pago: $$valorAPrazoDado</li>
        <li>Taxa Real ($iteracoes iterações) = $taxaReal% ao mês</li>
        <li>Valor corrigido: $precoTotalCorrigido</li>
    ";
}

function generateTable(
    $tabelaPrice,
    $numParcelas,
    $taxaJurosAtualizada,
    $precoAVistaAtualizado,
    $valorFinanciadoAtualizado
) {
    $tableResult = "";

    for ($mes = 0; $mes < $numParcelas; $mes++) {
        $tableResult .= "
        <tr>
            <td>$mes</td>
            <td>{$tabelaPrice['prestacao']}</td>
            <td>" . ($mes == 0 ? "($taxaJurosAtualizada)" : $tabelaPrice['jurosPorMes'][$mes]) . "</td>
            <td>{$tabelaPrice['amortizacaoPorMes'][$mes]}</td>
            <td>{$tabelaPrice['saldoDevedorPorMes'][$mes]}</td>
        </tr>
        ";
    }

    $tableResultFinal = "
        <tr>
            <td>Total</td>
            <td>$precoAVistaAtualizado</td>
            <td>{$tabelaPrice['totalJuros']}</td>
            <td>$valorFinanciadoAtualizado</td>
            <td>0</td>
        </tr>
    ";

    return [
        'tableResult' => $tableResult,
        'tableResultFinal' => $tableResultFinal
    ];
}

const MSG_PRECO_A_VISTA_MENOR = "O preço à vista é menor do que o preço total corrigido → compre à vista";
const MSG_PRECO_A_VISTA_IGUAL = "O preço à vista é igual ao preço total corrigido";

function getConstantValues($t, $p, $hasEntrada) {
    if ($hasEntrada) {
        return [
            'a' => pow((1 + $t), ($p - 2)),
            'b' => pow((1 + $t), ($p - 1)),
            'c' => pow((1 + $t), $p)
        ];
    }

    return [
        'a' => pow((1 + $t), (-$p)),
        'b' => pow((1 + $t), (-$p - 1)),
        'c' => 0
    ];
}

function getFt($x, $y, $t, $p, $hasEntrada) {
    $constants = getConstantValues($t, $p, $hasEntrada);

    if ($hasEntrada) {
        return [
            'ft' => $y * $t * $constants['b'] - ($x / $p * ($constants['c'] - 1)),
            'ftlinha' => $y * ($constants['b'] + $t * ($p - 1) * $constants['a']) - $x * $constants['b']
        ];
    }

    return [
        'ft' => $y * $t - (($x / $p) * (1 - $constants['a'])),
        'ftlinha' => $y - ($x * $constants['b'])
    ];
}

function calculateTax($x, $y, $p, $hasEntrada) {
    $t0 = $x / $y;
    $tn = 0;
    $numberIter = 0;

    while (abs($tn - $t0) > 0.0001) {
        if ($tn !== 0) $t0 = $tn;
        $ftftlinha = getFt($x, $y, $t0, $p, $hasEntrada);
        $ft = $ftftlinha['ft'];
        $ftlinha = $ftftlinha['ftlinha'];
        $tn = $t0 - ($ft / $ftlinha);
        $numberIter++;
    }

    return ['realTax' => $tn * 100, 'numberIter' => $numberIter];
}

function calculateCF($t, $p) {
    return $t / (1 - pow((1 + $t), (-$p)));
}

function calculateFactor($fe, $p, $CF) {
    return $fe / ($p * $CF);
}

function calculateFe($t = 0) {
    return 1 + $t;
}

function calculatePrecoFinal($x, $fe, $p, $CF) {
    return $x * ($fe / ($p * $CF));
}

function getPercentualPagoAMais($A, $y) {
    return (($A - $y) / $y) * 100;
}

function getDesconto($A, $y) {
    return (($A - $y) / $A) * 100;
}

function getDataTabelaPrice($prestacao, $valorFinanciado, $tax, $p) {
    $jurosPorMes = [];
    $amortizacaoPorMes = [];
    $saldoDevedorPorMes = [];

    $novoValorFinanciado = $valorFinanciado;
    $novaAmortizacao = 0.00;
    $novoJuros = $tax;

    // primeira linha com valores iniciais:
    $saldoDevedorPorMes[] = $novoValorFinanciado;
    $jurosPorMes[] = $tax;
    $amortizacaoPorMes[] = $novaAmortizacao;

    for ($i = 1; $i < $p; $i++) {
        $novoJuros = $novoValorFinanciado * $tax;
        $jurosPorMes[] = $novoJuros;

        $novaAmortizacao = $prestacao - $novoJuros;
        $amortizacaoPorMes[] = $novaAmortizacao;

        $novoValorFinanciado -= $novaAmortizacao;
        $saldoDevedorPorMes[] = $novoValorFinanciado;
    }

    return [
        'jurosPorMes' => $jurosPorMes,
        'amortizacaoPorMes' => $amortizacaoPorMes,
        'saldoDevedorPorMes' => $saldoDevedorPorMes
    ];
}

function orquestrator($x, $y, $p, $hasEntrada, $tax = 0) {
    $taxData = calculateTax($x, $y, $p, $hasEntrada);
    $taxUpdated = $tax == 0 ? $taxData['realTax'] : $tax;

    $cf = calculateCF($taxUpdated, $p);
    $fe = $hasEntrada ? calculateFe($taxUpdated) : calculateFe();
    $factor = calculateFactor($fe, $p, $cf);
    $precoTotalCorrigido = calculatePrecoFinal($x, $fe, $p, $cf);

    $mensagemPreco = $y < $precoTotalCorrigido ? MSG_PRECO_A_VISTA_MENOR : MSG_PRECO_A_VISTA_IGUAL;

    $jurosEmbutidos =  getPercentualPagoAMais($x, $y);
    $desconto = getDesconto($x, $y);

    $percentualPagoAMais = getPercentualPagoAMais($precoTotalCorrigido, $y);

    $prestacao = $cf * $y;
    $valorFinanciado = $y - $prestacao;

    $tabelaPriceData = getDataTabelaPrice($prestacao, $valorFinanciado, $taxUpdated, $p);
    $totalJurosTabelaPrice = $precoTotalCorrigido - $y;

    $precoAVistaAtualizado = $prestacao * $p;

    return [
        'mensagemPreco' => $mensagemPreco,
        'coeficienteFinanciamento' => number_format($cf, 4),
        'valorCorrigido' => '$' . number_format($precoTotalCorrigido, 4),
        'taxaReal' => number_format($taxData['realTax'], 4),
        'iteracoes' => $taxData['numberIter'],
        'fatorAplicado' => number_format($factor, 4),
        'jurosEmbutidos' => number_format($jurosEmbutidos, 2) . '%',
        'desconto' => number_format($desconto, 2) . '%',
        'percentualPagoAMais' => number_format($percentualPagoAMais, 2) . '%',
        'valorFinanciadoAtualizado' => '$' . number_format($valorFinanciado, 2),
        'precoAVistaAtualizado' => number_format($precoAVistaAtualizado, 2),
        'tabelaPrice' => [
            'prestacao' => number_format($prestacao, 2),
            'jurosPorMes' => array_map(fn ($j) => number_format($j, 2), $tabelaPriceData['jurosPorMes']),
            'totalJuros' => number_format($totalJurosTabelaPrice, 2),
            'amortizacaoPorMes' => array_map(fn ($a) => number_format($a, 2), $tabelaPriceData['amortizacaoPorMes']),
            'saldoDevedorPorMes' => array_map(fn ($s) => number_format($s, 2), $tabelaPriceData['saldoDevedorPorMes']),
        ]
    ];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

   // Lembre-se de incluir o código JavaScript anterior no final do seu HTML para manipular eventos no lado do cliente.

   $numParcelasInput = isset($_POST["parc"]) ? $_POST["parc"] : null;
   $taxaJurosInput = isset($_POST["itax"]) ? $_POST["itax"] : null;
   $valorFinanciadoInput = isset($_POST["ipv"]) ? $_POST["ipv"] : null;
   $valorFinalInput = isset($_POST["ipp"]) ? $_POST["ipp"] : null;
   $valorAVoltarInput = isset($_POST["valoravoltar"]) ? $_POST["valoravoltar"] : null;
   $mesesAVoltarInput = isset($_POST["mesesavoltar"]) ? $_POST["mesesavoltar"] : null;

   // Define $hasEntrada como 0 por padrão
   $hasEntrada = 0;

   // Verifica se a chave "idp" está definida e a atribui a $hasEntradaCheckbox
   $hasEntradaCheckbox = isset($_POST["idp"]) ? $_POST["idp"] : null;
}

?>
