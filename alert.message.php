<?php
session_start();

function ErrorMessage(){
   if(isset($_SESSION['error'])){
        $output = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        $output .= htmlentities($_SESSION['error']); // htmlentities help complex data types to be able to show up as in a primitive data types
        $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="close"></buuton>';
        $output .='</div>';
        // .= helps to be able to assign another function to the same name function
        $_SESSION['error'] = null;
    
    return $output;
    }
}

function SuccessMessage(){
   if(isset($_SESSION['success'])){
        $output = '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        $output .= htmlentities($_SESSION['success']); // htmlentities help complex data types to be able to show up as in a primitive data types
        $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="close"></buuton>';
        $output .='</div>';
        // .= helps to be able to assign another function to the same name function
        $_SESSION['success'] = null;
    
    return $output;
    }
}