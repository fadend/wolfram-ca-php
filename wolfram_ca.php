<?php

// This code draws iterations of a cellular automata.
// The coding for the update function comes from Wolfram's
// "A New Kind of Science",
// http://www.wolframscience.com/nksonline/page-24
//
// This script looks for the following parameters, all of which are optional:
//   rule -- coding of the update function
//   cells -- the number of cells
//   steps -- the number of times to iterate the update function on the cells
//   start_type -- how to initialize the cells
//   initial -- the initial values for the cells
//   seed -- the seed for the random number generator used to
//         fill in the initial values of the cells if initial isn't provided

declare(strict_types=1);

require_once 'get_param.php';

const START_TYPES = ['left', 'middle', 'multiple', 'right'];

function printRuleDescriptionHtml(int $rule)
{
    echo "Rule $rule maps ";
    for ($neighborhood = 0; $neighborhood < 8; $neighborhood++) {
        if ($neighborhood != 0) {
            echo ', ';
        }
        if ($neighborhood == 7) {
            echo 'and ';
        }
        $neighborhoodString = str_pad(decbin($neighborhood), 3, '0', STR_PAD_LEFT);
        $value = ($rule >> $neighborhood) & 1;
        echo "$neighborhoodString&nbsp;to&nbsp;$value";
    }
    echo '.';
}

function createInitialCells(string $startType, int $numberOfCells, string $initialString = '', int $seed = 0): array
{
    $cells = array_fill(0, $numberOfCells, 0);
    switch ($startType) {
        case 'left':
            $cells[0] = 1;
            break;
        case 'right':
            $cells[$numberOfCells - 1] = 1;
            break;
        case 'middle':
            $cells[intdiv($numberOfCells, 2)] = 1;
            break;
        case 'multiple':
            if ($initialString) {
                $length = strlen($initialString);
                for ($i = 0; $i < $numberOfCells; $i++) {
                    $cells[$i] = intval('1' == $initialString[$i % $length]);
                }
            } else {
                if ($seed) {
                    mt_srand($seed);
                }
                for ($c = 0; $c < $numberOfCells; $c++) {
                    $cells[$c] = mt_rand(0, 1);
                }
            }
            break;
        default:
            throw new InvalidArgumentException("Unknown startType: $startType");
    }
    return $cells;
}

function sendCellularAutomataStepsImage(array $cellsInitialState, int $rule, int $numberOfSteps)
{
    $cells = $cellsInitialState;
    $numberOfCells = count($cells);

    $image = imageCreate($numberOfCells, $numberOfSteps);
    // Set the background to white by allocating white first.
    imageColorAllocate($image, 255, 255, 255);
    $black = imageColorAllocate($image, 0, 0, 0);

    for ($step = 0; $step < $numberOfSteps; $step++) {
        $oldCells = $cells;
        for ($c = 0; $c < $numberOfCells; $c++) {
            $leftNeighbor = ($c != 0) ? $c - 1 : $numberOfCells - 1;
            $left = $oldCells[$leftNeighbor];

            $rightNeighbor = ($c + 1) % $numberOfCells;
            $right = $oldCells[$rightNeighbor];

            $neighorhoodValue = ($left << 2) | ($oldCells[$c] << 1) | $right;
            $cells[$c] = ($rule >> $neighorhoodValue) & 1;
            if ($cells[$c]) {
                imageSetPixel($image, $c, $step, $black);
            }
        }
    }

    header('Content-type: image/png', true);
    header("Content-Disposition: inline; filename=\"rule$rule.png\"");

    imagePNG($image);
    imageDestroy($image);
}

//TODO: Warn the user when a parameter is invalid.
$numberOfCells = int_param_with_default_range('cells', 200, 1, 2000);
$numberOfSteps = int_param_with_default_range('steps', 200, 1, 2000);
$rule = int_param_with_default('rule', 110);
$startType = get_with_default('start_type', 'multiple');
if (!in_array($startType, START_TYPES, true)) {
    $startType = 'multiple';
}
$initialString = '';
if (isset($_GET['initial']) && preg_match('/^[01]+$/', $_GET['initial'])) {
    $initialString = $_GET['initial'];
}
$seed = int_param_with_default('seed', 0);

if (get_with_default('image', 'no') == 'yes') {
    $cells = createInitialCells($startType, $numberOfCells, $initialString, $seed);

    sendCellularAutomataStepsImage($cells, $rule, $numberOfSteps);
} else {
    header('Content-type: text/html', true);
    ?>
<html>
<head><title>Rule <?= $rule ?></title></head>
<body>
<div style="width:450px">
<h1>Rule <?= $rule ?></h1>
<img
  src="<?= "wolfram_ca.php?cells=$numberOfCells&steps=$numberOfSteps&rule=$rule&start_type=$startType&seed=$seed&initial=$initialString&image=yes" ?>"
  width="<?= $numberOfCells ?>"
  height="<?= $numberOfSteps ?>"
  alt="Image of rule <?= $rule ?>"
>
<br>
<?php printRuleDescriptionHtml($rule); ?>
<h2>Change the parameters</h2>
<form method="get">
Rule: <input type="text" name="rule" value="<?= $rule ?>">
<br>
Number of cells: <input type="text" name="cells" value="<?= $numberOfCells ?>">
<br>
Number of steps: <input type="text" name="steps" value="<?= $numberOfSteps ?>">
<br>
Initial state:
<br>
<label>
  <input type="radio" name="start_type" id="start-type-multiple" value="multiple"
    <?= ($startType == 'multiple') ? 'checked' : '' ?> >
  Start with multiple cells "on" (selected at random or from given initialization string)
</label>
<br>
<label>
  <input type="radio" name="start_type" value="left"
    <?= ($startType == 'left') ? 'checked' : '' ?> >
  Start with only the leftmost cell "on".
</label>
<br>
<label>
  <input type="radio" name="start_type" value="middle"
    <?= ($startType == 'middle') ? 'checked' : '' ?> >
  Start with only the middle cell "on".
</label>
<br>
<label>
  <input type="radio" name="start_type" value="right"
    <?= ($startType == 'right') ? 'checked' : '' ?> >
  Start with only the rightmost cell "on".
</label>
<div id="options-for-multiple">
Random seed: <input type="text" name="seed" value="<?= $seed ?>">
<br>
Initial cell values: <input type="text" name="initial" value="<?= $initialString ?>" size="35">
</div>
<br>
<input type="submit">
</form>
<hr>
<p>
  If you select the option to start with (potentially) multiple cells initially "on", then if
  provided, the "Initial cell values" string will be repeated to fill the entire row of cells.
  If you leave "Initial cell values" empty, the cells will be pseudo randomly initialized
  instead.
</p>
<p>
  Set a non-zero seed if you want the pseudo random initialization to be consistent across reloads.
</p>
<p>
  More information on cellular automata and their applications can be found in Stephen Wolfram's
  <a href="http://www.wolframscience.com/nksonline/toc.html">A New Kind of Science</a>.
  This page uses the encoding described in
  <a href="https://www.wolframscience.com/nks/p51--the-search-for-general-features/">Chapter 3</a>
  of his book.
</p>
</div>
<script>
  (function() {
    const optionsForMultipleDiv = document.getElementById('options-for-multiple');
    const multipleRadio = document.getElementById('start-type-multiple');
    let hideShowMultipleOptions = () => {
        optionsForMultipleDiv.style.display = multipleRadio.checked ? '' : 'none';
    };
    for (let radio of [...document.getElementsByName('start_type')]) {
        radio.addEventListener('click', hideShowMultipleOptions);
    }
    hideShowMultipleOptions();
  })();
</script>
</body>
</html>
<?php } ?>