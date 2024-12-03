<?php
session_start();

// Initialize default settings
if (!isset($_SESSION['settings'])) {
    $_SESSION['settings'] = [
        'level' => 1,
        'operator' => 'addition',
        'num_items' => 5,
        'max_diff' => 10,
        'min_range' => 1,
        'max_range' => 10,
    ];
}

// Function to generate a math problem
function generateProblem($level, $operator, $min_range = null, $max_range = null, $max_diff = 10) {
    $min = $level === 3 ? $min_range : ($level === 1 ? 1 : 11);
    $max = $level === 3 ? $max_range : ($level === 1 ? 10 : 100);

    $num1 = rand($min, $max);
    $num2 = rand($min, $max);

    switch ($operator) {
        case 'subtraction':
            $answer = $num1 - $num2;
            $symbol = '-';
            break;
        case 'multiplication':
            $answer = $num1 * $num2;
            $symbol = '×';
            break;
        case 'addition':
        default:
            $answer = $num1 + $num2;
            $symbol = '+';
            break;
    }

    $choices = [$answer];
    while (count($choices) < 4) {
        $option = $answer + rand(-$max_diff, $max_diff);
        if (!in_array($option, $choices)) {
            $choices[] = $option;
        }
    }

    shuffle($choices); // Randomize choices
    return [$num1, $symbol, $num2, $answer, $choices];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_quiz'])) {
        $_SESSION['settings']['level'] = (int)$_POST['level'];
        $_SESSION['settings']['operator'] = $_POST['operator'];
        $_SESSION['settings']['num_items'] = (int)$_POST['num_items'];
        $_SESSION['settings']['max_diff'] = (int)$_POST['max_diff'];

        if ($_POST['level'] == 3) {
            $_SESSION['settings']['min_range'] = (int)$_POST['min_range'];
            $_SESSION['settings']['max_range'] = (int)$_POST['max_range'];
        }

        $_SESSION['quiz'] = [
            'problems' => [],
            'score' => 0,
            'correct' => 0,
            'wrong' => 0,
        ];

        for ($i = 0; $i < $_SESSION['settings']['num_items']; $i++) {
            $_SESSION['quiz']['problems'][] = generateProblem(
                $_SESSION['settings']['level'],
                $_SESSION['settings']['operator'],
                $_SESSION['settings']['min_range'],
                $_SESSION['settings']['max_range']
            );
        }
    } elseif (isset($_POST['answer'])) {
        if (!empty($_SESSION['quiz']['problems'])) {
            $current = array_shift($_SESSION['quiz']['problems']);
            $userAnswer = (int)$_POST['answer'];

            if ($userAnswer === $current[3]) {
                $_SESSION['quiz']['correct']++;
                $_SESSION['quiz']['score'] += 10;
            } else {
                $_SESSION['quiz']['wrong']++;
            }
        }
    } elseif (isset($_POST['restart'])) {
        unset($_SESSION['quiz']);
    }
}

$gameOver = isset($_SESSION['quiz']['problems']) && empty($_SESSION['quiz']['problems']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Math Quiz</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f9f9f9;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 600px;
        }

        h1, h2 {
            color: #333;
        }

        form {
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            background-color: #4CAF50;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .btn.red {
            background-color: #f44336;
        }

        .btn.red:hover {
            background-color: #d32f2f;
        }

        .choices button {
            width: calc(50% - 10px);
            margin: 5px;
            padding: 10px;
            font-size: 16px;
        }

        .score-board {
            margin-top: 20px;
            padding: 10px;
            background-color: #f3f4f6;
            border-radius: 8px;
        }

        .settings input, .settings select {
            display: block;
            width: calc(100% - 20px);
            padding: 8px;
            margin: 10px auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Math Quiz</h1>

        <?php if ($gameOver): ?>
            <h2>Game Over</h2>
            <div class="score-board">
                <p>Score: <?php echo $_SESSION['quiz']['score']; ?></p>
                <p>Correct: <?php echo $_SESSION['quiz']['correct']; ?></p>
                <p>Wrong: <?php echo $_SESSION['quiz']['wrong']; ?></p>
            </div>
            <form method="post">
                <button class="btn" type="submit" name="restart">Restart Quiz</button>
            </form>
        <?php else: ?>
            <?php if (!isset($_SESSION['quiz'])): ?>
                <h2>Settings</h2>
                <form method="post" class="settings">
                    <label>Level:
                        <select name="level">
                            <option value="1">Level 1 (1-10)</option>
                            <option value="2">Level 2 (11-100)</option>
                            <option value="3">Custom Level</option>
                        </select>
                    </label>

                    <label>Operator:
                        <select name="operator">
                            <option value="addition">Addition</option>
                            <option value="subtraction">Subtraction</option>
                            <option value="multiplication">Multiplication</option>
                        </select>
                    </label>

                    <label>Number of Items:</label>
                    <input type="number" name="num_items" value="5" min="1" max="20">

                    <label>Max Difference of Choices:</label>
                    <input type="number" name="max_diff" value="10" min="1" max="50">

                    <label>Custom Level: Min Range</label>
                    <input type="number" name="min_range" value="1" min="1">
                    <label>Custom Level: Max Range</label>
                    <input type="number" name="max_range" value="10" min="1">

                    <button class="btn" type="submit" name="start_quiz">Start Quiz</button>
                </form>
            <?php else: ?>
                <h2>Question</h2>
                <div>
                    <?php $current = $_SESSION['quiz']['problems'][0]; ?>
                    <p><?php echo "{$current[0]} {$current[1]} {$current[2]} = ?"; ?></p>
                </div>
                <form method="post" class="choices">
                    <?php foreach ($current[4] as $choice): ?>
                        <button class="btn" type="submit" name="answer" value="<?php echo $choice; ?>"><?php echo $choice; ?></button>
                    <?php endforeach; ?>
                </form>
                <div class="score-board">
                    <p>Score: <?php echo $_SESSION['quiz']['score']; ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
