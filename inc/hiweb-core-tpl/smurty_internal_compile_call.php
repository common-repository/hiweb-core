<?php
/**
 * hiweb_tpl Internal Plugin Compile Function_Call
 * Compiles the calls of user defined tags defined by {function}
 *
 * @package    hiweb_tpl
 * @subpackage Compiler
 * @author     Uwe Tews
 */

/**
 * hiweb_tpl Internal Plugin Compile Function_Call Class
 *
 * @package    hiweb_tpl
 * @subpackage Compiler
 */
class Smurty_Internal_Compile_Call extends Smurty_Internal_CompileBase
{
    /**
     * Attribute definition: Overwrites base class.
     *
     * @var array
     * @see Smurty_Internal_CompileBase
     */
    public $required_attributes = array('name');
    /**
     * Attribute definition: Overwrites base class.
     *
     * @var array
     * @see Smurty_Internal_CompileBase
     */
    public $shorttag_order = array('name');
    /**
     * Attribute definition: Overwrites base class.
     *
     * @var array
     * @see Smurty_Internal_CompileBase
     */
    public $optional_attributes = array('_any');

    /**
     * Compiles the calls of user defined tags defined by {function}
     *
     * @param  array  $args     array with attributes from parser
     * @param  object $compiler compiler object
     *
     * @return string compiled code
     */
    public function compile($args, $compiler)
    {
        // check and get attributes
        $_attr = $this->getAttributes($compiler, $args);
        // save possible attributes
        if (isset($_attr['assign'])) {
            // output will be stored in a smurty variable instead of being displayed
            $_assign = $_attr['assign'];
        }
        $_name = $_attr['name'];
        if ($compiler->compiles_template_function) {
            $compiler->called_functions[] = trim($_name, "'\"");
        }
        unset($_attr['name'], $_attr['assign'], $_attr['nocache']);
        // set flag (compiled code of {function} must be included in cache file
        if ($compiler->nocache || $compiler->tag_nocache) {
            $_nocache = 'true';
        } else {
            $_nocache = 'false';
        }
        $_paramsArray = array();
        foreach ($_attr as $_key => $_value) {
            if (is_int($_key)) {
                $_paramsArray[] = "$_key=>$_value";
            } else {
                $_paramsArray[] = "'$_key'=>$_value";
            }
        }
        if (isset($compiler->template->properties['function'][$_name]['parameter'])) {
            foreach ($compiler->template->properties['function'][$_name]['parameter'] as $_key => $_value) {
                if (!isset($_attr[$_key])) {
                    if (is_int($_key)) {
                        $_paramsArray[] = "$_key=>$_value";
                    } else {
                        $_paramsArray[] = "'$_key'=>$_value";
                    }
                }
            }
        } elseif (isset($compiler->smurty->template_functions[$_name]['parameter'])) {
            foreach ($compiler->smurty->template_functions[$_name]['parameter'] as $_key => $_value) {
                if (!isset($_attr[$_key])) {
                    if (is_int($_key)) {
                        $_paramsArray[] = "$_key=>$_value";
                    } else {
                        $_paramsArray[] = "'$_key'=>$_value";
                    }
                }
            }
        }
        //variable name?
        if (!(strpos($_name, '$') === false)) {
            $call_cache = $_name;
            $call_function = '$tmp = "smurty_template_function_".' . $_name . '; $tmp';
        } else {
            $_name = trim($_name, "'\"");
            $call_cache = "'{$_name}'";
            $call_function = 'smurty_template_function_' . $_name;
        }

        $_params = 'array(' . implode(",", $_paramsArray) . ')';
        $_hash = str_replace('-', '_', $compiler->template->properties['nocache_hash']);
        // was there an assign attribute
        if (isset($_assign)) {
            if ($compiler->template->caching) {
                $_output = "<?php ob_start(); Smurty_Internal_Function_Call_Handler::call ({$call_cache},\$_smurty_tpl,{$_params},'{$_hash}',{$_nocache}); \$_smurty_tpl->assign({$_assign}, ob_get_clean());?>\n";
            } else {
                $_output = "<?php ob_start(); {$call_function}(\$_smurty_tpl,{$_params}); \$_smurty_tpl->assign({$_assign}, ob_get_clean());?>\n";
            }
        } else {
            if ($compiler->template->caching) {
                $_output = "<?php Smurty_Internal_Function_Call_Handler::call ({$call_cache},\$_smurty_tpl,{$_params},'{$_hash}',{$_nocache});?>\n";
            } else {
                $_output = "<?php {$call_function}(\$_smurty_tpl,{$_params});?>\n";
            }
        }

        return $_output;
    }
}
