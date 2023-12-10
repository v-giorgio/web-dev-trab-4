<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>CDC</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Crédito Direto ao Consumidor</title>
    <link rel="stylesheet" href="./style/index.css">
    
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var hasEntradaCheckbox = document.getElementById("idp");
            var hasEntradaInput = document.getElementById("hasEntrada");

            hasEntradaCheckbox.addEventListener("change", function () {
                hasEntradaInput.value = hasEntradaCheckbox.checked ? 1 : 0;
            });

            var calculateBtn = document.getElementById("submitButton");

            calculateBtn.addEventListener("click", function (event) {
                event.preventDefault();

                // Obtenha os valores dos campos de entrada
                var numParcelas = document.getElementById("parc").value;
                var taxaJuros = document.getElementById("itax").value;
                var valorFinanciado = document.getElementById("ipv").value;
                var valorFinal = document.getElementById("ipp").value;
                var valorAVoltar = document.getElementById("valoravoltar").value;
                var mesesAVoltar = document.getElementById("mesesavoltar").value;
                var hasEntrada = document.getElementById("hasEntrada").value;

                // Crie um objeto FormData para enviar os dados ao servidor
                var formData = new FormData();
                formData.append("parc", numParcelas);
                formData.append("itax", taxaJuros);
                formData.append("ipv", valorFinanciado);
                formData.append("ipp", valorFinal);
                formData.append("valoravoltar", valorAVoltar);
                formData.append("mesesavoltar", mesesAVoltar);
                formData.append("idp", hasEntrada);

                // Faça uma solicitação AJAX para o script PHP
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "calculator.php", true);
                xhr.onload = function () {
                    if (xhr.status == 200) {
                        // Exiba a resposta do servidor (resultado do PHP)
                        console.log(xhr.responseText);
                    }
                };
                xhr.send(formData);
            });
        });
    </script>
</head>
<body>
    <fieldset id="cdcfieldset" class="main-box ui-widget-content">
        <legend class="legend-box">
            <strong>Crédito Direto ao Consumidor</strong>
        </legend>
        <form method="post">
            <?php
            $hasEntrada = 0;
            ?>
            <div class="box">
                <span class="input-group-addon" style="color: antiquewhite">$</span>
                <label for="parc">Parcelamento:</label>
                <input
                    id="parc"
                    type="number"
                    name="np"
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
                    name="tax"
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
                    name="pv"
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
                    name="pp"
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
                    name="pp"
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
                    name="pp"
                    placeholder="2"
                    min="0"
                    step="1"
                    class="form-control currency"
                /><br />

                <label for="idp">Entrada?</label>
                <input id="idp" type="checkbox" name="dp" <?php echo $hasEntrada == 1 ? 'checked' : ''; ?> /><br />
            </div>
            <div class="messages">
                <input
                    id="submitButton"
                    class="button"
                    type="submit"
                    value="Calcular"
                />
            </div>
        </form>
    </fieldset>

    <main>
        <ul class="list-info">
            <?php
            $valorFinanciado = 1000;
            $taxaReal = 5.0;
            $iteracoes = 10;
            echo "
                <li>Valor financiado: R$ " . number_format($valorFinanciado, 2) . "</li>
                <li>Taxa Real: " . number_format($taxaReal, 2) . "%</li>
                <li>Iterações: " . $iteracoes . "</li>
            ";
            ?>
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
        <tbody class="table-result">
            <?php
            for ($i = 1; $i <= 12; $i++) {
                echo "
                    <tr>
                        <td>$i</td>
                        <td>R$ 100.00</td>
                        <td>R$ 10.00</td>
                        <td>R$ 90.00</td>
                        <td>R$ 810.00</td>
                    </tr>
                ";
            }
            ?>
        </tbody>
        <tfoot class="table-result-final">
            <?php
            $totalJuros = 120.0;
            echo "
                <tr>
                    <td>Total</td>
                    <td>R$ 1200.00</td>
                    <td>R$ " . number_format($totalJuros, 2) . "</td>
                    <td>R$ 880.00</td>
                    <td>R$ 0.00</td>
                </tr>
            ";
            ?>
        </tfoot>
    </table>
</body>
</html>
