<?php

/***************
   NO COMMENT
(plugin to disable facebook comments on view page)
 ***************/

$FUNCTION_OVERRIDE["generate_comment_widget"] = "new_generate_comment_widget";

function new_generate_comment_widget() { ; }

