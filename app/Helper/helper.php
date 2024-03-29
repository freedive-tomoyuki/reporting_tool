<?php

use App\Asp;

if (! function_exists('asp_options')) {
    
    function asp_options($id='')
    {
        $asps = Asp::all();

        $options = "<option value=''>-- ASP --</option>";

        //$prefectures = Prefecture::all();
        foreach($asps as $a) {
            // echo $pref;
            if($a->id === $id) {
                $options .= "<option value=\"$a->id\" selected>$a->name</option>";
            } else {
                $options .= "<option value=\"$a->id\">$a->name</option>";
            }
        }
        return $options;
    }
}
if (! function_exists('get_col_key')) {
    function get_col_key($target)
    {
        for ($i = 0; $i < 26; $i++) {
            $alphabet[] = strtoupper(chr(ord('a') + $i));
        }
        $one = fmod($target, 26);
        $result = $alphabet[$one];
        $carry = ($target - $one) / 26;
        while ($carry != 0) {
            $one = fmod($carry - 1, 26);
            $result = $alphabet[$one].$result;
            $carry = ($carry - 1 - $one) / 26;
        }
        return $result;
    }
}

if (! function_exists('calc_percent')) {
    function calc_percent($x,$y)
    {
        $ans = 0;
        if($x!=0 && $y!=0){
            $ans = number_format(
                sprintf('%.2f',( $x / $y ) *100),2
            );
        }
        return $ans;
    }
}
if (! function_exists('calc_cpa')) {
    function calc_cpa($x,$y)
    {
        $ans = 0;
        if($x!=0 && $y!=0){
            $ans = number_format(
                sprintf('%.2f',( $x / $y ) *100),2
            );
        }
        return $ans;
    }
}