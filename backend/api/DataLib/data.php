<?php
    function GET_GB($name)
    {
        switch ($name) {
            case 'assessments':
                include './data/assessments.php';
                break;
            default:
                # code...
                break;
        }
        return $GB_TRANSIT;
    }
?>