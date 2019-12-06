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
