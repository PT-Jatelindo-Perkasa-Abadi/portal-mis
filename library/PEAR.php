<?php

class PEAR {
    public static function isError($data) {
        return ($data instanceof PEAR_Error);
    }
}

class PEAR_Error {

}