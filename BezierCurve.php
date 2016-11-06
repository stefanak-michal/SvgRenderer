<?php
/**
 * BezierCurve
 *
 * @author Michal Stefanak
 */
class BezierCurve
{

    /**
     * Calculate the coordinate of the Bezier curve at $t = 0..1
     * 
     * @param type $p1
     * @param type $p2
     * @param type $p3
     * @param type $p4
     * @param type $t
     * @return array
     */
    private static function calc($p1, $p2, $p3, $p4, $t)
    {
        // lines between successive pairs of points (degree 1)
        $q1 = array((1 - $t) * $p1[0] + $t * $p2[0], (1 - $t) * $p1[1] + $t * $p2[1]);
        $q2 = array((1 - $t) * $p2[0] + $t * $p3[0], (1 - $t) * $p2[1] + $t * $p3[1]);
        $q3 = array((1 - $t) * $p3[0] + $t * $p4[0], (1 - $t) * $p3[1] + $t * $p4[1]);
        // curves between successive pairs of lines. (degree 2)
        $r1 = array((1 - $t) * $q1[0] + $t * $q2[0], (1 - $t) * $q1[1] + $t * $q2[1]);
        $r2 = array((1 - $t) * $q2[0] + $t * $q3[0], (1 - $t) * $q2[1] + $t * $q3[1]);
        // final curve between the two 2-degree curves. (degree 3)
        return array((1 - $t) * $r1[0] + $t * $r2[0], (1 - $t) * $r1[1] + $t * $r2[1]);
    }

    /**
     * Calculate the squared distance between two points
     * 
     * @param type $p1
     * @param type $p2
     * @return int
     */
    private static function Point_distance2($p1, $p2)
    {
        $dx = $p2[0] - $p1[0];
        $dy = $p2[1] - $p1[1];
        return $dx * $dx + $dy * $dy;
    }

    /**
     * Convert the curve to a polyline
     * 
     * @param type $p1
     * @param type $p2
     * @param type $p3
     * @param type $p4
     * @param type $tolerance
     * @return type
     */
    private static function convert($p1, $p2, $p3, $p4, $tolerance)
    {
        $t1 = 0.0;
        $prev = $p1;
        $t2 = 0.1;
        $tol2 = $tolerance * $tolerance;
        $result [] = $prev[0];
        $result [] = $prev[1];
        while ( $t1 < 1.0 ) {
            if ( $t2 > 1.0 ) {
                $t2 = 1.0;
            }
            $next = self::calc($p1, $p2, $p3, $p4, $t2);
            $dist = self::Point_distance2($prev, $next);
            while ( $dist > $tol2 ) {
                // Halve the distance until small enough
                $t2 = $t1 + ($t2 - $t1) * 0.5;
                $next = self::calc($p1, $p2, $p3, $p4, $t2);
                $dist = self::Point_distance2($prev, $next);
            }
            // the image*polygon functions expect a flattened array of coordiantes
            $result [] = $next[0];
            $result [] = $next[1];
            $t1 = $t2;
            $prev = $next;
            $t2 = $t1 + 0.1;
        }
        return $result;
    }

    /**
     * Draw a Bezier curve on an image
     * 
     * @todo vyhodit $image & $color ak to bude len vraciat body
     * @param gd $image
     * @param array $p1
     * @param array $p2
     * @param array $p3
     * @param array $p4
     * @param type $color
     */
    public static function drawfilled($image, $p1, $p2, $p3, $p4, $color)
    {
        return self::convert($p1, $p2, $p3, $p4, 1.0);
        //imagefilledpolygon($image, $polygon, count($polygon) / 2, $color);
    }

}
