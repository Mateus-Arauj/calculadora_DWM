<?php
$dbFile = 'calculadora.db';
$logFile = fopen('log.txt', 'a') or die('Erro ao abrir o arquivo de log');

try {
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('CREATE TABLE IF NOT EXISTS historico (
        id INTEGER PRIMARY KEY,
        num1 REAL,
        num2 REAL,
        op TEXT,
        result REAL,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
}


function logOperacao($num1, $num2, $op, $resultado, $mensagem) {
    global $logFile;
    $logData = date('Y-m-d H:i:s') . " - Operação: $num1 $op $num2 = $resultado - $mensagem\n";
    fwrite($logFile, $logData);
}
function calcular($num1, $num2, $op) {
    global $db;

    try {
        switch ($op) {
            case '+':
                $resultado = $num1 + $num2;
                break;
            case '-':
                $resultado = $num1 - $num2;
                break;
            case '*':
                $resultado = $num1 * $num2;
                break;
            case '/':
                if ($num2 != 0) {
                    $resultado = $num1 / $num2;
                } else {
                    return 'Erro: divisão por zero';
                }
                break;
            default:
                $mensagem = 'Operação inválida';
                logOperacao($num1, $num2, $op, $resultado, $mensagem);
                return $mensagem;
        }

        $stmt = $db->prepare('INSERT INTO historico (num1, num2, op, result) VALUES (?, ?, ?, ?)');
        $stmt->execute([$num1, $num2, $op, $resultado]);

        $mensagem = 'Operação realizada com sucesso';
        logOperacao($num1, $num2, $op, $resultado, $mensagem);
        return $resultado;
    } catch (Exception $e) {
        $mensagem = 'Erro no cálculo: ' . $e->getMessage();
        logOperacao($num1, $num2, $op, $resultado, $mensagem);
        return $mensagem;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['num1'], $_POST['num2'], $_POST['op'])) {
    $num1 = $_POST['num1'];
    $num2 = $_POST['num2'];
    $op = $_POST['op'];
    $resultado = calcular($num1, $num2, $op);
}
fclose($logFile); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora com SQLite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            clifford: '#da373d',
          }
        }
      }
    }
    function validarNumero(input) {
            input.value = input.value.replace(/[^0-9.]/g, ''); //Validar números
        }
  </script>
</head>
<style>
    /* Responsividade */
    @media (max-width: 768px) {
        #result {
            font-size: 0.8rem;
        }
        #result th,
        #result td {
            padding: 0.5rem; 
        }
    }

    @media (max-width: 480px) {
        #result {
            font-size: 0.7rem; 
        }
        #result th,
        #result td {
            padding: 0.3rem; 
        }
    }
</style>

<body class="bg-blue-200">
    <div class="container mx-auto p-4">
        <div class="w-full items-center">
            <h1 class="text-4xl font-bold mb-4">Calculadora</h1>
        </div>
        <form class="flex flex-col space-y-4" method="post" action="">
        <label for="num1" class="text-lg">Número 1:</label>
        <input type="number" name="num1" id="num1" required oninput="validarNumero(this)"
            class="border border-gray-300 p-2 rounded-md focus:outline-none focus:border-blue-500">

        <label for="num2" class="text-lg">Número 2:</label>
        <input type="number" name="num2" id="num2" required oninput="validarNumero(this)"
            class="border border-gray-300 p-2 rounded-md focus:outline-none focus:border-blue-500">

        <label for="op" class="text-lg">Operação (+, -, *, /):</label>
        <input type="text" name="op" id="op" required
            class="border border-gray-300 p-2 rounded-md focus:outline-none focus:border-blue-500">

        <button type="submit"
            class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:shadow-outline-blue">
            Calcular
        </button>
    </form>
</div>


    <?php if (isset($resultado)): ?>
        <p class = "mt-5 mb-5 ml-5">Resultado: <?php echo $resultado; ?></p>
    <?php endif; ?>
    <div class="flex flex-row w-full items-center">
        <h2 class="text-2xl font-bold mb-4 ml-4">Histórico</h2>
        <button class="bg-blue-400 ml-4 px-3 py-1 rounded text-white" onclick="toggleVisibility()">Mostrar</button>
    </div>
    <table class="min-w-full border border-gray-300 divide-y divide-gray-200" id="result">
    <thead>
        <tr>
            <th class="py-2 px-4 bg-gray-200 border-b">ID</th>
            <th class="py-2 px-4 bg-gray-200 border-b">Número 1</th>
            <th class="py-2 px-4 bg-gray-200 border-b">Número 2</th>
            <th class="py-2 px-4 bg-gray-200 border-b">Operação</th>
            <th class="py-2 px-4 bg-gray-200 border-b">Resultado</th>
            <th class="py-2 px-4 bg-gray-200 border-b">Data</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $historico = $db->query('SELECT * FROM historico ORDER BY date DESC LIMIT 10');
        foreach ($historico as $item): ?>
            <tr>
                <td class="py-2 px-4 border-b"><?php echo $item['id']; ?></td>
                <td class="py-2 px-4 border-b"><?php echo $item['num1']; ?></td>
                <td class="py-2 px-4 border-b"><?php echo $item['num2']; ?></td>
                <td class="py-2 px-4 border-b"><?php echo $item['op']; ?></td>
                <td class="py-2 px-4 border-b"><?php echo $item['result']; ?></td>
                <td class="py-2 px-4 border-b"><?php echo $item['date']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</body>
<script>
        function toggleVisibility() {
            var element = document.getElementById("result");
            element.classList.toggle("hidden");
        }
    </script>
</html>
