<?php
$data = ['numerator' => '', 'denominator' => '', 'actual_value' => '50'];
if (isset($data['numerator']) && isset($data['denominator']) && $data['denominator'] != 0) {
    if (isset($data['actual_value'])) {
        $actualValue = $data['actual_value'];
        echo "Branch 1: " . $actualValue;
    } else {
        $actualValue = ($data['numerator'] / $data['denominator']) * 100;
        echo "Branch 2: " . $actualValue;
    }
} elseif (isset($data['actual_value'])) {
    $actualValue = $data['actual_value'];
    echo "Branch 3: " . $actualValue;
} else {
    echo "Exception";
}
