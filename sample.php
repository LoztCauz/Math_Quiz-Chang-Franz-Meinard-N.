<?php
session_start();

// Connect to MySQL database
$host = 'localhost';
$user = 'root'; // Default user for XAMPP
$password = ''; // Default password is empty for XAMPP
$dbname = 'math_quiz';
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

// Function to generate a math problem (same as original)
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
    } elseif (isset($_POST['save_score'])) {
        $username = $_POST['username'];
        $score = $_SESSION['quiz']['score'];
        $correct = $_SESSION['quiz']['correct'];
        $wrong = $_SESSION['quiz']['wrong'];

        $stmt = $conn->prepare("INSERT INTO leaderboard (username, score, correct, wrong) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siii", $username, $score, $correct, $wrong);
        $stmt->execute();
        $stmt->close();

        unset($_SESSION['quiz']);
    }
}

$gameOver = isset($_SESSION['quiz']['problems']) && empty($_SESSION['quiz']['problems']);

// Retrieve leaderboard
$leaderboard = $conn->query("SELECT * FROM leaderboard ORDER BY score DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Math Quiz with Leaderboard</title>
    <!-- Add your existing styles here -->
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
            <input type="text" name="username" placeholder="Enter your name" required>
            <button class="btn" type="submit" name="save_score">Save Score</button>
        </form>
    <?php elseif (!isset($_SESSION['quiz'])): ?>
        <!-- Add your existing quiz form here -->
    <?php endif; ?>

    <h2>Leaderboard</h2>
    <table border="1">
        <tr>
            <th>Rank</th>
            <th>Name</th>
            <th>Score</th>
            <th>Correct</th>
            <th>Wrong</th>
            <th>Date</th>
        </tr>
        <?php $rank = 1; while ($row = $leaderboard->fetch_assoc()): ?>
            <tr>
                <td><?php echo $rank++; ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo $row['score']; ?></td>
                <td><?php echo $row['correct']; ?></td>
                <td><?php echo $row['wrong']; ?></td>
                <td><?php echo $row['created_at']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
