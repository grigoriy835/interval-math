<?php
namespace IM;

class IntervalMath
{
    const A_OR_B = 0;
    const A_AND_B = 1;
    const A_MINUS_B = 2;

    const LC_SIDE = 0;
    const LC_VALUE = 1;
    const LC_DIR = 2;

    // если в A будет > 3 элементов, стабильно дёрнет стоковый bool.
    // если в А > 3 и B > 3 надо юзать эту функцию 100%
    public static function boolMinusBigIntervals(& $A, & $B)
    {
        if (count($B) == 0) {
            return $A;
        }

        if (count($A) == 0) {
            return [];
        }

        $result = [];
        $line = [];

        foreach ($A as $id => $a) {
            if ($a[0] >= $a[1]) {
                continue;
            }
            $line[] = [
                static::LC_DIR => 1,
                static::LC_SIDE => 0,
                static::LC_VALUE => $a[0]
            ];

            $line[] = [
                static::LC_DIR => 0,
                static::LC_SIDE => 0,
                static::LC_VALUE => $a[1]
            ];
        }

        foreach ($B as $id => $b) {
            if ($b[0] >= $b[1]) {
                continue;
            }
            $line[] = [
                static::LC_DIR => 1,
                static::LC_SIDE => 1,
                static::LC_VALUE => $b[0]
            ];

            $line[] = [
                static::LC_DIR => 0,
                static::LC_SIDE => 1,
                static::LC_VALUE => $b[1]
            ];
        }

        usort($line, function ($p1, $p2) {
            $p1 = $p1[static::LC_VALUE];
            $p2 = $p2[static::LC_VALUE];
            return $p1 <=> $p2;
        });

        $intervalOpenValue = null;
        $intervalOpenSide = null;
        $openedBSidesCount = 0;
        $openedASidesCount = 0;

        // здесь дальше какой то лютый пиздос, писано под наркотиками
        foreach ($line as $p) {
            $value = $p[static::LC_VALUE];
            if ($p[static::LC_DIR] == 1) {
                //открываемся
                if ($p[static::LC_SIDE] == 1) {
                    // открываемся в B
                    $openedBSidesCount++;

                    if ($openedBSidesCount == 1 &&
                        !is_null($intervalOpenValue) &&
                        ($intervalOpenSide == 0 || $openedASidesCount)
                    ) {
                        if ($intervalOpenValue < $value) {
                            $result[] = [$intervalOpenValue, $value];
                        }
                        $intervalOpenSide = null;
                        $intervalOpenValue = null;
                    }
                } else {
                    // открываемся в A
                    $openedASidesCount++;

                    if ($openedASidesCount == 1 && $openedBSidesCount == 0) {
                        $intervalOpenValue = $value;
                        $intervalOpenSide = 0;
                    }
                }
            } else {
                // закрываемся

                if ($p[static::LC_SIDE] == 1) {
                    // закрываемся в B
                    $openedBSidesCount--;

                    if ($openedBSidesCount == 0) {
                        $intervalOpenValue = $value;
                        $intervalOpenSide = 1;
                    }
                } else {
                    // закрываемся в A
                    $openedASidesCount--;

                    if ($openedASidesCount == 0 && $openedBSidesCount == 0 && !is_null($intervalOpenValue)) {
                        if ($intervalOpenValue < $value) {
                            $result[] = [$intervalOpenValue, $value];
                        }
                        $intervalOpenValue = null;
                        $intervalOpenSide = null;
                    }
                }
            }
        }

        return static::mergeIntersectedIntervals($result);
    }

    public static function boolSlow($A, $B, $mode, $sortResult = true)
    {
        foreach ($A as $id => $i) {
            if (empty($i)) {
                array_splice($A, $id, 1);
            }
        }

        foreach ($B as $id => $i) {
            if (empty($i)) {
                array_splice($B, $id, 1);
            }
        }

        return static::bool($A, $B, $mode, $sortResult);
    }

    public static function bool(&$A, &$B, $mode, $sortResult = true)
    {
        $la = count($A);
        $lb = count($B);

        if ($la == 1 && $lb == 1) { // 50% cases
            switch ($mode) {
                case IntervalMath::A_AND_B:
                    $l = max($A[0][0], $B[0][0]);
                    $r = min($A[0][1], $B[0][1]);
                    if ($l >= $r) {
                        return [];
                    } else {
                        return [[$l, $r]];
                    }
                case IntervalMath::A_OR_B:
                    if (static::isIntersect($A[0][0], $A[0][1], $B[0][0], $B[0][1])) {
                        $l = min($A[0][0], $B[0][0]);
                        $r = max($A[0][1], $B[0][1]);
                        return [[$l, $r]];
                    } else {
                        return [$A[0], $B[0]];
                    }
                case IntervalMath::A_MINUS_B:
                    if (static::isIntersect($A[0][0], $A[0][1], $B[0][0], $B[0][1])) {
                        $ls = $B[0][0] - $A[0][0];
                        $rs = $A[0][1] - $B[0][1];

                        if ($ls > 0 && $rs > 0) {
                            return [
                                [$A[0][0], $B[0][0]],
                                [$B[0][1], $A[0][1]]
                            ];
                        } else if ($ls > 0) {
                            return [[$A[0][0], $B[0][0]]];
                        } else if ($rs > 0) {
                            return [[$B[0][1], $A[0][1]]];
                        } else {
                            return [];
                        }
                    } else {
                        return $A;
                    }
            }
        }

        $result = [];
//        $key = $la . 'x' . $lb;
//        if (empty($_SERVER['bool'])) {
//            $_SERVER['bool'] = [];
//        }
//
//        if (empty($_SERVER['bool'][$key])) {
//            $_SERVER['bool'][$key] = 0;
//        }
//        $_SERVER['bool'][$key]++;

        if ($mode == static::A_AND_B) {
            for ($ai = 0; $ai < $la; $ai++) {
                $a = $A[$ai];
                for ($bi = 0; $bi < $lb; $bi++) {
                    $b = $B[$bi];

                    if (static::isIntersect($a[0], $a[1], $b[0], $b[1])) {
                        $s = max($a[0], $b[0]);
                        $e = min($a[1], $b[1]);
                        $result[] = [$s, $e];
                    }
                }
            }
        }

        if ($mode == static::A_OR_B) {
            $mergedIntervals = array_merge($A, $B);
            $result = static::mergeIntersectedIntervals($mergedIntervals);
        }

        if ($mode == static::A_MINUS_B) {
            if ($lb == 0) {
                return $A;
            }

            if ($la == 0) {
                return [];
            }

            if ($la >= 3 && $lb >= 3) {
                return static::boolMinusBigIntervals($A, $B);
            }

            $AClone = $A; // clone A
            for ($ai = 0; $ai < $la; $ai++) {
                $a = $AClone[$ai];
                $wasIntersected = false;

                for ($bi = 0; $bi < $lb; $bi++) {
                    $b = $B[$bi];

                    if (static::isIntersect($a[0], $a[1], $b[0], $b[1], true)) {
                        $wasIntersected = true;
                        $ls = min($a[0], $b[0]);
                        $le = min($a[1], $b[0]);
                        $rs = max($a[0], $b[1]);
                        $re = max($a[1], $b[1]);

                        $localResult = [];

                        if ($ls < $le) {
                            $localResult[] = [$ls, $le];
                        }

                        if ($rs < $re) {
                            $localResult[] = [$rs, $re];
                        }

                        if (!empty($localResult)) {
                            array_splice($AClone, $ai, 1);
                            foreach ($localResult as $localRange) {
                                $AClone[] = $localRange;
                            }

                            $la = count($AClone);
                            $ai = -1;
                            break;
                        }
                    }
                }

                if (!$wasIntersected) {
                    $result[] = $a;
                }
            }

            $result = static::mergeIntersectedIntervals($result);
        }

        if ($sortResult) {
            return static::sortIntervals($result);
        }

        return $result;
    }

    public static function sortIntervals(& $intervals)
    {
        usort($intervals, function ($a, $b) {
            return $a[0] > $b[0] ? 1 : ($a[0] < $b[0] ? -1 : 0);
        });

        return $intervals;
    }

    /** Performance proved */
    public static function mergeIntersectedIntervals(& $intervals)
    {
        $length = count($intervals);

        for ($i = 0; $i < $length; $i++) {
            if ($intervals[$i][0] >= $intervals[$i][1]) {
                $length--;
                array_splice($intervals, $i--, 1);
            }
        }

        for ($i = 0; $i < $length; $i++) {
            for ($j = $i + 1; $j < $length; $j++) {
                if (static::isIntersect(
                    $intervals[$i][0],
                    $intervals[$i][1],
                    $intervals[$j][0],
                    $intervals[$j][1])
                ) {
                    $s1 = min($intervals[$i][0], $intervals[$i][1], $intervals[$j][0], $intervals[$j][1]);
                    $e1 = max($intervals[$i][0], $intervals[$i][1], $intervals[$j][0], $intervals[$j][1]);

                    $intervals[$i] = [$s1, $e1];
                    $i = -1;
                    $length--;
                    array_splice($intervals, $j, 1);
                    break;
                }
            }
        }

        return static::sortIntervals($intervals);
    }

    /** Performance proved */
    public static function isIntersect($s1, $e1, $s2, $e2, $isOpen = false)
    {
        if ($isOpen) {
            return $e1 > $s2 && $e2 > $s1;
        }
        return $e1 >= $s2 && $e2 >= $s1;
    }

    /** Performance proved */
    public static function getRangeLength(array & $ranges)
    {
        $result = 0;
        foreach ($ranges as $range) {
            $result += $range[1] - $range[0];
        }
        return $result;
    }

    public static function getRangeLengthSlow(array $ranges)
    {
        return static::getRangeLength($ranges);
    }
}