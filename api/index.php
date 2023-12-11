<?php
const MSG_PRECO_A_VISTA_MENOR = "O preço à vista é menor do que o preço total corrigido → compre à vista";
const MSG_PRECO_A_VISTA_IGUAL = "O preço à vista é igual ao preço total corrigido";

function getConstantValues($t, $p, $hasEntrada) {
    if ($hasEntrada) {
        return [
            pow((1 + $t), ($p - 2)),
            pow((1 + $t), ($p - 1)),
            pow((1 + $t), $p)
        ];
    }

    return [
        pow((1 + $t), (-$p)),
        pow((1 + $t), (-$p - 1)),
        0
    ];
}

function getFt($x, $y, $t, $p, $hasEntrada) {
    $constants = getConstantValues($t, $p, $hasEntrada);

    if ($hasEntrada) {
        return [
            $y * $t * $constants[1] - ($x / $p * ($constants[2] - 1)),
            $y * ($constants[1] + $t * ($p - 1) * $constants[0]) - $x * $constants[1]
        ];
    }

    return [
        $y * $t - (($x / $p) * (1 - $constants[0])),
        $y - ($x * $constants[1])
    ];
}

function calculateTax($x, $y, $p, $hasEntrada) {
    $t0 = $x / $y;
    $tn = 0;
    $numberIter = 0;

    while (abs($tn - $t0) > 0.0001) {
        if ($tn !== 0) $t0 = $tn;
        $ftftlinha = getFt($x, $y, $t0, $p, $hasEntrada);
        $ft = $ftftlinha[0];
        $ftlinha = $ftftlinha[1];
        $tn = $t0 - ($ft / $ftlinha);
        $numberIter++;
    }

    return [$tn * 100, $numberIter];
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
        $jurosPorMes,
        $amortizacaoPorMes,
        $saldoDevedorPorMes
    ];
}

function orquestrator($x, $y, $p, $hasEntrada, $tax = 0) {
    $taxData = calculateTax($x, $y, $p, $hasEntrada);
    $taxUpdated = $tax == 0 ? $taxData[0] : $tax;

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
        $mensagemPreco,
        number_format($cf, 4),
        '$' . number_format($precoTotalCorrigido, 4),
        number_format($taxData[0], 4),
        $taxData[1],
        number_format($factor, 4),
        number_format($jurosEmbutidos, 2) . '%',
        number_format($desconto, 2) . '%',
        number_format($percentualPagoAMais, 2) . '%',
        '$' . number_format($valorFinanciado, 2),
        number_format($precoAVistaAtualizado, 2),
        number_format($prestacao, 2),
        array_map(fn ($j) => number_format($j, 2), $tabelaPriceData[0]),
        number_format($totalJurosTabelaPrice, 2),
        array_map(fn ($a) => number_format($a, 2), $tabelaPriceData[1]),
        array_map(fn ($s) => number_format($s, 2), $tabelaPriceData[2]),
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>CDC</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Crédito Direto ao Consumidor</title>

    <style>
        .box {
            background-color: antiquewhite;
            box-shadow: 8px 8px 6px grey;
            width: 450px;
            border-style: solid;
            border-width: 3px;
            border-color: lightblue;
            padding-left: 10px;
            padding-right: 10px;
            padding-bottom: 10px;
            margin-left: 2px;
        }
        body {
            background-color: #f0f0f2;
            margin: 0;
            padding: 2em;
            font-family: -apple-system, system-ui, BlinkMacSystemFont,
                "Segoe UI", "Open Sans", "Helvetica Neue", Helvetica, Arial,
                sans-serif;
        }
        input {
            margin: 10px 3px 10px 3px;
            border: 1px solid grey;
            border-radius: 5px;
            font-size: 12px;
            padding: 5px 5px 5px 5px;
        }
        label {
            position: relative;
            top: 12px;
            width: 190px;
            float: left;
        }
        #submitButton {
            width: 80px;
            margin-left: 20px;
        }
        #errorMessage {
            color: red;
            font-size: 90% !important;
        }
        #successMessage {
            color: green;
            font-size: 90% !important;
            display: block;
            margin-top: 20px;
        }
        .button {
            font-size: 13px;
            color: red;
            background-color: #f8fad7;
        }
        .button:hover {
            background-color: #fadad7;
        }
        .main-box {
            border: 1px black solid;
            background-color: #cac3ba;
            width: 400px;
        }
        input.currency {
            text-align: left;
            padding-right: 15px;
        }
        .input-group .form-control {
            float: none;
        }
        .input-group .input-buttons {
            position: relative;
            z-index: 3;
        }
        .messages {
            text-align: center;
        }
        .legend-box {
            border: 5px lightblue solid;
            margin-left: 1em;
            background-color: #ff6347;
            padding: 0.2em 0.8em;
        }

        table {
            text-align: center;
            border-top: 2px dashed black;
        }

        tbody tr td, thead tr th {
            padding: 10px 30px;
            border-left: 2px dashed black;
            border-right: 2px dashed black;
            border-bottom: 2px dashed black;
        }

        tfoot td {
            border: 2px solid black;
        }
    </style>
</head>
<body>
    <fieldset id="cdcfieldset" class="main-box ui-widget-content">
        <legend class="legend-box">
            <strong>Crédito Direto ao Consumidor</strong>
        </legend>
        <form method="post">
            <div class="box">
                <span class="input-group-addon" style="color: antiquewhite">$</span>
                <label for="parc">Parcelamento:</label>
                <input
                    id="parc"
                    type="number"
                    name="parc"
                    size="5"
                    placeholder="12"
                    min="1"
                    max="72000"
                    step="1"
                    required
                />meses<br />

                <span class="input-group-addon" style="color: antiquewhite">$</span>
                <label for="itax">Taxa de juros:</label>
                <input
                    id="itax"
                    type="number"
                    name="itax"
                    size="10"
                    placeholder="0,05"
                    min="0.0"
                    max="100.0"
                    step="any"
                    required
                />% mês<br />

                <span class="input-group-addon">$</span>
                <label for="ipv">Valor Financiado: </label>
                <input
                    id="ipv"
                    type="number"
                    name="ipv"
                    min="0.0"
                    placeholder="200,00"
                    step="0.01"
                    class="form-control currency"
                    required
                /><br />

                <span class="input-group-addon">$</span>
                <label for="ipp">Valor Final:</label>
                <input
                    id="ipp"
                    type="number"
                    name="ipp"
                    placeholder="500,00"
                    min="0.0"
                    step="0.01"
                    class="form-control currency"
                    required
                /><br />

                <label for="valoravoltar">Valor A Voltar (opcional):</label>
                <input
                    id="valoravoltar"
                    type="number"
                    name="valoravoltar"
                    placeholder="500,00"
                    min="0.0"
                    step="0.01"
                    class="form-control currency"
                /><br />
                
                <span class="input-group-addon">$</span>
                <label for="mesesavoltar">Meses A Voltar (opcional):</label>
                <input
                    id="mesesavoltar"
                    type="number"
                    name="mesesavoltar"
                    placeholder="2"
                    min="0"
                    step="1"
                    class="form-control currency"
                /><br />

                <label for="idp">Entrada?</label>
                <input id="idp" type="checkbox" name="idp" /><br />
            </div>
            <div class="messages">
                <input
                    id="submitButton"
                    class="button"
                    type="submit"
                    name="submitButton"
                />
            </div>
        </form>
    </fieldset>

    <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submitButton"])) {
            $parcelamento = $_POST["parc"];
            $taxaJuros = $_POST["itax"];
            $valorFinanciado = $_POST["ipv"];
            $valorFinal = $_POST["ipp"];
            $valorAVoltar = isset($_POST["valoravoltar"]) ? $_POST["valoravoltar"] : "";
            $mesesAVoltar = isset($_POST["mesesavoltar"]) ? $_POST["mesesavoltar"] : "";
            $entrada = isset($_POST["idp"]) ? true : false;
        
            $valuesArr = orquestrator(floatval($valorFinanciado), floatval($valorFinal),
            floatval($parcelamento), $entrada, floatval($taxaJuros));

            echo '
            <main>
                <ul class="list-info">
                    <li>Valor financiado: R$ ' . $valuesArr[10] . '</li>
                    <li>Taxa Real: ' . $valuesArr[3] . '%</li>
                    <li>Iterações: ' . $valuesArr[4] . '</li>
                </ul>
            </main>
        
            <table class="table">
                <h1>Tabela Price</h1>
                <thead>
                    <tr>
                        <th scope="col">Mês</th>
                        <th scope="col">Prestação</th>
                        <th scope="col">Juros</th>
                        <th scope="col">Amortização</th>
                        <th scope="col">Saldo Devedor</th>
                    </tr>
                </thead>
                <tbody class="table-result">';
                for ($i = 0; $i < count($valuesArr[12]); $i++) {
                    echo '
                    <tr>
                        <td>' . ($i + 1) . '</td>
                        <td>R$ ' . $valuesArr[11] . '</td>
                        <td>R$ ' . $valuesArr[12][$i] . '</td>
                        <td>R$ ' . $valuesArr[14][$i] . '</td>
                        <td>R$ ' . $valuesArr[15][$i] . '</td>
                    </tr>
                    ';
                }
                echo '
                </tbody>
                <tfoot class="table-result-final">
                    <tr>
                        <td>Total</td>
                        <td>R$ ' . $valuesArr[10] . '</td>
                        <td>R$ ' . $valuesArr[13] . '</td>
                        <td>R$ ' . $valuesArr[2] . '</td>
                        <td>R$ 0.00</td>
                    </tr>
                </tfoot>
            </table>
            ';
        }
    ?>
</body>
</html>
