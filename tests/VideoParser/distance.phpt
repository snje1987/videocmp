--TEST--
distance test
--CAPTURE_STDIO--
STDOUT
--FILE--
<?php

use Org\Snje\Videocmp\VideoParser;

require __DIR__ . '/../bootstrap.php';

$input = [
    [
        [0, 0, 0, 0],
        [0, 0, 0, 0],
        0
    ],
    [
        [65535, 65535, 65535, 65535],
        [65535, 65535, 65535, 65535],
        0
    ],
    [
        [0, 0, 0, 1],
        [0, 0, 0, 0],
        0
    ],
    [
        [65535, 65535, 65535, 65535],
        [65535, 65535, 65535, 65534],
        0
    ],
    [
        [0, 0, 0, 1],
        [0, 0, 0, 0],
        1
    ],
    [
        [65535, 65535, 65535, 65535],
        [65535, 65535, 65535, 65534],
        1
    ],
    [
        [31999, 65484, 52424, 51400],
        [59124, 62684, 56520, 51400],
        2
    ],
    [
        ['53199', '53199', '53071', '20227'], [53199, 53199, 53199, 52995], 2
    ],
];

foreach ($input as $values) {
    $ret = VideoParser::withinDistance($values[0], $values[1], $values[2]);
    var_dump($ret);
}
?>
--EXPECT--
bool(true)
bool(true)
bool(false)
bool(false)
bool(true)
bool(true)
bool(false)
bool(true)