<?php

//This code draws iterations of a cellular automata.
//The coding for the update function comes from Wolfram's
//"A New Kind of Science",
//http://www.wolframscience.com/nksonline/page-24
//
//This script looks for the following parameters, all of which are optional:
//cells -- the number of cells
//steps -- the number of times to iterate the update function on the cells
//initial -- the initial values for the cells
//seed -- the seed for the random number generator used to
//         fill in the initial values of the cells if initial isn't provided
//rule -- coding of the update function
//
//David Faden, dfaden@cs.iastate.edu
//April 25, 2004

require_once('get_param.php');

//TODO: Warn the user when a parameter is invalid.
$numberOfCells = int_param_with_default_range('cells', 100, 1, 1000);
$numberOfSteps = int_param_with_default_range('steps', 100, 1, 1000);
$rule = int_param_with_default('rule', 110);
$initialString = '';
if (isset($_GET['initial']) && preg_match('/^[01]+$/', $_GET['initial'])) {
    $initialString = $_GET['initial'];
}
$seed = int_param_with_default('seed', 0);

if (get_with_default('image', 'no') == 'yes') {
    header('Content-type: imager/png', true);
    //TODO: Make it so that when users select
    //"Open image in new window" or the equivalent from their
    //browser window, the image will indeed be opened within
    //the window. The RFC for Content-Disposition suggests
    //that the following would work, but it doesn't.
    //Currently, Safari downloads the image to disk rather
    //than displaying it inline. Firefox does the same.
    //At least with the Content-Disposition header
    //we can specify a reasonable name for the downloaded file.
    //See http://www.faqs.org/rfcs/rfc2183
    //
    header("Content-Disposition: inline; filename=\"rule$rule.png\"");

    $image = imageCreate($numberOfCells, $numberOfSteps);
    $white = imageColorAllocate($image, 250, 250, 250);
    $black = imageColorAllocate($image, 0, 0, 0);

    $cells = array();
    if ($initialString) {
        $length = strlen($initialString);
        for ($i = 0; $i < $numberOfCells; $i++) {
            $cells[$i] = ('1' == $initialString[$i % $length]) ? 1 : 0;
        }
    } else {
        if ($seed) {
            mt_srand(intval($seed));
        }
        for ($c = 0; $c < $numberOfCells; $c++) {
            $cells[$c] = mt_rand(0, 1);
        }
    }

    for ($step = 0; $step < $numberOfSteps; $step++) {
        $oldCells = $cells;
        for ($c = 0; $c < $numberOfCells; $c++) {
            if ($c != 0) {
                $left = $oldCells[$c - 1];
            } else {
                $left = $oldCells[$numberOfCells - 1];
            }

            if ($c != ($numberOfCells - 1)) {
                $right = $oldCells[$c + 1];
            } else {
                $right = $oldCells[0];
            }

            $neighorhoodValue = ($left << 2) | ($oldCells[$c] << 1) | $right;
            $cells[$c] = ($rule >> $neighorhoodValue) & 1;
            if ($cells[$c] == 1) {
                imageSetPixel($image, $c, $step, $black);
            }
        }
    }

    imagePNG($image);
    imageDestroy($image);
} else {
    header('Content-type: text/html', true);
    echo <<<TOP
<html>
<head><title>Rule $rule</title></head>
<body>
<div style="width:450px">
<h1>Rule $rule</h1>
<img src="wolfram_ca.php?cells=$numberOfCells&steps=$numberOfSteps&rule=$rule&seed=$seed&initial=$initialString&image=yes" width="$numberOfCells" height="$numberOfSteps">
<br>
TOP;
    echo "Rule $rule maps ";
    for ($neighborhood = 0; $neighborhood < 8; $neighborhood++) {
        if ($neighborhood != 0) {
            echo ', ';
        }
        if ($neighborhood == 7) {
            echo 'and ';
        }
        $neighborhoodString = str_pad(decbin($neighborhood), 3, '0', STR_PAD_LEFT);
        //$neighborhoodString = substr($neighborhoodString, -3);
        $value = ($rule >> $neighborhood) & 1;
        echo "$neighborhoodString&nbsp;to&nbsp;$value";
    }
    echo '.';
    echo <<<BOTTOM
<h2>Change the parameters</h2>
<form method="get">
Rule: <input type="text" value="$rule" name="rule">
<br>
Number of cells: <input type="text" value="$numberOfCells" name="cells">
<br>
Number of steps: <input type="text" value="$numberOfSteps" name="steps">
<br>
Random seed: <input type="text" value="$seed" name="seed">
<br>
Initial cell values: <input type="text" value="$initialString" name="initial" size="35">
&nbsp;&nbsp;<input type="submit">
</form>
<hr>
<p>If no initial value is provided, the cells are set to random values. If an initial value isn't provided for every cell, the provided sequence of values is repeated until every cell is provided for, going from left to right.
</p>
<p>
The seed is used to seed the pseudo-random number generator. If no non-zero seed is provided, the system will "randomly" choose one. It's useful to specify a seed here if you wish the results to be easily reproducible.
</p>
<p>
More information on cellular automata and their applications can be found in Stephen Wolfram's
<a href="http://www.wolframscience.com/nksonline/toc.html">A New Kind of Science</a>.
</p>
</div>
</body>
</html>
BOTTOM;
}
