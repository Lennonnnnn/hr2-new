<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/adminlogin.php");
    exit();
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Performance Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 50px; }
        .result-card { transition: all 0.3s ease; }
        .result-card:hover { transform: scale(1.05); }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4 text-center">Performance Management System</h1>
        <form method="post" action="" class="mb-4">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="sales" class="form-label">Sales Performance (0-100):</label>
                    <input type="number" class="form-control" id="sales" name="sales" min="0" max="100" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="customer_satisfaction" class="form-label">Customer Satisfaction (0-100):</label>
                    <input type="number" class="form-control" id="customer_satisfaction" name="customer_satisfaction" min="0" max="100" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="attendance" class="form-label">Attendance (0-100):</label>
                    <input type="number" class="form-control" id="attendance" name="attendance" min="0" max="100" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="project_completion" class="form-label">Project Completion Rate (0-100):</label>
                    <input type="number" class="form-control" id="project_completion" name="project_completion" min="0" max="100" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="team_collaboration" class="form-label">Team Collaboration (0-100):</label>
                    <input type="number" class="form-control" id="team_collaboration" name="team_collaboration" min="0" max="100" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="learning_development" class="form-label">Learning & Development (0-100):</label>
                    <input type="number" class="form-control" id="learning_development" name="learning_development" min="0" max="100" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Evaluate Performance</button>
        </form>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $sales = $_POST["sales"];
            $customer_satisfaction = $_POST["customer_satisfaction"];
            $attendance = $_POST["attendance"];
            $project_completion = $_POST["project_completion"];
            $team_collaboration = $_POST["team_collaboration"];
            $learning_development = $_POST["learning_development"];

            // Rule-based AI system
            class PerformanceAI {
                private $rules;
                private $weights;

                public function __construct() {
                    $this->rules = [
                        'sales' => [
                            ['range' => [90, 100], 'score' => 100],
                            ['range' => [75, 89], 'score' => 80],
                            ['range' => [60, 74], 'score' => 60],
                            ['range' => [0, 59], 'score' => 40]
                        ],
                        'customer_satisfaction' => [
                            ['range' => [90, 100], 'score' => 100],
                            ['range' => [75, 89], 'score' => 80],
                            ['range' => [60, 74], 'score' => 60],
                            ['range' => [0, 59], 'score' => 40]
                        ],
                        'attendance' => [
                            ['range' => [95, 100], 'score' => 100],
                            ['range' => [85, 94], 'score' => 80],
                            ['range' => [75, 84], 'score' => 60],
                            ['range' => [0, 74], 'score' => 40]
                        ],
                        'project_completion' => [
                            ['range' => [90, 100], 'score' => 100],
                            ['range' => [75, 89], 'score' => 80],
                            ['range' => [60, 74], 'score' => 60],
                            ['range' => [0, 59], 'score' => 40]
                        ],
                        'team_collaboration' => [
                            ['range' => [90, 100], 'score' => 100],
                            ['range' => [75, 89], 'score' => 80],
                            ['range' => [60, 74], 'score' => 60],
                            ['range' => [0, 59], 'score' => 40]
                        ],
                        'learning_development' => [
                            ['range' => [90, 100], 'score' => 100],
                            ['range' => [75, 89], 'score' => 80],
                            ['range' => [60, 74], 'score' => 60],
                            ['range' => [0, 59], 'score' => 40]
                        ]
                    ];

                    $this->weights = [
                        'sales' => 0.25,
                        'customer_satisfaction' => 0.20,
                        'attendance' => 0.15,
                        'project_completion' => 0.20,
                        'team_collaboration' => 0.10,
                        'learning_development' => 0.10
                    ];
                }

                public function evaluatePerformance($data) {
                    $totalScore = 0;
                    $scores = [];

                    foreach ($data as $metric => $value) {
                        $score = $this->applyRule($metric, $value);
                        $weightedScore = $score * $this->weights[$metric];
                        $totalScore += $weightedScore;
                        $scores[$metric] = [
                            'raw' => $value,
                            'score' => $score,
                            'weighted' => $weightedScore
                        ];
                    }

                    $overallRating = $this->getOverallRating($totalScore);

                    return [
                        'scores' => $scores,
                        'total_score' => $totalScore,
                        'overall_rating' => $overallRating
                    ];
                }

                private function applyRule($metric, $value) {
                    foreach ($this->rules[$metric] as $rule) {
                        if ($value >= $rule['range'][0] && $value <= $rule['range'][1]) {
                            return $rule['score'];
                        }
                    }
                    return 0;
                }

                private function getOverallRating($score) {
                    if ($score >= 90) return "Outstanding";
                    if ($score >= 80) return "Excellent";
                    if ($score >= 70) return "Good";
                    if ($score >= 60) return "Satisfactory";
                    return "Needs Improvement";
                }
            }

            $ai = new PerformanceAI();
            $result = $ai->evaluatePerformance([
                'sales' => $sales,
                'customer_satisfaction' => $customer_satisfaction,
                'attendance' => $attendance,
                'project_completion' => $project_completion,
                'team_collaboration' => $team_collaboration,
                'learning_development' => $learning_development
            ]);

            echo "<div class='mt-4 card result-card'>";
            echo "<div class='card-body'>";
            echo "<h4 class='card-title'>Performance Evaluation Result</h4>";
            echo "<div class='row'>";
            foreach ($result['scores'] as $metric => $data) {
                echo "<div class='col-md-4 mb-3'>";
                echo "<h5>" . ucwords(str_replace('_', ' ', $metric)) . "</h5>";
                echo "<p>Raw Score: {$data['raw']}</p>";
                echo "<p>Evaluated Score: {$data['score']}</p>";
                echo "<p>Weighted Score: " . number_format($data['weighted'], 2) . "</p>";
                echo "</div>";
            }
            echo "</div>";
            echo "<hr>";
            echo "<h5>Total Score: " . number_format($result['total_score'], 2) . "</h5>";
            echo "<h4>Overall Performance: <strong>{$result['overall_rating']}</strong></h4>";
            echo "</div>";
            echo "</div>";
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
